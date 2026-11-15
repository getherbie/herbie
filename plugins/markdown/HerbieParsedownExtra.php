<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use Parsedown;
use ParsedownExtra;

/**
 * ParsedownExtra subclass with PHP 8.3 compatibility fix.
 *
 * ParsedownExtra's processTag() calls DOMDocument::loadHTML() with markup
 * that can be empty or whitespace-only, which throws a ValueError on PHP 8.3+.
 * This subclass overrides processTag() entirely to guard against that case.
 */
class HerbieParsedownExtra extends ParsedownExtra
{
    /**
     * @param mixed $elementMarkup
     * @return string
     */
    protected function processTag($elementMarkup): string
    {
        if (!is_string($elementMarkup)) {
            return '';
        }
        if (trim($elementMarkup) === '') {
            return $elementMarkup;
        }

        // Suppress mb_convert_encoding deprecation (PHP 8.2+) and user deprecations (PHP 8.4+)
        $mask = E_DEPRECATED;
        if (defined('E_USER_DEPRECATION')) {
            $mask |= E_USER_DEPRECATION;
        }
        $previousLevel = error_reporting(error_reporting() & ~$mask);

        try {
            return parent::processTag($elementMarkup);
        } catch (\Throwable $e) {
            return $elementMarkup;
        } finally {
            error_reporting($previousLevel);
        }
    }
}
