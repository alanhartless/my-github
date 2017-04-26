<?php

namespace App\Http\Controllers\Milestones;

use App\Http\Controllers\Controller;
use App\Http\Decorator\RateLimitTrait;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;

class IndexController extends Controller
{
    use RateLimitTrait;

    public function generate($login, $repo)
    {
        // Find the milestone number by fetching all milestones
        $paginator  = new ResultPager(GitHub::connection());
        $milestones = $paginator->fetchAll(GitHub::issues()->milestones(), 'all', [$login, $repo]);

        return view(
            'milestones.index',
            [
                'login'            => $login,
                'repo'             => $repo,
                'milestones'       => $milestones,
                'rateLimits'       => $this->getRateLimit()
            ]
        );
    }
}