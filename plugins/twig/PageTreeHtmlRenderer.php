<?php

declare(strict_types=1);

namespace herbie\plugins\twig;

use herbie\Page;
use RecursiveIterator;
use RecursiveIteratorIterator;

/**
 * @method Page getMenuItem()
 */
/** @extends \RecursiveIteratorIterator<\IteratorAggregate|\RecursiveIterator> */
final class PageTreeHtmlRenderer extends RecursiveIteratorIterator
{
    private string $class;

    private string $output;

    /** @var array<string, string> */
    private array $template = [
        'beginIteration' => '<div class="{class}"><ul>',
        'endIteration' => '</ul></div>',
        'beginChildren' => '<ul>',
        'endChildren' => '</ul></li>',
        'beginCurrent' => '<li>',
        'endCurrent' => '</li>',
    ];

    /** @var callable|null */
    private $itemCallback;

    /**
     * @param \RecursiveIterator<mixed, mixed> $iterator
     * @param 0|1|2 $mode
     * @param 0|16 $flags
     */
    public function __construct(
        RecursiveIterator $iterator,
        int $mode = RecursiveIteratorIterator::SELF_FIRST,
        int $flags = 0,
    ) {
        parent::__construct($iterator, $mode, $flags);
        $this->class = '';
        $this->output = '';
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * @param array<string, string> $options
     */
    public function setTemplate(array $options = []): void
    {
        foreach ($options as $key => $value) {
            $this->template[$key] = $value;
        }
    }

    public function setItemCallback(callable $callback): void
    {
        $this->itemCallback = $callback;
    }

    #[\Override]
    public function beginIteration(): void
    {
        $this->output .= $this->getTemplate('beginIteration');
    }

    private function getTemplate(string $key): string
    {
        $replacements = [
            '{class}' => $this->class,
            '{level}' => $this->getDepth() + 1,
        ];
        return strtr($this->template[$key], $replacements);
    }

    #[\Override]
    public function endIteration(): void
    {
        $this->output .= $this->getTemplate('endIteration');
    }

    #[\Override]
    public function beginChildren(): void
    {
        $this->output .= $this->getTemplate('beginChildren');
    }

    #[\Override]
    public function endChildren(): void
    {
        $this->output .= $this->getTemplate('endChildren');
    }

    public function render(string $route = ''): string
    {
        foreach ($this as $item) {
            $beginCurrent = $this->getTemplate('beginCurrent');
            $this->output .= $this->addCssClasses($beginCurrent, $route);
            if (is_callable($this->itemCallback)) {
                $this->output .= call_user_func($this->itemCallback, $item);
            } else {
                $this->output .= $item->getMenuItem()->getTitle();
            }
            if (!$this->callHasChildren()) {
                $this->output .= $this->getTemplate('endCurrent');
            }
        }
        return $this->output;
    }

    private function addCssClasses(string $beginCurrent, string $route): string
    {
        /** @phpstan-ignore-next-line */
        $menuItemRoute = $this->getMenuItem()->getRoute();
        $cssClasses = [];
        if ($route === $menuItemRoute) {
            $cssClasses[] = 'current';
        }
        if (!empty($menuItemRoute)) {
            if (str_starts_with($route, $menuItemRoute)) {
                $cssClasses[] = 'active';
            }
        }
        if (!empty($cssClasses)) {
            $classString = sprintf(' class="%s"', implode(' ', $cssClasses));
            $beginCurrent = str_replace('>', $classString . '>', $beginCurrent);
        }
        return $beginCurrent;
    }
}
