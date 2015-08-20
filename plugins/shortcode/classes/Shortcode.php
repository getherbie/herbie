<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugin\shortcode\classes;

class Shortcode
{
    /**
     * @var array
     */
    private $tags = [];

    /**
     * @param array $tags
     */
    public function __construct(array $tags = [])
    {
        $this->tags = $tags;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $tag
     * @param callable $callable
     */
    public function add($tag, callable $callable)
    {
        $this->tags[$tag] = $callable;
    }

    /**
     * @param string $tag
     */
    public function remove($tag)
    {
        if (array_key_exists($tag, $this->tags)) {
            unset($this->tags[$tag]);
        }
    }

    /**
     * @return void
     */
    public function removeAll()
    {
        $this->tags = [];
    }

    /**
     * @param string $tag
     * @return boolean
     */
    public function exists($tag)
    {
        return array_key_exists($tag, $this->tags);
    }

    /**
     * @param string $content
     * @param string $tag
     * @return boolean
     */
    public function has($content, $tag)
    {
        if (false === strpos($content, '[')) {
            return false;
        }

        if ($this->exists($tag)) {
            preg_match_all('/' . $this->getRegex() . '/s', $content, $matches, PREG_SET_ORDER);
            if (empty($matches)) {
                return false;
            }
            foreach ($matches as $shortcode) {
                if ($tag === $shortcode[2]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $content
     * @return string
     */
    public function parse($content)
    {
        if (false === strpos($content, '[')) {
            return $content;
        }

        if (empty($this->tags) || !is_array($this->tags)) {
            return $content;
        }

        $pattern = $this->getRegex();
        return preg_replace_callback("/$pattern/s", [$this, 'parseShortcode'], $content);
    }

    /**
     * Retrieve the shortcode regular expression for searching.
     *
     * The regular expression combines the shortcode tags in the regular expression
     * in a regex class.
     *
     * The regular expression contains 6 different sub matches to help with parsing.
     *
     * 1 - An extra [ to allow for escaping shortcodes with double [[]]
     * 2 - The shortcode name
     * 3 - The shortcode argument list
     * 4 - The self closing /
     * 5 - The content of a shortcode when it wraps some content.
     * 6 - An extra ] to allow for escaping shortcodes with double [[]]
     *
     * @return string The shortcode search regular expression
     */
    private function getRegex()
    {
        $tagnames = array_keys($this->tags);
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        // WARNING! Do not change this regex without changing parseShortcode() and stripShortcode()
        // Also, see shortcode_unautop() and shortcode.js.
        return
            '\\['                              // Opening bracket
            . '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
            . "($tagregexp)"                     // 2: Shortcode name
            . '(?![\\w-])'                       // Not followed by word character or hyphen
            . '('                                // 3: Unroll the loop: Inside the opening shortcode tag
            . '[^\\]\\/]*'                   // Not a closing bracket or forward slash
            . '(?:'
            . '\\/(?!\\])'               // A forward slash not followed by a closing bracket
            . '[^\\]\\/]*'               // Not a closing bracket or forward slash
            . ')*?'
            . ')'
            . '(?:'
            . '(\\/)'                        // 4: Self closing tag ...
            . '\\]'                          // ... and closing bracket
            . '|'
            . '\\]'                          // Closing bracket
            . '(?:'
            . '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
            . '[^\\[]*+'             // Not an opening bracket
            . '(?:'
            . '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
            . '[^\\[]*+'         // Not an opening bracket
            . ')*+'
            . ')'
            . '\\[\\/\\2\\]'             // Closing shortcode tag
            . ')?'
            . ')'
            . '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
    }

    /**
     * @param array $m
     * @return mixed
     */
    private function parseShortcode($m)
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        $tag = $m[2];
        $attr = $this->parseAttributes($m[3]);

        if (isset($m[5])) {
            // enclosing tag - extra parameter
            return $m[1] . call_user_func($this->tags[$tag], $attr, $m[5], $tag) . $m[6];
        } else {
            // self-closing tag
            return $m[1] . call_user_func($this->tags[$tag], $attr, null, $tag) . $m[6];
        }
    }

    /**
     * @param string $text
     * @return array
     */
    private function parseAttributes($text)
    {
        $atts = [];
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1])) {
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                } elseif (!empty($m[3])) {
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                } elseif (!empty($m[5])) {
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                } elseif (isset($m[7]) && strlen($m[7])) {
                    $atts[] = stripcslashes($m[7]);
                } elseif (isset($m[8])) {
                    $atts[] = stripcslashes($m[8]);
                }
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    /**
     * @param array $pairs
     * @param array $atts
     * @param string $shortcode
     * @return array
     */
    public function shortcodeAtts(array $pairs, array $atts, $shortcode = '')
    {
        $atts = (array) $atts;
        $out = [];
        foreach ($pairs as $name => $default) {
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
        }
        if ($shortcode) {
            $out = apply_filters("shortcodeAtts{$shortcode}", $out, $pairs, $atts);
        }
        return $out;
    }

    /**
     * @param string $content
     * @return string
     */
    public function stripShortcodes($content)
    {
        if (false === strpos($content, '[')) {
            return $content;
        }

        if (empty($this->tags) || !is_array($this->tags)) {
            return $content;
        }
        $pattern = $this->getRegex();

        return preg_replace_callback("/$pattern/s", [$this, 'stripShortcode'], $content);
    }

    /**
     * @param array $m
     * @return string
     */
    private function stripShortcode($m)
    {
        // allow [[foo]] syntax for escaping a tag
        if ($m[1] == '[' && $m[6] == ']') {
            return substr($m[0], 1, -1);
        }

        return $m[1] . $m[6];
    }
}
