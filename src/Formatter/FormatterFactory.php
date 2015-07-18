<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <http://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Formatter;

class FormatterFactory
{
    /**
     * @param string $format
     * @return FormatterInterface
     */
    public static function create($format)
    {
        if (in_array($format, ['md', 'markdown'])) {
            $formatter = new MarkdownFormatter();
        } elseif (in_array($format, ['textile'])) {
            $formatter = new TextileFormatter();
        } else {
            $formatter = new RawFormatter();
        }
        return $formatter;
    }
}
