<?php

namespace App\Http\Decorator;

use Github\HttpClient\Message\ResponseMediator;
use GrahamCampbell\GitHub\Facades\GitHub;
use Guzzle\Http\Message\Response;

trait PaginationTrait
{
    public function getPagination(Response $response)
    {
        $ghPagination = ResponseMediator::getPagination($response);

        $pagination   = [
            'first' => null,
            'prev'  => null,
            'next'  => null,
            'last'  => null,
            'count' => 0
        ];

        foreach ($ghPagination as $key => $url) {
            if (preg_match('/(.*?)\?page=(.*?)$/', $url, $match)) {
                $pagination[$key] = (int) $match[2];
            }
        }

        if (null !== $pagination['last']) {
            $pagination['count'] = $pagination['last'];
        } elseif (null !== $pagination['next']) {
            $pagination['count'] = $pagination['next'];
        } elseif (null !== $pagination['prev']) {
            $pagination['count'] = $pagination['prev'] + 1;
        }

        return $pagination;
    }
}