<?php

declare(strict_types=1);

namespace herbie;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @see https://stackoverflow.com/a/17414042/6161354
 * @see https://daylerees.com/code-happy-fluent-query-builder/
 */
final class QueryBuilder implements IteratorAggregate
{
    private const WHERE_CLAUSE_OPERATORS = ['AND', 'OR'];
    private const OPERATORS = [
        "!=" => 'matchNotEqual',
        ">=" => 'matchGreaterThanEqual',
        "<=" => 'matchLessThanEqual',
        "*=" => 'matchContains',
        "^=" => 'matchStarts',
        "~=" => 'matchContainsWords',
        "$=" => 'matchEnds',
        "&" => 'matchBitwiseAnd',
        ">" => 'matchGreaterThan',
        "<" => 'matchLessThan',
        "=" => 'matchEqual',
    ];
    private array $where;
    private int $limit;
    /** @var callable|string $order */
    private $order;
    private array $data;
    private array $processed;

    public function __construct()
    {
        $this->where = [];
        $this->limit = 0;
        $this->order = '';
        $this->data = [];
        $this->processed = [];
    }

    public function from(iterable $iterator): self
    {
        if ($iterator instanceof Traversable) {
            $this->data = iterator_to_array($iterator);
        } else {
            $this->data = (array)$iterator;
        }
        return $this;
    }

    /**
     * @param array|string ...$conditions
     */
    public function where(...$conditions): self
    {
        if (empty($conditions)) {
            throw new \InvalidArgumentException('Empty where conditions');
        }
        foreach ($conditions as $condition) {
            if (is_string($condition)) {
                $parsedCondition = $this->parseCondition($condition);
                $this->where[] = array_merge(['AND'], [$parsedCondition]);
                continue;
            }
            if (is_array($condition)) {
                if (isset($condition[0]) && in_array(strtoupper($condition[0]), self::WHERE_CLAUSE_OPERATORS)) {
                    $this->where[] = $this->parseConditionsInOperatorFormat($condition);
                    continue;
                } elseif (array_is_assoc($condition)) {
                    $this->where[] = $this->parseConditionsInHashFormat($condition);
                    continue;
                }
            }
            throw new \InvalidArgumentException('Unsupported where conditions');
        }
        return $this;
    }

    private function parseCondition(string $condition): array
    {
        foreach (self::OPERATORS as $syntax => $name) {
            $position = stripos($condition, $syntax);
            if ($position !== false) {
                $syntaxLength = strlen($syntax);
                return [$name, substr($condition, 0, $position), substr($condition, $position + $syntaxLength)];
            }
        }
        throw new \InvalidArgumentException('Unsupported operator');
    }

    private function parseConditionsInOperatorFormat(array $conditions): array
    {
        if (!isset($conditions[0]) || !in_array(strtoupper($conditions[0]), self::WHERE_CLAUSE_OPERATORS)) {
            throw new \InvalidArgumentException('Missing where clause operator');
        }
        $whereClauseOperator = [strtoupper(array_shift($conditions))];
        $items = [];
        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                $items[] = $this->parseConditionsInOperatorFormat($condition);
            } else {
                $items[] = $this->parseCondition($condition);
            }
        }
        return array_merge($whereClauseOperator, $items);
    }

    private function parseConditionsInHashFormat(array $conditions): array
    {
        $items = [];
        foreach ($conditions as $key => $value) {
            if (is_scalar($value)) {
                $items[] = ['match' . ucfirst(gettype($value)), $key, $value];
            }
        }
        return array_merge(['AND'], $items);
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function order(callable|string $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function all(): iterable
    {
        $this->processData();
        return $this->processed;
    }

    /**
     * @return null|mixed
     */
    public function one()
    {
        $this->processData();
        $item = reset($this->data);
        if ($item === false) {
            return null;
        }
        return $item;
    }

    public function getIterator(): Traversable
    {
        $this->processData();
        return new ArrayIterator($this->processed);
    }

    private function processData(): void
    {
        $i = 0;
        $this->sort();
        foreach ($this->data as $item) {
            $status = $this->processItem($item, array_merge(['AND'], $this->where));
            if ($status === true) {
                $this->processed[] = $item;
                $i++;
                if (($this->limit > 0) && ($i >= $this->limit)) {
                    break;
                }
            }
        }
    }

    private function processItem(ArrayAccess|array $item, array $conditions): bool
    {
        $whereClauseOperator = array_shift($conditions);

        if (empty($conditions)) {
            return true;
        }

        $status = [];
        foreach ($conditions as $condition) {
            if (isset($condition[0]) && in_array(strtoupper($condition[0]), self::WHERE_CLAUSE_OPERATORS)) {
                $status[] = $this->processItem($item, $condition);
            } else {
                [$operator, $field, $value] = $condition;
                if (!isset($item[$field])) {
                    $status[] = false;
                } else {
                    /** @var callable $callable */
                    $callable = [$this, $operator];
                    $status[] = call_user_func_array($callable, [$item[$field], $value]);
                }
            }
        }

        if ($whereClauseOperator === 'OR') {
            return in_array(true, $status, true);
        }

        $uniqueStatus = array_unique($status);
        $uniqueStatusCount = count($uniqueStatus);
        return $uniqueStatusCount === 1 && in_array(true, $uniqueStatus, true);
    }

    private function sort(): bool
    {
        if (is_callable($this->order)) {
            return uasort($this->data, $this->order);
        }

        if (trim($this->order, '-+') === '') {
            return false;
        }

        $field = '';
        if (!empty($this->order)) {
            $field = trim($this->order, '+');
        }

        $direction = 'asc';
        if (str_starts_with($field, '-')) {
            $field = substr($field, 1);
            $direction = 'desc';
        }

        return uasort($this->data, function ($value1, $value2) use ($field, $direction) {
            if (!isset($value1[$field]) || !isset($value2[$field])) {
                return 0;
            }
            if ($value1[$field] === $value2[$field]) {
                return 0;
            }
            if ($direction === 'asc') {
                return ($value1[$field] < $value2[$field]) ? -1 : 1;
            } else {
                return ($value2[$field] < $value1[$field]) ? -1 : 1;
            }
        });
    }

    protected function matchString(string $value1, string $value2): bool
    {
        return $this->matchEqual($value1, $value2);
    }

    protected function matchBoolean(bool $value1, bool $value2): bool
    {
        return $value1 === $value2;
    }

    protected function matchInteger(int $value1, int $value2): bool
    {
        return $value1 === $value2;
    }

    protected function matchFloat(float $value1, float $value2): bool
    {
        return $value1 === $value2;
    }

    protected function matchEqual(string $value1, string $value2): bool
    {
        return $value1 === $value2;
    }

    protected function matchNotEqual(string $value1, string $value2): bool
    {
        return $value1 !== $value2;
    }

    protected function matchGreaterThan(string $value1, string $value2): bool
    {
        return $value1 > $value2;
    }

    protected function matchLessThan(string $value1, string $value2): bool
    {
        return $value1 < $value2;
    }

    protected function matchGreaterThanEqual(string $value1, string $value2): bool
    {
        return $value1 >= $value2;
    }

    protected function matchLessThanEqual(string $value1, string $value2): bool
    {
        return $value1 <= $value2;
    }

    protected function matchBitwiseAnd(string $value1, string $value2): bool
    {
        return ((int)$value1 & (int)$value2) > 0;
    }

    protected function matchContains(string $value1, string $value2): bool
    {
        return stripos($value1, $value2) !== false;
    }

    protected function matchContainsWords(string $value1, string $value2): bool
    {
        $words = preg_split('/[-\s]/', $value2, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($words) || count($words) === 0) {
            return false;
        }
        foreach ($words as $word) {
            if (!preg_match('/\b' . preg_quote($word) . '\b/i', $value1)) {
                return false;
            }
        }
        return true;
    }

    protected function matchStarts(string $value1, string $value2): bool
    {
        return stripos(trim($value1), $value2) === 0;
    }

    protected function matchEnds(string $value1, string $value2): bool
    {
        $value2 = trim($value2);
        $value1 = substr($value1, -1 * strlen($value2));
        return strcasecmp($value1, $value2) === 0;
    }
}
