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

use Michelf\MarkdownExtra;

class MarkdownFormatter implements FormatterInterface
{

    /**
     * @param string $value
     * @return string
     */
    public function transform($value)
    {
        $parser = new MarkdownExtra();
        return $parser->transform($value);
    }

}