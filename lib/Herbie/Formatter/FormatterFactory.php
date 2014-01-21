<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Formatter;


class FormatterFactory
{

    /**
     * @param string $type
     * @return FormatterInterface
     */
    public static function create($type)
    {
        if (in_array($type, ['md', 'markdown'])) {
            $formatter = new MarkdownFormatter();
        } elseif (in_array($type, ['textile'])) {
            $formatter = new TextileFormatter();
        } else {
            $formatter = new RawFormatter();
        }
        return $formatter;
    }

}