<?php

namespace App\Http\Decorator;


use Github\HttpClient\Message\ResponseMediator;
use GrahamCampbell\GitHub\Facades\GitHub;

trait NotificationSubjectTrait
{

    protected function getRelatedHtmlUrl($subject)
    {
        /** @var Response $response */
        $response = GitHub::connection()->getHttpClient()->get($subject['latest_comment_url']);

        $subjectData = ResponseMediator::getContent($response);

        return $subjectData['html_url'];
    }
}