<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Core Validator (Form & API Data Validation)
 */

namespace Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $this->data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $params);
                }
            }
        }

        return empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }

        return null;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // Rules
    private function validateRequired(string $field, mixed $value, array $params): void
    {
        if ($value === null || trim((string)$value) === '') {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " is required.");
        }
    }

    private function validateEmail(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Please enter a valid email address.");
        }
    }

    private function validateMin(string $field, mixed $value, array $params): void
    {
        $min = (int)($params[0] ?? 0);
        if (is_numeric($value)) {
            if ($value < $min) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min}.");
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) < $min) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.");
            }
        }
    }

    private function validateMax(string $field, mixed $value, array $params): void
    {
        $max = (int)($params[0] ?? 0);
        if (is_numeric($value)) {
            if ($value > $max) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max}.");
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) > $max) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.");
            }
        }
    }

    private function validateNumeric(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " must be a numeric value.");
        }
    }

    private function validateIn(string $field, mixed $value, array $params): void
    {
        if (!in_array($value, $params)) {
            $this->addError($field, "Selected " . str_replace('_', ' ', $field) . " is invalid.");
        }
    }

    private function validateMatches(string $field, mixed $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $otherVal = $this->data[$otherField] ?? null;
        if ($value !== $otherVal) {
            $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " does not match {$otherField}.");
        }
    }

    private function validateUrl(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "Please enter a valid URL.");
        }
    }

    private function validateUnique(string $field, mixed $value, array $params): void
    {
        $table = $params[0] ?? '';
        $column = $params[1] ?? $field;
        $ignoreId = $params[2] ?? null;

        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :val";
        $binds = [':val' => $value];

        if ($ignoreId) {
            $sql .= " AND `id` != :ignore_id";
            $binds[':ignore_id'] = $ignoreId;
        }

        try {
            $count = (int)Database::fetchColumn($sql, $binds);
            if ($count > 0) {
                $this->addError($field, ucfirst(str_replace('_', ' ', $field)) . " is already taken.");
            }
        } catch (\Exception $e) {
            // Table may not exist yet during install
        }
    }
}
