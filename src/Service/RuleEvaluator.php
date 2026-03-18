<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ConditionType;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;

/**
 * Evaluates a single ReadinessRule against a raw field value.
 *
 * Returns true when the value SATISFIES the rule (the field is "ready"),
 * and false when the value FAILS the rule (the field is "missing" or incomplete).
 *
 * Design notes:
 *   - No deprecated PHP functions (no count() on non-countable, no is_null(), no intval()).
 *   - All type checks use strict PHP 8.2 idioms.
 *   - Cognitive complexity per method is kept below 10.
 */
final class RuleEvaluator
{
    /**
     * Evaluates the rule against the given raw value.
     *
     * @param mixed $value The raw value retrieved from the DataObject field.
     */
    public function evaluate(ReadinessRule $rule, mixed $value): bool
    {
        return match ($rule->getConditionType()) {
            ConditionType::NOT_EMPTY         => $this->evaluateNotEmpty($value),
            ConditionType::MIN_LENGTH        => $this->evaluateMinLength($value, $rule->getConditionValue()),
            ConditionType::MAX_LENGTH        => $this->evaluateMaxLength($value, $rule->getConditionValue()),
            ConditionType::MIN_VALUE         => $this->evaluateMinValue($value, $rule->getConditionValue()),
            ConditionType::MAX_VALUE         => $this->evaluateMaxValue($value, $rule->getConditionValue()),
            ConditionType::REGEX             => $this->evaluateRegex($value, $rule->getConditionValue()),
            ConditionType::RELATION_COUNT_MIN => $this->evaluateRelationCountMin($value, $rule->getConditionValue()),
            ConditionType::FILE_ATTACHED     => $this->evaluateFileAttached($value),
        };
    }

    private function evaluateNotEmpty(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        if ($value instanceof \Countable) {
            return count($value) > 0;
        }

        if (is_int($value) || is_float($value)) {
            return $value !== 0 && $value !== 0.0;
        }

        return (bool) $value;
    }

    private function evaluateMinLength(mixed $value, ?string $conditionValue): bool
    {
        if (!is_string($value) || $conditionValue === null) {
            return false;
        }

        return mb_strlen($value) >= (int) $conditionValue;
    }

    private function evaluateMaxLength(mixed $value, ?string $conditionValue): bool
    {
        if (!is_string($value) || $conditionValue === null) {
            return false;
        }

        return mb_strlen($value) <= (int) $conditionValue;
    }

    private function evaluateMinValue(mixed $value, ?string $conditionValue): bool
    {
        if ((!is_int($value) && !is_float($value)) || $conditionValue === null) {
            return false;
        }

        return $value >= (float) $conditionValue;
    }

    private function evaluateMaxValue(mixed $value, ?string $conditionValue): bool
    {
        if ((!is_int($value) && !is_float($value)) || $conditionValue === null) {
            return false;
        }

        return $value <= (float) $conditionValue;
    }

    private function evaluateRegex(mixed $value, ?string $conditionValue): bool
    {
        if (!is_string($value) || $conditionValue === null) {
            return false;
        }

        // Suppress warning — preg_match returns false on invalid pattern.
        $result = @preg_match($conditionValue, $value);

        return $result === 1;
    }

    private function evaluateRelationCountMin(mixed $value, ?string $conditionValue): bool
    {
        if ($conditionValue === null) {
            return false;
        }

        $min = (int) $conditionValue;

        if (is_array($value)) {
            return count($value) >= $min;
        }

        if ($value instanceof \Countable) {
            return count($value) >= $min;
        }

        return false;
    }

    private function evaluateFileAttached(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        // A single asset/document.
        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId() !== null;
        }

        // An array of assets/documents.
        if (is_array($value)) {
            return count($value) > 0;
        }

        return false;
    }
}
