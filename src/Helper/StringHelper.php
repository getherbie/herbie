<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Helper;

class StringHelper
{
    /**
     * @see https://github.com/alixaxel/phunction/blob/master/phunction/Text.php#L297
     */
    public static function unaccent($string)
    {
        if (extension_loaded('intl') === true) {
            $string = Normalizer::normalize($string, Normalizer::FORM_KD);
        }
        if (strpos($string = htmlentities($string, ENT_QUOTES, 'UTF-8'), '&') !== false) {
            $string = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|caron|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');
        }
        return $string;
    }

    public static function removeOneNewlineAtEnd($string)
    {

    }

    public static function escapeNonAsciiCharacters($string)
    {
        return addcslashes($string, "\0..\37!@\177..\377");
    }

}

