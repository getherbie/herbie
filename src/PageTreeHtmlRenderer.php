<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

class PageTreeHtmlRenderer extends \RecursiveIteratorIterator
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $output;

    /**
     * @var array
     */
    private $template = [
        'beginIteration' => '<div class="{class}"><ul>',
        'endIteration' => '</ul></div>',
        'beginChildren' => '<ul>',
        'endChildren' => '</ul></li>',
        'beginCurrent' => '<li>',
        'endCurrent' => '</li>'
    ];

    /**
     * @var callable
     */
    public $itemCallback;

    /**
     * @param \RecursiveIterator $iterator
     * @param int $mode
     * @param int $flags
     */
    public function __construct(
        \RecursiveIterator $iterator,
        int $mode = \RecursiveIteratorIterator::SELF_FIRST,
        int $flags = 0
    ) {
        parent::__construct($iterator, $mode, $flags);
        $this->class = '';
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * @param array $options
     */
    public function setTemplate(array $options = []): void
    {
        foreach ($options as $key => $value) {
            $this->template[$key] = $value;
        }
    }

    /**
     * @return void
     */
    public function beginIteration(): void
    {
        $this->output .= $this->getTemplate('beginIteration');
    }

    /**
     * @return void
     */
    public function endIteration(): void
    {
        $this->output .= $this->getTemplate('endIteration');
    }

    /**
     * @return void
     */
    public function beginChildren(): void
    {
        $this->output .= $this->getTemplate('beginChildren');
    }

    /**
     * @return void
     */
    public function endChildren(): void
    {
        $this->output .= $this->getTemplate('endChildren');
    }

    /**
     * @param string $route
     * @return string
     */
    public function render(string $route = ''): string
    {
        foreach ($this as $item) {
            $beginCurrent = $this->getTemplate('beginCurrent');
            $this->output .= $this->addCssClasses($beginCurrent, $route);
            if (is_callable($this->itemCallback)) {
                $this->output .= call_user_func($this->itemCallback, $item);
            } else {
                $this->output .= $item->getMenuItem()->title;
            }
            if (!$this->callHasChildren()) {
                $this->output .= $this->getTemplate('endCurrent');
            }
        }
        return $this->output;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getTemplate(string $key): string
    {
        $replacements = [
            '{class}' => $this->class,
            '{level}' => $this->getDepth()+1
        ];
        return strtr($this->template[$key], $replacements);
    }

    /**
     * @param string $beginCurrent
     * @param string $route
     * @return string
     */
    private function addCssClasses(string $beginCurrent, string $route): string
    {
        $menuItem = $this->getMenuItem();
        $cssClasses = [];
        if ($route == $menuItem->route) {
            $cssClasses[] = 'current';
        }
        if (!empty($menuItem->route)) {
            if (strpos($route, $menuItem->route) === 0) {
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
