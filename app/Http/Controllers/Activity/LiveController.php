<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Decorator\ActivityParserTrait;
use App\Http\Decorator\PaginationTrait;
use App\Http\Decorator\RateLimitTrait;
use Github\HttpClient\Message\ResponseMediator;
use GrahamCampbell\GitHub\Facades\GitHub;
use Guzzle\Http\Message\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class LiveController extends Controller
{
    use ActivityParserTrait;

    public function getActivity(Request $request)
    {
        $me          = GitHub::me()->show();
        $lastEventId = $request->session()->get('last_event_id', false);

        $activity = [];
        $interval = 60;
        if ($lastEventId) {
            list($interval, $activity) = $this->findNewActivity($me['login'], $lastEventId);

            if ($activity) {
                $request->session()->set('last_event_id', $activity[0]['id']);

                $this->parseActivity($activity, $me, Input::get('pending', 0));
            }
        }

        $html = view(
            'activity.live',
            [
                'me'       => $me,
                'activity' => $activity
            ]
        );

        $data = [
            'activity' => $html->render(),
            'interval' => (int) $interval * 1000,
            'count'    => count($activity)
        ];

        $response = \Illuminate\Support\Facades\Response::make(json_encode($data), 200);

        $response->header('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @param     $login
     * @param     $lastEventId
     * @param int $page
     *
     * @return array
     */
    private function findNewActivity ($login, $lastEventId, $page = 1)
    {
        /** @var Response $response */
        $response = GitHub::connection()->getHttpClient()->get(
            sprintf('/users/%s/received_events?page=%s', $login, (int) $page)
        );

        // Get the interval Github allows for polling
        $interval = $response->hasHeader('X-Poll-Interval') ? (string) $response->getHeader('X-Poll-Interval') : 60;

        $activity   = ResponseMediator::getContent($response);
        $pagination = ResponseMediator::getPagination($response);
        $isLastPage = empty($pagination['next']);

        $caughtUp    = false;
        $newActivity = [];

        foreach ($activity as $event) {
            if ($event['id'] == $lastEventId) {
                $caughtUp = true;

                break;
            }

            $newActivity[] = $event;
        }

        if (!$caughtUp && !$isLastPage) {
            // Try the next page
            list ($interval, $activity) = $this->findNewActivity($login, $lastEventId, $page + 1);

            $newActivity += $activity;
        }

        return [$interval, $newActivity];
    }
}