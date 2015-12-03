<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Decorator\NotificationSubjectTrait;
use App\Http\Decorator\PaginationTrait;
use App\Http\Decorator\RateLimitTrait;
use Github\HttpClient\Message\ResponseMediator;
use GrahamCampbell\GitHub\Facades\GitHub;
use Guzzle\Http\Message\Response;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    use NotificationSubjectTrait;

    public function getActivity(Request $request)
    {
        $me          = GitHub::me()->show();
        $lastEventId = $request->session()->get('last_notification_id', false);

        $activity = [];
        $interval = 60;
        if ($lastEventId) {
            list($interval, $activity) = $this->findNewActivity($me['login'], $lastEventId);

            if ($activity) {
                $request->session()->set('last_notification_id', $activity[0]['id']);

                // Mark as read
                try {
                    GitHub::notification()->markRead();
                } catch (\Exception $e) {
                    // Github returns empty string for this endpoint but the API library tries to parse it as json
                }

                foreach ($activity as &$notice) {
                    $notice['html_url'] = $this->getRelatedHtmlUrl($notice['subject']);
                }
            }
        }

        $html = view(
            'notifications.live',
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
            sprintf('/notifications?page=%s', $login, (int) $page)
        );

        // Get the interval Github allows for polling
        $interval    = $response->hasHeader('X-Poll-Interval') ? (string) $response->getHeader('X-Poll-Interval') : 60;
        $activity    = ResponseMediator::getContent($response);
        $newActivity = [];

        foreach ($activity as $event) {
            if ($event['id'] == $lastEventId) {

                break;
            }

            $newActivity[] = $event;
        }

        return [$interval, $newActivity];
    }
}