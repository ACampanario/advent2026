<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use Authorization\IdentityInterface;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use InvalidArgumentException;

/**
 * Shared input validation and row cap for the agent tools.
 */
abstract class AbstractTool implements AgentToolInterface
{
    /**
     * @param int $maxRows Maximum rows returned per call (AGENT_MAX_ROWS).
     */
    public function __construct(protected int $maxRows)
    {
    }

    /**
     * @inheritDoc
     */
    final public function execute(array $input, IdentityInterface $identity): ToolResult
    {
        try {
            return $this->run($input, $identity);
        } catch (InvalidToolInputException $e) {
            return ToolResult::error($e->getMessage());
        }
    }

    /**
     * Tool body. Throw InvalidToolInputException for invalid input.
     *
     * @param array<string, mixed> $input Input sent by the model.
     * @param \Authorization\IdentityInterface $identity Logged-in user.
     * @return \App\Service\Ai\Tool\ToolResult
     */
    abstract protected function run(array $input, IdentityInterface $identity): ToolResult;

    /**
     * Runs a scoped query with the row cap and returns the rows plus `truncated` / `total`.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface|array<string, mixed>> $query Scoped query.
     * @param string $key Key of the row list in the payload.
     * @return \App\Service\Ai\Tool\ToolResult
     */
    protected function cappedResult(SelectQuery $query, string $key): ToolResult
    {
        $total = $query->count();
        $rows = [];
        foreach ($query->limit($this->maxRows)->all() as $row) {
            $rows[] = $row instanceof EntityInterface ? $row->toArray() : $row;
        }
        $truncated = $total > count($rows);

        $data = [$key => $rows];
        if ($truncated) {
            $data['truncated'] = true;
            $data['total'] = $total;
        }

        return ToolResult::ok($data, count($rows), $truncated);
    }

    /**
     * Reads a positive integer.
     *
     * @param array<string, mixed> $input Input.
     * @param string $key Field name.
     * @param bool $required Whether the field is required.
     * @return int|null
     */
    protected function positiveInt(array $input, string $key, bool $required = false): ?int
    {
        $value = $input[$key] ?? null;
        if ($value === null) {
            return $required ? throw new InvalidToolInputException(sprintf('"%s" is required.', $key)) : null;
        }
        if (!is_int($value) || $value < 1) {
            throw new InvalidToolInputException(sprintf('"%s" must be a positive integer.', $key));
        }

        return $value;
    }

    /**
     * Reads an optional non-empty string.
     *
     * @param array<string, mixed> $input Input.
     * @param string $key Field name.
     * @param int $maxLength Maximum length.
     * @return string|null
     */
    protected function optionalString(array $input, string $key, int $maxLength = 100): ?string
    {
        $value = $input[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $maxLength) {
            throw new InvalidToolInputException(
                sprintf('"%s" must be a non-empty string of at most %d characters.', $key, $maxLength),
            );
        }

        return trim($value);
    }

    /**
     * Reads a value that must be one of a fixed list.
     *
     * @param array<string, mixed> $input Input.
     * @param string $key Field name.
     * @param array<string> $allowed Allowed values.
     * @param bool $required Whether the field is required.
     * @return string|null
     */
    protected function enum(array $input, string $key, array $allowed, bool $required = false): ?string
    {
        $value = $input[$key] ?? null;
        if ($value === null) {
            return $required ? throw new InvalidToolInputException(sprintf('"%s" is required.', $key)) : null;
        }
        if (!in_array($value, $allowed, true)) {
            throw new InvalidToolInputException(sprintf('"%s" must be one of: %s.', $key, implode(', ', $allowed)));
        }

        return $value;
    }

    /**
     * Reads a date in YYYY-MM-DD format.
     *
     * @param array<string, mixed> $input Input.
     * @param string $key Field name.
     * @param bool $required Whether the field is required.
     * @return \Cake\I18n\Date|null
     */
    protected function date(array $input, string $key, bool $required = false): ?Date
    {
        $value = $input[$key] ?? null;
        if ($value === null) {
            return $required ? throw new InvalidToolInputException(sprintf('"%s" is required.', $key)) : null;
        }
        try {
            $date = is_string($value) ? Date::createFromFormat('Y-m-d', $value) : null;
        } catch (InvalidArgumentException) {
            $date = null;
        }
        if ($date === null || $date->format('Y-m-d') !== $value) {
            throw new InvalidToolInputException(sprintf('"%s" must be a date in YYYY-MM-DD format.', $key));
        }

        return $date;
    }

    /**
     * Checks that a date range is not reversed.
     *
     * @param \Cake\I18n\Date|null $from Start date.
     * @param \Cake\I18n\Date|null $to End date.
     * @return void
     */
    protected function assertRange(?Date $from, ?Date $to): void
    {
        if ($from !== null && $to !== null && $from->greaterThan($to)) {
            throw new InvalidToolInputException('"date_from" must be before or equal to "date_to".');
        }
    }
}
