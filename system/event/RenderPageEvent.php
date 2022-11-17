<?php

declare(strict_types=1);

namespace herbie\event;

use herbie\AbstractEvent;
use herbie\Page;

final class RenderPageEvent extends AbstractEvent
{
    private Page $page;
    private string $route;
    private array $routeParams;

    public function __construct(Page $page, string $route, array $routeParams)
    {
        $this->page = $page;
        $this->route = $route;
        $this->routeParams = $routeParams;
    }

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
