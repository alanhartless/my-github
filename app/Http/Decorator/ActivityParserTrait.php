<?php

namespace App\Http\Decorator;

use GrahamCampbell\GitHub\Facades\GitHub;

trait ActivityParserTrait
{
    public function parseActivity(array &$activity, $me, $pending = false)
    {
        foreach ($activity as &$event) {
            // Get the related PR
            list($login, $repo) = explode('/', $event['repo']['name']);
            $event['repo_owner'] = $login;
            $event['repo_name']  = $repo;

            $number = null;
            $isPr   = false;
            switch (true) {
                case isset($event['payload']['issue']['pull_request']):
                    $number         = $event['payload']['issue']['number'];
                    $isPr           = true;
                    break;
                case isset($event['payload']['pull_request']['number']):
                    $number         = $event['payload']['pull_request']['number'];
                    $isPr           = true;
                    break;
                case (isset($event['payload']['issue']['number'])):
                    $number         = $event['payload']['issue']['number'];
                    $event['state'] = $event['payload']['issue']['state'];
                    break;
            }

            if ($number) {
                if ($isPr) {
                    // Get the latest info on the PR and not what's cached with the activity
                    $event['pull_request'] = GitHub::pullRequest()->show($login, $repo, $number);
                    $event['state']        = $event['pull_request']['state'];
                    if (isset($event['payload']['issue']['pull_request'])) {
                        $event['payload']['issue']['pull_request'] =& $event['pull_request'];
                    } else {
                        $event['payload']['pull_request'] =& $event['pull_request'];
                    }
                } else {
                    // Get the latest info on the PR and not what's cached with the activity
                    $event['payload']['issue'] = GitHub::issue()->show($login, $repo, $number);
                }

                // Get list of comments
                $event['comments'] = GitHub::issue()->comments()->all($login, $repo, $number);

                if (count($event['comments'])) {
                    // Order by latest first
                    $event['comments'] = array_reverse($event['comments']);

                    // Did I comment last?
                    $event['user_last_replied'] = $event['comments'][0]['user']['login'];

                    // Get set the payload comment with updated version if applicable
                    if (isset($event['payload']['comment'])) {
                        foreach ($event['comments'] as $comment) {
                            if ($comment['id'] === $event['payload']['comment']['id']) {
                                $event['payload']['comment'] = $comment;
                                break;
                            }
                        }
                    }
                }
            }

            // Check for labels and set font color
            if (isset($event['payload']['issue']['labels'])) {
                foreach ($event['payload']['issue']['labels'] as &$label) {
                    $label['is_light'] = $this->isLightColor($label['color']);
                }
            } elseif (isset($event['payload']['pull_request']['labels'])) {
                foreach ($event['payload']['pull_request']['labels'] as &$label) {
                    $label['is_light'] = $this->isLightColor($label['color']);
                }
            }
        }

        if ($pending) {
            // Filter out those that have not been addressed by me yet
            foreach ($activity as $k => $event) {
                if (!isset($event['state']) || $event['state'] != 'open' || !isset($event['comments']) || !isset($event['user_last_replied']) || $event['user_last_replied'] == $me['login'])  {
                    unset($activity[$k]);
                }
            }
        }
    }

    /**
     * @param $hex
     *
     * @return bool
     */
    private function isLightColor($hex)
    {

        $c_r = hexdec(substr($hex, 0, 2));
        $c_g = hexdec(substr($hex, 2, 2));
        $c_b = hexdec(substr($hex, 4, 2));

        $brightness = (($c_r * 299) + ($c_g * 587) + ($c_b * 114)) / 1000;

        return ($brightness > 130);
    }

}