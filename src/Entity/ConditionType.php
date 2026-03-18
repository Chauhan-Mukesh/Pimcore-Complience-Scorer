<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

/**
 * Supported condition types for ReadinessRule evaluation.
 *
 * Using a backed enum ensures no magic string constants and compile-time safety.
 */
enum ConditionType: string
{
    /** Value is not null, not an empty string, not an empty array, and not zero. */
    case NOT_EMPTY = 'not_empty';

    /** String length >= conditionValue. */
    case MIN_LENGTH = 'min_length';

    /** String length <= conditionValue. */
    case MAX_LENGTH = 'max_length';

    /** Numeric value >= conditionValue. */
    case MIN_VALUE = 'min_value';

    /** Numeric value <= conditionValue. */
    case MAX_VALUE = 'max_value';

    /** Value matches the regex in conditionValue (e.g. "/^[A-Z]{2}/"). */
    case REGEX = 'regex';

    /** Relation or collection count >= conditionValue. */
    case RELATION_COUNT_MIN = 'relation_count_min';

    /** An asset or document relation is attached (not empty). */
    case FILE_ATTACHED = 'file_attached';
}
