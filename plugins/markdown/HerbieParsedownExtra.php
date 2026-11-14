<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use Parsedown;
use ParsedownExtra;

/**
 * ParsedownExtra subclass with PHP 8.3 compatibility fix.
 *
 * ParsedownExtra's blockMarkupComplete() calls processTag('') with an empty
 * string when processing certain markup, which causes DOMDocument::loadHTML('')
 * to throw a ValueError on PHP 8.3+.
 * This subclass catches that case and returns an empty string instead.
 */
class HerbieParsedownExtra extends ParsedownExtra
{
    protected function processTag($tag, $attr = '', $close = false)
    {
        if ($tag === '') {
            return '';
        }

        return parent::processTag($tag, $attr, $close);
    }
}
