<?php

namespace App\Http\Controllers\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Decorator\RateLimitTrait;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;

class ChangelogController extends Controller
{
    use RateLimitTrait;

    public function generate($login, $repo, $milestone)
    {
        // Find the milestone number by fetching all milestones
        $paginator  = new ResultPager(GitHub::connection());
        $milestones = $paginator->fetchAll(GitHub::issues()->milestones(), 'all', [$login, $repo]);

        // Fetch the issues for this milestone
        $milestoneIssues = [];
        foreach ($milestones as $result) {
            if ($milestone == $result['title']) {
                $milestoneIssues = $paginator->fetchAll(
                    GitHub::issues(),
                    'all',
                    [$login, $repo, ['milestone' => $result['number'], 'state' => 'all']]
                );

                break;
            }
        }

        $pullRequests     = [];
        $issues           = [];
        $acknowledgements = [];
        $labels           = [];
        $hideAuthors      = explode(',', env('GITHUB_HIDE_AUTHORS'));
        $labelGroups      = explode(',', env('GITHUB_LABELS'));
        foreach ($labelGroups as &$label) {
            list($labelName, $groupName) = explode('|', $label);

            $labels[$labelName] = $groupName;
        }
        $defaultGroup = $labels[env('GITHUB_DEFAULT_GROUP')];

        foreach ($milestoneIssues as $k => $issue) {
            $issue['submitter'] = in_array($issue['user']['login'], $hideAuthors) ? '' : $issue['user']['login'];
            if (!empty($issue['submitter']) && !in_array('@'.$issue['submitter'], $acknowledgements)) {
                $acknowledgements[] = '@'.$issue['submitter'];
            }
            if (isset($issue['pull_request'])) {
                $labelFound = false;
                foreach ($issue['labels'] as $label) {
                    if (array_key_exists($label['name'], $labels)) {
                        $pullRequests[$labels[$label['name']]][] = $issue;
                        $labelFound                              = true;
                        break;
                    }
                }
                if (!$labelFound) {
                    $pullRequests[$defaultGroup][] = $issue;
                }
            } else {
                $labelFound = false;
                foreach ($issue['labels'] as $label) {
                    if (array_key_exists($label['name'], $labels)) {
                        $issues[$labels[$label['name']]][] = $issue;
                        $labelFound                        = true;
                        break;
                    }
                }
                if (!$labelFound) {
                    $issues[$defaultGroup][] = $issue;
                }
            }
        }

        // Sort by label order
        $pullRequests = array_merge(array_flip($labels), $pullRequests);
        $issues       = array_merge(array_flip($labels), $issues);

        // Set acknowledgements
        natcasesort($acknowledgements);
        $acknowledgements = implode(', ', $acknowledgements);

        return view(
            'milestones.changelog',
            [
                'login'            => $login,
                'repo'             => $repo,
                'milestone'        => $milestone,
                'pullRequests'     => $pullRequests,
                'issues'           => $issues,
                'acknowledgements' => $acknowledgements,
                'rateLimits'       => $this->getRateLimit()
            ]
        );
    }
}