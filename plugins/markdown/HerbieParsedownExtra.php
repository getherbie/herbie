<?php

declare(strict_types=1);

namespace herbie\sysplugins\markdown;

use Parsedown;
use ParsedownExtra;

/**
 * ParsedownExtra subclass with PHP 8.3 compatibility fix.
 *
 * ParsedownExtra 0.8.x accesses $Block['element']['text'] without checking
 * whether the key exists, which triggers an "Undefined array key" warning on
 * PHP 8.0+ (promoted to an exception by Herbie's error handler).
 */
class HerbieParsedownExtra extends ParsedownExtra
{
    protected function blockHeader($Line)
    {
        // Call Parsedown::blockHeader directly to skip the buggy implementation
        // in ParsedownExtra::blockHeader.
        $reflection = new \ReflectionMethod(Parsedown::class, 'blockHeader');
        $reflection->setAccessible(true);
        $Block = $reflection->invoke($this, $Line);

        if (!isset($Block)) {
            return null;
        }

        $text = $Block['element']['text'] ?? '';
        if (preg_match('/[ #]*{(' . $this->regexAttribute . '+)}[ ]*$/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $attributeString = $matches[1][0];

            $Block['element']['attributes'] = $this->parseAttributeData($attributeString);

            $Block['element']['text'] = substr($text, 0, $matches[0][1]);
        }

        return $Block;
    }
}
