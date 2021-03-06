<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class FilterIterator implements \Iterator, \Countable, FilterInterface
{
    private $position = 0;

    private $items = [];

    public function insert(callable $callback)
    {
        $this->items[] = $callback;
    }

    public function remove(callable $callback)
    {
        foreach ($this->items as $i => $item) {
            if ($callback === $item) {
                unset($this->items[$i]);
                $this->items = array_values($this->items);
                $this->position = ($i === 0) ? 0 : $i - 1;
                break;
            }
        }
    }

    /**
     * @param mixed|null $context
     * @param array $params
     * @param FilterInterface|null $filters
     * @return mixed|null
     */
    public function next($context = null, array $params = [], FilterInterface $filters = null)
    {
        if (is_null($context) || is_null($filters)) {
            return null;
        }

        $this->position++;

        if (!$this->valid()) {
            return null;
        }

        $next = $this->current();
        return $next($context, $params, $filters);
    }

    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->items[$this->position];
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->items);
    }
}
