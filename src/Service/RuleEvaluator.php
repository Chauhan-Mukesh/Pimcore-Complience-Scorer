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
 * Supports all ConditionType cases including the advanced quality-check types:
 *   - Format checks: IS_URL, IS_EMAIL, REGEX
 *   - Set membership: IN_SET, NOT_IN_SET
 *   - Extended relations: HAS_RELATION, RELATION_COUNT_MAX
 *   - Word count: WORD_COUNT_MIN, WORD_COUNT_MAX
 *   - Numeric: IS_NUMERIC
 *   - Asset metadata: IMAGE_HAS_ALT
 *   - Boolean: BOOLEAN_TRUE
 *   - Date: DATE_NOT_PAST
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
            // --- Basic completeness ---
            ConditionType::NOT_EMPTY         => $this->evaluateNotEmpty($value),
            ConditionType::FILE_ATTACHED     => $this->evaluateFileAttached($value),

            // --- String length ---
            ConditionType::MIN_LENGTH        => $this->evaluateMinLength($value, $rule->getConditionValue()),
            ConditionType::MAX_LENGTH        => $this->evaluateMaxLength($value, $rule->getConditionValue()),
            ConditionType::WORD_COUNT_MIN    => $this->evaluateWordCountMin($value, $rule->getConditionValue()),
            ConditionType::WORD_COUNT_MAX    => $this->evaluateWordCountMax($value, $rule->getConditionValue()),

            // --- Numeric ---
            ConditionType::MIN_VALUE         => $this->evaluateMinValue($value, $rule->getConditionValue()),
            ConditionType::MAX_VALUE         => $this->evaluateMaxValue($value, $rule->getConditionValue()),
            ConditionType::IS_NUMERIC        => $this->evaluateIsNumeric($value),

            // --- Format / pattern ---
            ConditionType::REGEX             => $this->evaluateRegex($value, $rule->getConditionValue()),
            ConditionType::IS_URL            => $this->evaluateIsUrl($value),
            ConditionType::IS_EMAIL          => $this->evaluateIsEmail($value),

            // --- Set membership ---
            ConditionType::IN_SET            => $this->evaluateInSet($value, $rule->getConditionValue()),
            ConditionType::NOT_IN_SET        => $this->evaluateNotInSet($value, $rule->getConditionValue()),

            // --- Relations ---
            ConditionType::RELATION_COUNT_MIN => $this->evaluateRelationCountMin($value, $rule->getConditionValue()),
            ConditionType::RELATION_COUNT_MAX => $this->evaluateRelationCountMax($value, $rule->getConditionValue()),
            ConditionType::HAS_RELATION       => $this->evaluateHasRelation($value),

            // --- Asset metadata ---
            ConditionType::IMAGE_HAS_ALT     => $this->evaluateImageHasAlt($value),

            // --- Boolean ---
            ConditionType::BOOLEAN_TRUE      => $this->evaluateBooleanTrue($value),

            // --- Date ---
            ConditionType::DATE_NOT_PAST     => $this->evaluateDateNotPast($value),
        };
    }

    // -------------------------------------------------------------------------
    // Basic completeness
    // -------------------------------------------------------------------------

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

    private function evaluateFileAttached(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId() !== null;
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // String length
    // -------------------------------------------------------------------------

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

    private function evaluateWordCountMin(mixed $value, ?string $conditionValue): bool
    {
        if (!is_string($value) || $conditionValue === null) {
            return false;
        }

        // Strip HTML tags before counting (handles WYSIWYG / richtext fields).
        $plain = strip_tags($value);

        return str_word_count($plain) >= (int) $conditionValue;
    }

    private function evaluateWordCountMax(mixed $value, ?string $conditionValue): bool
    {
        if (!is_string($value) || $conditionValue === null) {
            return false;
        }

        $plain = strip_tags($value);

        return str_word_count($plain) <= (int) $conditionValue;
    }

    // -------------------------------------------------------------------------
    // Numeric
    // -------------------------------------------------------------------------

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

    private function evaluateIsNumeric(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return is_numeric($value);
    }

    // -------------------------------------------------------------------------
    // Format / pattern
    // -------------------------------------------------------------------------

    private function evaluateRegex(mixed $value, ?string $conditionValue): bool
    {
        if (!is_string($value) || $conditionValue === null) {
            return false;
        }

        $result = @preg_match($conditionValue, $value);

        return $result === 1;
    }

    private function evaluateIsUrl(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        return filter_var(trim($value), FILTER_VALIDATE_URL) !== false;
    }

    private function evaluateIsEmail(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        return filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
    }

    // -------------------------------------------------------------------------
    // Set membership
    // -------------------------------------------------------------------------

    private function evaluateInSet(mixed $value, ?string $conditionValue): bool
    {
        if ($conditionValue === null || $value === null) {
            return false;
        }

        $allowed = array_map('trim', explode(',', $conditionValue));

        return in_array((string) $value, $allowed, strict: true);
    }

    private function evaluateNotInSet(mixed $value, ?string $conditionValue): bool
    {
        if ($conditionValue === null || $value === null) {
            return false;
        }

        $forbidden = array_map('trim', explode(',', $conditionValue));

        return !in_array((string) $value, $forbidden, strict: true);
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

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

    private function evaluateRelationCountMax(mixed $value, ?string $conditionValue): bool
    {
        if ($conditionValue === null) {
            return false;
        }

        $max = (int) $conditionValue;

        if (is_array($value)) {
            return count($value) <= $max;
        }

        if ($value instanceof \Countable) {
            return count($value) <= $max;
        }

        return false;
    }

    private function evaluateHasRelation(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        if ($value instanceof \Countable) {
            return count($value) > 0;
        }

        // Single relation object.
        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId() !== null;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Asset metadata
    // -------------------------------------------------------------------------

    /**
     * Checks whether an asset/image has non-empty alt text.
     *
     * Pimcore stores asset metadata as key-value pairs accessible via getMetadata().
     * The method tries getMetadata('alt') → getData() and also checks a plain
     * getAlt() getter for custom field types.
     */
    private function evaluateImageHasAlt(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        // Pimcore Asset — try getMetadata('alt')
        if (is_object($value) && method_exists($value, 'getMetadata')) {
            $meta = $value->getMetadata('alt');
            if ($meta !== null) {
                $data = is_object($meta) && method_exists($meta, 'getData') ? $meta->getData() : $meta;

                return is_string($data) && trim($data) !== '';
            }
        }

        // Fallback: plain getAlt() on image hotspot / image advanced fields
        if (is_object($value) && method_exists($value, 'getAlt')) {
            $alt = $value->getAlt();

            return is_string($alt) && trim($alt) !== '';
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Boolean
    // -------------------------------------------------------------------------

    private function evaluateBooleanTrue(mixed $value): bool
    {
        return $value === true;
    }

    // -------------------------------------------------------------------------
    // Date
    // -------------------------------------------------------------------------

    private function evaluateDateNotPast(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value >= new \DateTimeImmutable('today');
        }

        // Pimcore date fields may return a Carbon instance or a timestamp integer.
        if (is_int($value)) {
            return $value >= strtotime('today');
        }

        // String date — attempt to parse it.
        if (is_string($value) && trim($value) !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value)
                ?: \DateTimeImmutable::createFromFormat('d.m.Y', $value)
                ?: false;

            if ($parsed !== false) {
                return $parsed >= new \DateTimeImmutable('today');
            }
        }

        return false;
    }
}

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
