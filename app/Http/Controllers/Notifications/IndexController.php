<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Decorator\NotificationSubjectTrait;
use App\Http\Decorator\RateLimitTrait;
use GrahamCampbell\GitHub\Facades\GitHub;
use Guzzle\Http\Message\Response;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    use RateLimitTrait;
    use NotificationSubjectTrait;

    public function showIndex(Request $request, $page = 1)
    {
        $me = GitHub::me()->show();

        /** @var Response $response */
        $activity = GitHub::notification()->all();

        // Save the latest activity ID for live fetching
        if (count($activity)) {
            $request->session()->put('last_notification_id', $activity[0]['id']);

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

        // Get the interval Github allows for polling
        $response = GitHub::connection()->getHttpClient()->getLastResponse();
        $interval = $response->hasHeader('X-Poll-Interval') ? (string) $response->getHeader('X-Poll-Interval') : 60;

        return view(
            'notifications.index',
            [
                'me'         => $me,
                'activity'   => $activity,
                'page'       => $page,
                'interval'   => (int) $interval * 1000
            ]
        );
    }
}