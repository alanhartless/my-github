<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Decorator\PaginationTrait;
use App\Http\Decorator\RateLimitTrait;
use Github\HttpClient\Message\ResponseMediator;
use GrahamCampbell\GitHub\Facades\GitHub;
use Guzzle\Http\Message\Response;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    use RateLimitTrait;
    use PaginationTrait;

    public function showIndex(Request $request, $page = 1)
    {
        $me = GitHub::me()->show();

        /** @var Response $response */
        $response = GitHub::connection()->getHttpClient()->get(
            sprintf('/users/%s/received_events?page=%s', $me['login'], (int) $page)
        );

        $activity   = ResponseMediator::getContent($response);
        $pagination = $this->getPagination($response);

        // Save the latest activity ID for live fetching
        if (count($activity)) {
            $request->session()->put('last_event_id', $activity[0]['id']);
        }

        // Get the interval Github allows for polling
        $interval = $response->hasHeader('X-Poll-Interval') ? (string) $response->getHeader('X-Poll-Interval') : 60;

        return view(
            'activity.index',
            [
                'me'         => $me,
                'activity'   => $activity,
                'pagination' => $pagination,
                'page'       => $page,
                'interval'   => (int) $interval * 1000
            ]
        );
    }
}