<?php

namespace App\Http\Decorator;

use GrahamCampbell\GitHub\Facades\GitHub;

trait RateLimitTrait
{
    public function getRateLimit()
    {
        $rateLimits = GitHub::api('rate_limit')->getRateLimits();
        $limits     = $rateLimits['resources']['core'];

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($limits['reset']);
        $limits['reset_at'] = $dateTime;

        return $limits;
    }
}