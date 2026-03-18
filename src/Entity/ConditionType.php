<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

/**
 * Supported condition types for ReadinessRule evaluation.
 *
 * Using a backed enum ensures no magic string constants and compile-time safety.
 *
 * --- Basic completeness ---
 * NOT_EMPTY      — value is not null / empty string / empty array / zero
 * FILE_ATTACHED  — an asset or document relation is set and non-null
 *
 * --- String / text length ---
 * MIN_LENGTH     — mb_strlen(value) >= conditionValue
 * MAX_LENGTH     — mb_strlen(value) <= conditionValue
 * WORD_COUNT_MIN — word count (str_word_count) >= conditionValue
 * WORD_COUNT_MAX — word count (str_word_count) <= conditionValue
 *
 * --- Numeric range ---
 * MIN_VALUE      — (float) value >= conditionValue
 * MAX_VALUE      — (float) value <= conditionValue
 * IS_NUMERIC     — value is numeric (int, float, or numeric string)
 *
 * --- Format / pattern ---
 * REGEX          — value matches /pattern/ in conditionValue
 * IS_URL         — value is a syntactically valid URL (filter_var FILTER_VALIDATE_URL)
 * IS_EMAIL       — value is a syntactically valid e-mail (filter_var FILTER_VALIDATE_EMAIL)
 *
 * --- Set membership ---
 * IN_SET         — value is one of the comma-separated items in conditionValue
 * NOT_IN_SET     — value is NOT one of the comma-separated items in conditionValue
 *
 * --- Relations / collections ---
 * RELATION_COUNT_MIN — count(value) >= conditionValue
 * RELATION_COUNT_MAX — count(value) <= conditionValue
 * HAS_RELATION       — at least one related object is set (non-empty array / non-null)
 *
 * --- Asset metadata ---
 * IMAGE_HAS_ALT — image/asset has non-empty alt text (metadata key "alt")
 *
 * --- Boolean ---
 * BOOLEAN_TRUE  — boolean field is strictly true
 *
 * --- Date ---
 * DATE_NOT_PAST — \DateTimeInterface value is >= today (in the future or today)
 */
enum ConditionType: string
{
    // --- Basic completeness ---
    case NOT_EMPTY = 'not_empty';
    case FILE_ATTACHED = 'file_attached';

    // --- String length ---
    case MIN_LENGTH = 'min_length';
    case MAX_LENGTH = 'max_length';
    case WORD_COUNT_MIN = 'word_count_min';
    case WORD_COUNT_MAX = 'word_count_max';

    // --- Numeric ---
    case MIN_VALUE = 'min_value';
    case MAX_VALUE = 'max_value';
    case IS_NUMERIC = 'is_numeric';

    // --- Format / pattern ---
    case REGEX = 'regex';
    case IS_URL = 'is_url';
    case IS_EMAIL = 'is_email';

    // --- Set membership ---
    case IN_SET = 'in_set';
    case NOT_IN_SET = 'not_in_set';

    // --- Relations ---
    case RELATION_COUNT_MIN = 'relation_count_min';
    case RELATION_COUNT_MAX = 'relation_count_max';
    case HAS_RELATION = 'has_relation';

    // --- Asset metadata ---
    case IMAGE_HAS_ALT = 'image_has_alt';

    // --- Boolean ---
    case BOOLEAN_TRUE = 'boolean_true';

    // --- Date ---
    case DATE_NOT_PAST = 'date_not_past';

    /**
     * Returns a human-readable description of the condition type shown in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_EMPTY => 'Not empty',
            self::FILE_ATTACHED => 'File / asset attached',
            self::MIN_LENGTH => 'Minimum length',
            self::MAX_LENGTH => 'Maximum length',
            self::WORD_COUNT_MIN => 'Minimum word count',
            self::WORD_COUNT_MAX => 'Maximum word count',
            self::MIN_VALUE => 'Minimum value',
            self::MAX_VALUE => 'Maximum value',
            self::IS_NUMERIC => 'Is numeric',
            self::REGEX => 'Matches regex',
            self::IS_URL => 'Valid URL',
            self::IS_EMAIL => 'Valid e-mail',
            self::IN_SET => 'Value in set',
            self::NOT_IN_SET => 'Value not in set',
            self::RELATION_COUNT_MIN => 'Min. relation count',
            self::RELATION_COUNT_MAX => 'Max. relation count',
            self::HAS_RELATION => 'Has relation',
            self::IMAGE_HAS_ALT => 'Image has alt text',
            self::BOOLEAN_TRUE => 'Boolean is true',
            self::DATE_NOT_PAST => 'Date not in the past',
        };
    }

    /**
     * Returns true if this condition type requires a conditionValue parameter.
     */
    public function requiresValue(): bool
    {
        return match ($this) {
            self::MIN_LENGTH,
            self::MAX_LENGTH,
            self::WORD_COUNT_MIN,
            self::WORD_COUNT_MAX,
            self::MIN_VALUE,
            self::MAX_VALUE,
            self::REGEX,
            self::IN_SET,
            self::NOT_IN_SET,
            self::RELATION_COUNT_MIN,
            self::RELATION_COUNT_MAX => true,
            default => false,
        };
    }
}
