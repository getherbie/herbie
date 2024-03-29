<?php

declare(strict_types=1);

namespace herbie\sysplugins\twig;

use RecursiveIterator;
use RecursiveTreeIterator;

final class PageTreeTextRenderer extends RecursiveTreeIterator
{
    public string $emptyTitle = '[]';

    public function __construct(RecursiveIterator $iterator)
    {
        parent::__construct($iterator);
        $this->setPrefixPart(self::PREFIX_LEFT, '');
        $this->setPrefixPart(self::PREFIX_MID_HAS_NEXT, '│ ');
        $this->setPrefixPart(self::PREFIX_END_HAS_NEXT, '├ ');
        $this->setPrefixPart(self::PREFIX_END_LAST, '└ ');
    }

    public function render(): string
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
