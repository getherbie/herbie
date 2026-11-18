<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\Page;

final class RenderPageEvent extends AbstractEvent
{
    public function __construct(
        private readonly Page $page,
        private readonly string $route,
        private readonly array $routeParams,
    ) {}

    public function getPage(): Page
    {
        return $this->page;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }
}
