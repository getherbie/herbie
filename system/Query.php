<?php

namespace herbie;

/**
 * @see https://stackoverflow.com/a/17414042/6161354
 * @see https://daylerees.com/code-happy-fluent-query-builder/
 */
final class Query
{
    private const DEBUG = false;
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
    private array $select;
    private array $where;
    private int $limit;
    private string $order;
    private iterable $data;
    private array $processed;

    public function __construct()
    {
        $this->select = [];
        $this->where = [];
        $this->limit = 0;
        $this->order = '';
        $this->data = [];
        $this->processed = [];
    }

    public function from(iterable $data): self
    {
        $this->data = iterator_to_array($data);
        return $this;
    }

    public function select(array $select): self
    {
        $this->select = $select;
        return $this;
    }

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

    public function order(string $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function all(): iterable
    {
        $this->processData();
        return $this->processed;
    }

    private function processData(): void
    {
        $where = array_merge(['AND'], $this->where); // the outer where clause condition
        foreach ($this->data as $item) {
            $status = $this->processItem($item, $where);
            if ($status === true) {
                $this->processed[] = $item;
            }
        }
    }

    private function processItem($item, array $conditions): bool
    {
        $whereClauseOperator = array_shift($conditions);

        $debug = [];
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
                    $status[] = $s = call_user_func_array($callable, [$item[$field], $value]);
                    $debug[] = $operator . ': ' . $item[$field] . ' - ' . $value . ' => ' . (int)$s;
                }
            }
        }

        if (self::DEBUG) {
            echo $whereClauseOperator . '<br>';
            foreach ($debug as $d) {
                echo $d . '<br>';
            }
        }

        if ($whereClauseOperator === 'OR') {
            if (self::DEBUG) {
                echo (int)in_array(true, $status, true) . '<br>';
                echo '---<br>';
            }
            return in_array(true, $status, true);
        }

        $uniqueStatus = array_unique($status);
        $uniqueStatusCount = count($uniqueStatus);
        if (self::DEBUG) {
            echo (int)($uniqueStatusCount === 1 && in_array(true, $uniqueStatus, true)) . '<br>';
            echo '---<br>';
        }
        return $uniqueStatusCount === 1 && in_array(true, $uniqueStatus, true);
    }

    /**
     * @return mixed
     */
    public function one()
    {
        $this->processData();
        return reset($this->data);
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
