<?php

namespace App\Http\Controllers\Repositories;

use App\Http\Controllers\Controller;
use App\Http\Decorator\RateLimitTrait;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;

class IndexController extends Controller
{
    use RateLimitTrait;

    public function showIndex()
    {
        $me            = GitHub::me()->show();
        $paginator     = new ResultPager(GitHub::connection());
        $repositories  = $paginator->fetchAll(GitHub::me(), 'repositories');
        $organizations = $paginator->fetchAll(GitHub::me(), 'organizations');
        $ignoredOrgs   = explode(',', env('GITHUB_IGNORE_ORGS', ''));

        $this->getIssuesAndPRs($repositories, $me, $me);

        foreach ($organizations as $i => &$org) {
            if (in_array($org['login'], $ignoredOrgs)) {
                unset($organizations[$i]);
                continue;
            }

            $paginator           = new ResultPager(GitHub::connection());
            $org['repositories'] = $paginator->fetchAll(GitHub::organization(), 'repositories', [$org['login'], ['sort' => '']]);

            usort($org['repositories'], function($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });

            $this->getIssuesAndPRs($org['repositories'], $org, $me);
        }

        return view(
            'repositories.index',
            [
                'me'         => $me,
                'repos'      => $repositories,
                'orgs'       => $organizations,
                'rateLimits' => $this->getRateLimit()
            ]
        );
    }

    /**
     * @param $repositories
     * @param $org
     * @param $me
     */
    private function getIssuesAndPRs(&$repositories, $org, $me)
    {
        foreach ($repositories as &$repo) {
            // Get pulls requests from issue list
            $pulls  = [];
            $issues = [];

            $paginator = new ResultPager(GitHub::connection());
            $allIssues = $paginator->fetchAll(
                GitHub::issues(),
                'all',
                [
                    $org['login'],
                    $repo['name'],
                    [
                        'creator' => $me['login']
                    ]
                ]
            );

            foreach ($allIssues as $issue) {
                if (isset($issue['pull_request'])) {
                    $pulls[] = $issue;
                } else {
                    $issues[] = $issue;
                }
            }

            $repo['issues'] = $issues;
            $repo['pulls']  = $pulls;
        }
    }
}