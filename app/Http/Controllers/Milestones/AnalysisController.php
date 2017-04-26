<?php

namespace App\Http\Controllers\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Decorator\RateLimitTrait;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;

class AnalysisController extends Controller
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
                $milestone = $result;
                break;
            }
        }

        $prs = [];
        $team = explode(',', env('GITHUB_HIDE_AUTHORS'));

        foreach ($milestoneIssues as $k => $issue) {
            if (isset($issue['pull_request']) && $issue['state'] !== 'closed') {
                $needsDocumentation = false;
                $hasConflicts       = false;
                $status = false;
                foreach ($issue['labels'] as $label) {
                    switch (true) {
                        case ('Pending Test Confirmation' == $label['name']):
                            $status = 'Pending Test Confirmation';
                            break;
                        case ('Code Review' == $label['name']):
                            $status = 'Code Review';
                            break;
                        case ('Pending Feedback' == $label['name']):
                            $status = 'Pending Feedback';
                            break;
                        case ('Ready To Commit' == $label['name']):
                            $status = "Ready To Commit";
                            break;
                    }

                    if ('Needs Documentation' == $label['name']) {
                        $needsDocumentation = true;
                    }

                    if ($status) {
                        break;
                    }
                }

                if (!$status) {
                    $status = 'Needs Testing';
                }

                $pull = GitHub::pullRequest()->show($login, $repo, $issue['number']);
                if (empty($pull['mergeable'])) {
                    $hasConflicts = true;
                }

                $plusOne = [];
                $feedBackBy = [];
                $comments = $event['comments'] = GitHub::issue()->comments()->all($login, $repo, $issue['number']);
                foreach ($comments as $comment) {
                    if (in_array($comment['user']['login'], $team)) {
                        if (strpos($comment['body'], '+1') !== false) {
                            $plusOne[] = $comment['user']['login'];
                        }
                        $feedBackBy[$comment['user']['login']] = $comment['user']['login'];
                    }
                }

                $pleaseTest = array_diff($team, $plusOne, [$issue['user']['login']]);

                natcasesort($pleaseTest);
                natcasesort($plusOne);
                natcasesort($feedBackBy);

                $prs[$issue['number']] = [
                    'author' => $issue['user']['login'],
                    'name' => $issue['title'],
                    'link' => $issue['html_url'],
                    'status' => $status,
                    'hasConflicts' => $hasConflicts,
                    'needsDocs' => $needsDocumentation,
                    'plusOne' => $plusOne,
                    'pleaseTest' => $pleaseTest,
                    'feedbackBy' => $feedBackBy
                ];
            }
        }

        uasort($prs, function ($a, $b) {
            return strnatcasecmp($a['author'], $b['author']);
        });

        return view(
            'milestones.analyasis',
            [
                'login'            => $login,
                'repo'             => $repo,
                'milestone'        => $milestone,
                'pullRequests'     => $prs,
                'rateLimits'       => $this->getRateLimit()
            ]
        );
    }
}