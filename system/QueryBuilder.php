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
    // ordered by key string length
    private const OPERATORS = [
        "!=" => 'matchNotEqual',
        ">=" => 'matchGreaterThanEqual',
        "<=" => 'matchLessThanEqual',
        "*=" => 'matchContains',
        "^=" => 'matchStarts',
        "~=" => 'matchContainsWords',
        "$=" => 'matchEnds',
        "=" => 'matchEqual',
        ">" => 'matchGreaterThan',
        "<" => 'matchLessThan',
        "&" => 'matchBitwiseAnd',
    ];
    private array $where;
    private int $limit;
    private int $offset;
    /** @var callable|string $order */
    private $order;
    private array $data;
    private array $processed;

    public function __construct()
    {
        $this->where = [];
        $this->limit = 0;
        $this->offset = 0;
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
                $value1 = substr($condition, 0, $position);
                $value2 = substr($condition, $position + $syntaxLength);
                if (str_contains($value1, '|')) {
                    $values1 = str_explode_filtered($value1, '|');
                    $conditions = ['OR'];
                    foreach ($values1 as $value1) {
                        $conditions[] = [$name, $value1, $value2];
                    }
                    return $conditions;
                }
                if (str_contains($value2, '|')) {
                    $values2 = str_explode_filtered($value2, '|');
                    $conditions = ['OR'];
                    foreach ($values2 as $value2) {
                        $conditions[] = [$name, $value1, $value2];
                    }
                    return $conditions;
                }
                return [$name, $value1, $value2];
            }
        }
        throw new \InvalidArgumentException('Unsupported operator');
    }

    private function convertType(mixed $value1, mixed $value2): mixed
    {
        if (is_bool($value1) && is_string($value2)) {
            $lowered = strtolower($value2);
            if ($lowered === 'true') {
                return true;
            }
            if ($lowered === 'false') {
                return false;
            }
            return $value2;
        }
        return $value2;
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
                $type = \herbie\gettype($value);
                $items[] = ['match' . ucfirst($type), $key, $value];
            }
        }
        return array_merge(['AND'], $items);
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
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

    public function one(): array|object|null
    {
        $this->limit = 1;
        $this->processData();
        $item = reset($this->processed);
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
        $j = 0;
        $this->sort();
        foreach ($this->data as $item) {
            if (($this->offset > 0) && ($j < ($this->offset))) {
                $j++;
                continue;
            }
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
                [$operator, $field, $value2] = $condition;
                if (!isset($item[$field])) {
                    $status[] = false;
                } else {
                    /** @var callable $callable */
                    $callable = [$this, $operator];
                    $value1 = $item[$field];
                    if (is_array($value1)) {
                        $arrStatus = [];
                        foreach ($value1 as $arrValue1) {
                            $value2 = $this->convertType($arrValue1, $value2);
                            $arrStatus[] = call_user_func_array($callable, [$arrValue1, $value2]);
                        }
                        $status[] = in_array(true, $arrStatus);
                    } else {
                        $value2 = $this->convertType($value1, $value2);
                        $status[] = call_user_func_array($callable, [$value1, $value2]);
                    }
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
        return $value1 === $value2;
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

    protected function matchEqual(string|float|int|bool $value1, string|float|int|bool $value2): bool
    {
        return $value1 === $value2;
    }

    protected function matchNotEqual(string|float|int|bool $value1, string|float|int|bool $value2): bool
    {
        return $value1 !== $value2;
    }

    protected function matchGreaterThan(string|float|int|bool $value1, string|float|int|bool $value2): bool
    {
        return $value1 > $value2;
    }

    protected function matchLessThan(string|float|int|bool $value1, string|float|int|bool $value2): bool
    {
        return $value1 < $value2;
    }

    protected function matchGreaterThanEqual(string|float|int|bool $value1, string|float|int|bool $value2): bool
    {
        return $value1 >= $value2;
    }

    protected function matchLessThanEqual(string|float|int|bool $value1, string|float|int|bool $value2): bool
    {
        return $value1 <= $value2;
    }

    protected function matchBitwiseAnd(int $value1, int $value2): bool
    {
        return ($value1 & $value2) > 0;
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
