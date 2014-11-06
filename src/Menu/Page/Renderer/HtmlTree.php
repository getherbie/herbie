<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page\Renderer;

class HtmlTree extends \RecursiveIteratorIterator
{
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
     * @param string $class
     * @param array $template
     */
    public function __construct(\RecursiveIterator $iterator, $class, $template = [])
    {
        $this->class = $class;
        foreach($template as $key => $value) {
            $this->template[$key] = $value;
        }
        parent::__construct($iterator, \RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * @return void
     */
    public function beginIteration()
    {
        $this->output .= $this->getTemplate('beginIteration');
    }

    /**
     * @return void
     */
    public function endIteration()
    {
        $this->output .= $this->getTemplate('endIteration');
    }

    /**
     * @return void
     */
    public function beginChildren()
    {
        $this->output .= $this->getTemplate('beginChildren');
    }

    /**
     * @return void
     */
    public function endChildren() {
        $this->output .= $this->getTemplate('endChildren');
    }

    /**
     * @return string
     */
    public function render()
    {
        foreach($this as $item) {
            $this->output .= $this->getTemplate('beginCurrent');
            if(is_callable($this->itemCallback)) {
                $this->output .= call_user_func($this->itemCallback, $item);
            } else {
                $this->output .= $item->getMenuItem()->title;
            }
            if(!$this->callHasChildren()) {
                $this->output .= $this->getTemplate('endCurrent');
            }
        }
        return $this->output;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getTemplate($key)
    {
        $replacements = [
            '{class}' => $this->class,
            '{level}' => $this->getDepth()+1
        ];
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->template[$key]
        );
    }

}