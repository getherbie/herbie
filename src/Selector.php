<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class Selector
{
    /** @var string */
    protected $className;

    /** @var array */
    protected $operators = [
        "!=" => 'matchNotEqual',
        ">=" => 'matchGreaterThanEqual',
        "<=" => 'matchLessThanEqual',
        "*=" => 'matchContains',
        "^=" => 'matchStarts',
        "~=" => 'matchContainsWords',
        "$=" => 'matchEnds',
        "&"  => 'matchBitwiseAnd',
        ">"  => 'matchGreaterThan',
        "<"  => 'matchLessThan',
        "="  => 'matchEqual',
    ];

    /** @var array */
    protected $selectors = [];

    /**
     * Selector constructor.
     * @param $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Find and return all items matching the given selector string.
     *
     * =   Equal to
     * !=  Not equal to
     * <   Less than
     * >   Greater than
     * <=  Less than or equal to
     * >=  Greater than or equal to
     * *=  Contains the exact word or phrase
     * ~=  Contains all the words
     * ^=  Contains the exact word or phrase at the beginning of the field
     * $=  Contains the exact word or phrase at the end of the field
     * &   Bitwise and
     *
     * @param array|string $selector
     * @param array &$data
     * @return mixed
     * @throws \Exception
     */
    public function find($selector, $data)
    {
        $selectors = $this->getSelector($selector);
        $sort = $this->extractSort($selectors);
        $limit = $this->extractLimit($selectors);

        unset($selector);

        if (!empty($sort)) {
            $this->sort($sort, $data);
        }

        if (empty($selectors)) {
            return $data;
        }

        $return = [];
        $i = 1;
        foreach ($data as $key => $item) {
            if (($limit > 0) && ($i > $limit)) {
                break;
            }

            $bool = true;
            foreach ($selectors as $selector) {
                list($field, $value, $function) = $selector;
                if (!isset($item[$field])) {
                    $bool = false;
                    break;
                }
                $bool &= call_user_func_array([$this, $function], [$item[$field], $value]);
            }

            if ($bool) {
                $return[] = $item;
                $i++;
            }
        }

        return $return;
    }

    protected function extractSort(&$selectors)
    {
        $sort = "";
        foreach ($selectors as $index => $selector) {
            if ($selector[0] == "sort") {
                $sort = $selector[1];
                unset($selectors[$index]);
                break;
            }
        }
        return $sort;
    }

    protected function extractLimit(&$selectors)
    {
        $limit = 0;
        foreach ($selectors as $index => $selector) {
            if ($selector[0] == "limit") {
                $limit = abs(intval($selector[1]));
                unset($selectors[$index]);
                break;
            }
        }
        return $limit;
    }

    /**
     * @param string $selector
     * @param array &$data
     * @return mixed
     * @throws \Exception
     */
    public function get($selector, &$data)
    {
        $object = $this->find($selector, $data)->first();
        return $object;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchEqual($value1, $value2)
    {
        return $value1 == $value2;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchNotEqual($value1, $value2)
    {
        return $value1 != $value2;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchGreaterThan($value1, $value2)
    {
        return $value1 > $value2;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchLessThan($value1, $value2)
    {
        return $value1 < $value2;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchGreaterThanEqual($value1, $value2)
    {
        return $value1 >= $value2;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchLessThanEqual($value1, $value2)
    {
        return $value1 <= $value2;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchBitwiseAnd($value1, $value2)
    {
        return ((int)$value1) & ((int)$value2);
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchContains($value1, $value2)
    {
        return stripos($value1, $value2) !== false;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchContainsWords($value1, $value2)
    {
        $hasAll = true;
        $words = preg_split('/[-\s]/', $value2, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($words as $key => $word) {
            if (!preg_match('/\b' . preg_quote($word) . '\b/i', $value1)) {
                $hasAll = false;
                break;
            }
        }
        return $hasAll;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchStarts($value1, $value2)
    {
        return stripos(trim($value1), $value2) === 0;
    }

    /**
     * @param string $value1
     * @param string $value2
     * @return bool
     */
    protected function matchEnds($value1, $value2)
    {
        $value2 = trim($value2);
        $value1 = substr($value1, -1 * strlen($value2));
        return strcasecmp($value1, $value2) == 0;
    }

    /**
     * @param string|array $selector
     * @return array
     */
    protected function getSelector($selector)
    {
        if (is_array($selector)) {
            $selectors = $selector;
        } elseif (is_string($selector)) {
            $selectors = [trim($selector)];
        } else {
            throw new \InvalidArgumentException("Selector has to be a string or an array.");
        }
        unset($selector);

        $return = [];
        foreach ($selectors as $selector) {
            foreach ($this->operators as $op => $methodName) {
                $pos = stripos($selector, $op);
                if ($pos !== false) {
                    $return[] = [
                        substr($selector, 0, $pos),
                        substr($selector, $pos + strlen($op)),
                        $methodName
                    ];
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @param callable|string $sort
     * @param $items
     * @return bool
     */
    public function sort($sort, &$items)
    {
        if (is_numeric($sort)) {
            return false;
        }

        if (is_callable($sort)) {
            $bool = uasort($items, $sort);
            return $bool;
        }

        $field = "title";
        if (!empty($sort)) {
            $field = trim($sort, "+");
        }

        $direction = "asc";
        if (substr($field, 0, 1) === "-") {
            $field = substr($field, 1);
            $direction = "desc";
        }

        $bool = uasort($items, function ($value1, $value2) use ($field, $direction) {
            if (!isset($value1[$field]) || !isset($value2[$field])) {
                return 0;
            }
            if ($value1[$field] == $value2[$field]) {
                return 0;
            }
            if ($direction == 'asc') {
                return ($value1[$field] < $value2[$field]) ? -1 : 1;
            } else {
                return ($value2[$field] < $value1[$field]) ? -1 : 1;
            }
        });

        return $bool;
    }

    public static function mergeSelectors($selector1, $selector2)
    {
        $selectors = [];
        if (is_array($selector1)) {
            $selectors = $selector1;
        } else {
            $selectors[] = $selector1;
        }
        if (is_array($selector2)) {
            $selectors = array_merge($selectors, $selector2);
        } else {
            $selectors[] = $selector2;
        }
        $selectors = array_filter($selectors);
        return $selectors;
    }
}
