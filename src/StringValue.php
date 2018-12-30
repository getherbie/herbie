<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

class StringValue
{
    protected $value;

    /**
     * StringValue constructor.
     * @param string $value
     */
    public function __construct(string $value = '')
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function set(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }
}
