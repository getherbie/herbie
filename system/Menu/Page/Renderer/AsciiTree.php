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

class AsciiTree extends \RecursiveTreeIterator
{

    /**
     * @var string
     */
    public $emptyTitle = '[]';

    /**
     * @param \RecursiveIterator $iterator
     */
    public function __construct(\RecursiveIterator $iterator)
    {
        parent::__construct($iterator);
        $this->setPrefixPart(self::PREFIX_LEFT, '');
        $this->setPrefixPart(self::PREFIX_MID_HAS_NEXT, '│ ');
        $this->setPrefixPart(self::PREFIX_END_HAS_NEXT, '├ ');
        $this->setPrefixPart(self::PREFIX_END_LAST, '└ ');
    }

    /**
     * @return string
     */
    public function render()
    {
        $output = '';
        foreach ($this as $item) {
            $title = $this->getEntry();
            $output .= $this->getPrefix();
            $output .= empty($title) ? $this->emptyTitle : $title;
            $output .= PHP_EOL;
        }
        return $output;
    }
}
