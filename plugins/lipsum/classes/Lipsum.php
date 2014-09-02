<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Lipsum
{
    public function text($words = 100)
    {
        return $this->intText(1000);
    }

    public function img($width = 400, $height = 300)
    {

    }

    /**
     * @param $length
     * @return string
     */
    private function intText($length)
    {
        return substr(
            'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam '
            . 'nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam '
            . 'erat, sed diam voluptua. At vero eos et accusam et justo duo dolores '
            . 'et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus '
            . 'est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, '
            . 'consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt '
            . 'ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero '
            . 'eos et accusam et justo duo dolores et ea rebum. Stet clita kasd '
            . 'gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
            0,
            $length
        );
    }

    public function raw($str)
    {
        return $str;
    }
}
