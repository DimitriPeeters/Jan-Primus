<?php

declare(strict_types=1);

namespace AEFS\Core;

final readonly class RouteMatch
{
    public function __construct(
        public Route $route,
        public RouteParameterBag $parameters
    ) {
    }
}