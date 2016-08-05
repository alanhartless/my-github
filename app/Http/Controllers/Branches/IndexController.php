<?php

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\Controller;
use App\Http\Decorator\RateLimitTrait;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;

class IndexController extends Controller
{
    use RateLimitTrait;

    public function showIndex($login, $repo)
    {
        $repoInfo = GitHub::repos()->show($login, $repo);
        if ($repoInfo['fork']) {
            $pullFrom      = $repoInfo['parent']['owner']['login'];
            $defaultBranch = $repoInfo['parent']['default_branch'];
        } else {
            $pullFrom      = $login;
            $defaultBranch = $repoInfo['default_branch'];
        }

        if ($pullFrom != $login) {
            $parentBranch = GitHub::repos()->branches($pullFrom, $repo, $defaultBranch);

            // Get pulls requests from issue list
            $pulls = [];

            $paginator = new ResultPager(GitHub::connection());
            $issues    = $paginator->fetchAll(
                GitHub::issues(),
                'all',
                [
                    $pullFrom,
                    $repo,
                    [
                        'creator' => $repoInfo['owner']['login']
                    ]
                ]
            );

            foreach ($issues as $issue) {
                if (isset($issue['pull_request'])) {
                    $pull = GitHub::pullRequest()->show($pullFrom, $repo, $issue['number']);

                    $pulls[$pull['head']['ref']][] = $pull;
                }
            }
        }

        // Get branches for this repo
        $branches = GitHub::gitData()->references()->branches($login, $repo);
        foreach ($branches as &$branch) {
            $branch['name'] = str_replace('refs/heads/', '', $branch['ref']);

            if ($pullFrom == $login) {
                $branch['pulls'] = GitHub::pullRequests()->all(
                    $pullFrom,
                    $repo,
                    [
                        'base' => $branch['name']
                    ]
                );
            } else {
                $branch['pulls'] = (isset($pulls[$branch['name']])) ? $pulls[$branch['name']] : [];
            }

            if (empty($branch['pulls']) && isset($parentBranch)) {
                // Check to see if there are PR changes
                $compare = GitHub::repos()->commits()->compare($login, $repo, "$pullFrom:$defaultBranch", "$login:{$branch['name']}");
                $branch['comparison'] = $compare['status'];
            }
        }

        return view(
            'branches.index',
            [
                'login'         => $login,
                'repo'          => $repo,
                'branches'      => $branches,
                'rateLimits'    => $this->getRateLimit(),
                'defaultBranch' => $defaultBranch
            ]
        );
    }
}