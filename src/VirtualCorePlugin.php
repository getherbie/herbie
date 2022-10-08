<?php

declare(strict_types=1);

namespace herbie;

final class VirtualCorePlugin extends Plugin
{
    public function filters(): array
    {
        return [
            ['renderLayout', [$this, 'renderLayout']],
            ['renderSegment', [$this, 'renderSegment']]
        ];
    }

    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        /** @var Page $page */
        $page = $params['page'];
        if (!empty($page->getTwig())) {
            $context = $this->twigRenderer->renderString($context, $params);
        }
        return $filter->next($context, $params, $filter);
    }

    public function renderLayout(string $context, array $params, FilterInterface $filter): string
    {
        /** @var Page $page */
        $page = $params['page'];
        $extension = trim($this->config->get('fileExtensions.layouts'));
        $name = empty($extension) ? $page->getLayout() : sprintf('%s.%s', $page->getLayout(), $extension);
        $context = $this->twigRenderer->renderTemplate($name, $params);
        return $filter->next($context, $params, $filter);
    }    
}
