<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use Parsedown;
use ParsedownExtra;

/**
 * ParsedownExtra subclass with PHP 8.3 compatibility fix.
 *
 * ParsedownExtra's blockMarkupComplete() calls processTag() with markup
 * that can be empty or whitespace-only, which causes DOMDocument::loadHTML()
 * to throw a ValueError on PHP 8.3+.
 * This subclass guards against that case.
 */
class HerbieParsedownExtra extends ParsedownExtra
{
    /**
     * @param mixed $elementMarkup
     * @return string
     */
    protected function processTag($elementMarkup): string
    {
        if (!is_string($elementMarkup) || trim($elementMarkup) === '') {
            return '';
        }

        try {
            return parent::processTag($elementMarkup);
        } catch (\Throwable $e) {
            return $elementMarkup;
        }
    }
}
