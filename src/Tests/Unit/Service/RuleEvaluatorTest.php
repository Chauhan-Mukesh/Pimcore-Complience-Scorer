<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Tests\Unit\Service;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ConditionType;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service\RuleEvaluator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RuleEvaluator covering all ConditionType values and edge cases.
 */
final class RuleEvaluatorTest extends TestCase
{
    private RuleEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new RuleEvaluator();
    }

    // -------------------------------------------------------------------------
    // NOT_EMPTY
    // -------------------------------------------------------------------------

    /**
     * @param mixed $value
     */
    #[DataProvider('notEmptyPassProvider')]
    public function testNotEmptyPasses(mixed $value): void
    {
        $rule = $this->makeRule(ConditionType::NOT_EMPTY);
        self::assertTrue($this->evaluator->evaluate($rule, $value));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function notEmptyPassProvider(): array
    {
        return [
            'non-empty string'     => ['Hello'],
            'string with spaces'   => ['  hi  '],
            'positive int'         => [1],
            'positive float'       => [0.1],
            'non-empty array'      => [['a']],
            'true bool'            => [true],
        ];
    }

    /**
     * @param mixed $value
     */
    #[DataProvider('notEmptyFailProvider')]
    public function testNotEmptyFails(mixed $value): void
    {
        $rule = $this->makeRule(ConditionType::NOT_EMPTY);
        self::assertFalse($this->evaluator->evaluate($rule, $value));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function notEmptyFailProvider(): array
    {
        return [
            'null'          => [null],
            'empty string'  => [''],
            'whitespace'    => ['   '],
            'empty array'   => [[]],
            'zero int'      => [0],
            'zero float'    => [0.0],
        ];
    }

    // -------------------------------------------------------------------------
    // MIN_LENGTH
    // -------------------------------------------------------------------------

    public function testMinLengthPasses(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_LENGTH, '5');
        self::assertTrue($this->evaluator->evaluate($rule, 'Hello'));
        self::assertTrue($this->evaluator->evaluate($rule, 'Hello World'));
    }

    public function testMinLengthFails(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_LENGTH, '5');
        self::assertFalse($this->evaluator->evaluate($rule, 'Hi'));
    }

    public function testMinLengthFailsOnNonString(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_LENGTH, '5');
        self::assertFalse($this->evaluator->evaluate($rule, 12345));
    }

    public function testMinLengthFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_LENGTH, '5');
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // MAX_LENGTH
    // -------------------------------------------------------------------------

    public function testMaxLengthPasses(): void
    {
        $rule = $this->makeRule(ConditionType::MAX_LENGTH, '10');
        self::assertTrue($this->evaluator->evaluate($rule, 'Short'));
    }

    public function testMaxLengthFails(): void
    {
        $rule = $this->makeRule(ConditionType::MAX_LENGTH, '3');
        self::assertFalse($this->evaluator->evaluate($rule, 'TooLong'));
    }

    // -------------------------------------------------------------------------
    // MIN_VALUE
    // -------------------------------------------------------------------------

    public function testMinValuePasses(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_VALUE, '0.1');
        self::assertTrue($this->evaluator->evaluate($rule, 1.5));
        self::assertTrue($this->evaluator->evaluate($rule, 0.1));
    }

    public function testMinValueFails(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_VALUE, '5.0');
        self::assertFalse($this->evaluator->evaluate($rule, 4.99));
    }

    public function testMinValueFailsOnString(): void
    {
        $rule = $this->makeRule(ConditionType::MIN_VALUE, '5');
        self::assertFalse($this->evaluator->evaluate($rule, '10'));
    }

    // -------------------------------------------------------------------------
    // MAX_VALUE
    // -------------------------------------------------------------------------

    public function testMaxValuePasses(): void
    {
        $rule = $this->makeRule(ConditionType::MAX_VALUE, '100');
        self::assertTrue($this->evaluator->evaluate($rule, 99));
        self::assertTrue($this->evaluator->evaluate($rule, 100.0));
    }

    public function testMaxValueFails(): void
    {
        $rule = $this->makeRule(ConditionType::MAX_VALUE, '10');
        self::assertFalse($this->evaluator->evaluate($rule, 10.01));
    }

    // -------------------------------------------------------------------------
    // REGEX
    // -------------------------------------------------------------------------

    public function testRegexPasses(): void
    {
        $rule = $this->makeRule(ConditionType::REGEX, '/^[A-Z]{2}\d{5}$/');
        self::assertTrue($this->evaluator->evaluate($rule, 'AB12345'));
    }

    public function testRegexFails(): void
    {
        $rule = $this->makeRule(ConditionType::REGEX, '/^[A-Z]{2}\d{5}$/');
        self::assertFalse($this->evaluator->evaluate($rule, 'ab12345'));
    }

    public function testRegexFailsOnInvalidPattern(): void
    {
        $rule = $this->makeRule(ConditionType::REGEX, 'NOT_A_REGEX');
        self::assertFalse($this->evaluator->evaluate($rule, 'anything'));
    }

    public function testRegexFailsOnNonString(): void
    {
        $rule = $this->makeRule(ConditionType::REGEX, '/\d+/');
        self::assertFalse($this->evaluator->evaluate($rule, 123));
    }

    // -------------------------------------------------------------------------
    // RELATION_COUNT_MIN
    // -------------------------------------------------------------------------

    public function testRelationCountMinPasses(): void
    {
        $rule = $this->makeRule(ConditionType::RELATION_COUNT_MIN, '2');
        self::assertTrue($this->evaluator->evaluate($rule, ['a', 'b', 'c']));
        self::assertTrue($this->evaluator->evaluate($rule, ['x', 'y']));
    }

    public function testRelationCountMinFails(): void
    {
        $rule = $this->makeRule(ConditionType::RELATION_COUNT_MIN, '3');
        self::assertFalse($this->evaluator->evaluate($rule, ['only_one']));
    }

    public function testRelationCountMinFailsOnNonArray(): void
    {
        $rule = $this->makeRule(ConditionType::RELATION_COUNT_MIN, '1');
        self::assertFalse($this->evaluator->evaluate($rule, 'a_string'));
    }

    // -------------------------------------------------------------------------
    // FILE_ATTACHED
    // -------------------------------------------------------------------------

    public function testFileAttachedPassesOnObjectWithId(): void
    {
        $rule = $this->makeRule(ConditionType::FILE_ATTACHED);

        $asset = new class () {
            public function getId(): int
            {
                return 42;
            }
        };

        self::assertTrue($this->evaluator->evaluate($rule, $asset));
    }

    public function testFileAttachedPassesOnNonEmptyArray(): void
    {
        $rule = $this->makeRule(ConditionType::FILE_ATTACHED);
        self::assertTrue($this->evaluator->evaluate($rule, [new \stdClass()]));
    }

    public function testFileAttachedFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::FILE_ATTACHED);
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    public function testFileAttachedFailsOnEmptyArray(): void
    {
        $rule = $this->makeRule(ConditionType::FILE_ATTACHED);
        self::assertFalse($this->evaluator->evaluate($rule, []));
    }

    // -------------------------------------------------------------------------
    // WORD_COUNT_MIN
    // -------------------------------------------------------------------------

    public function testWordCountMinPasses(): void
    {
        $rule = $this->makeRule(ConditionType::WORD_COUNT_MIN, '5');
        self::assertTrue($this->evaluator->evaluate($rule, 'one two three four five'));
        self::assertTrue($this->evaluator->evaluate($rule, 'one two three four five six'));
    }

    public function testWordCountMinFails(): void
    {
        $rule = $this->makeRule(ConditionType::WORD_COUNT_MIN, '5');
        self::assertFalse($this->evaluator->evaluate($rule, 'only three words'));
    }

    public function testWordCountMinStripsHtml(): void
    {
        $rule = $this->makeRule(ConditionType::WORD_COUNT_MIN, '3');
        self::assertTrue($this->evaluator->evaluate($rule, '<p>one <strong>two</strong> three</p>'));
    }

    public function testWordCountMinFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::WORD_COUNT_MIN, '1');
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // WORD_COUNT_MAX
    // -------------------------------------------------------------------------

    public function testWordCountMaxPasses(): void
    {
        $rule = $this->makeRule(ConditionType::WORD_COUNT_MAX, '5');
        self::assertTrue($this->evaluator->evaluate($rule, 'only two words'));
        self::assertTrue($this->evaluator->evaluate($rule, 'exactly five words here ok'));
    }

    public function testWordCountMaxFails(): void
    {
        $rule = $this->makeRule(ConditionType::WORD_COUNT_MAX, '3');
        self::assertFalse($this->evaluator->evaluate($rule, 'this has more than three words'));
    }

    // -------------------------------------------------------------------------
    // IS_NUMERIC
    // -------------------------------------------------------------------------

    public function testIsNumericPassesOnInt(): void
    {
        $rule = $this->makeRule(ConditionType::IS_NUMERIC);
        self::assertTrue($this->evaluator->evaluate($rule, 42));
    }

    public function testIsNumericPassesOnFloat(): void
    {
        $rule = $this->makeRule(ConditionType::IS_NUMERIC);
        self::assertTrue($this->evaluator->evaluate($rule, 3.14));
    }

    public function testIsNumericPassesOnNumericString(): void
    {
        $rule = $this->makeRule(ConditionType::IS_NUMERIC);
        self::assertTrue($this->evaluator->evaluate($rule, '123.45'));
    }

    public function testIsNumericFailsOnNonNumericString(): void
    {
        $rule = $this->makeRule(ConditionType::IS_NUMERIC);
        self::assertFalse($this->evaluator->evaluate($rule, 'abc'));
    }

    public function testIsNumericFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::IS_NUMERIC);
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // IS_URL
    // -------------------------------------------------------------------------

    public function testIsUrlPasses(): void
    {
        $rule = $this->makeRule(ConditionType::IS_URL);
        self::assertTrue($this->evaluator->evaluate($rule, 'https://example.com'));
        self::assertTrue($this->evaluator->evaluate($rule, 'http://sub.example.org/path?q=1'));
    }

    public function testIsUrlFails(): void
    {
        $rule = $this->makeRule(ConditionType::IS_URL);
        self::assertFalse($this->evaluator->evaluate($rule, 'not-a-url'));
        self::assertFalse($this->evaluator->evaluate($rule, ''));
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // IS_EMAIL
    // -------------------------------------------------------------------------

    public function testIsEmailPasses(): void
    {
        $rule = $this->makeRule(ConditionType::IS_EMAIL);
        self::assertTrue($this->evaluator->evaluate($rule, 'user@example.com'));
        self::assertTrue($this->evaluator->evaluate($rule, 'User+Tag@sub.example.org'));
    }

    public function testIsEmailFails(): void
    {
        $rule = $this->makeRule(ConditionType::IS_EMAIL);
        self::assertFalse($this->evaluator->evaluate($rule, 'not-an-email'));
        self::assertFalse($this->evaluator->evaluate($rule, '@missing-local.com'));
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // IN_SET
    // -------------------------------------------------------------------------

    public function testInSetPasses(): void
    {
        $rule = $this->makeRule(ConditionType::IN_SET, 'en, de, fr');
        self::assertTrue($this->evaluator->evaluate($rule, 'en'));
        self::assertTrue($this->evaluator->evaluate($rule, 'fr'));
    }

    public function testInSetFails(): void
    {
        $rule = $this->makeRule(ConditionType::IN_SET, 'en,de,fr');
        self::assertFalse($this->evaluator->evaluate($rule, 'es'));
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // NOT_IN_SET
    // -------------------------------------------------------------------------

    public function testNotInSetPasses(): void
    {
        $rule = $this->makeRule(ConditionType::NOT_IN_SET, 'draft, archived');
        self::assertTrue($this->evaluator->evaluate($rule, 'published'));
    }

    public function testNotInSetFails(): void
    {
        $rule = $this->makeRule(ConditionType::NOT_IN_SET, 'draft,archived');
        self::assertFalse($this->evaluator->evaluate($rule, 'draft'));
    }

    // -------------------------------------------------------------------------
    // RELATION_COUNT_MAX
    // -------------------------------------------------------------------------

    public function testRelationCountMaxPasses(): void
    {
        $rule = $this->makeRule(ConditionType::RELATION_COUNT_MAX, '3');
        self::assertTrue($this->evaluator->evaluate($rule, ['a', 'b']));
        self::assertTrue($this->evaluator->evaluate($rule, []));
    }

    public function testRelationCountMaxFails(): void
    {
        $rule = $this->makeRule(ConditionType::RELATION_COUNT_MAX, '2');
        self::assertFalse($this->evaluator->evaluate($rule, ['a', 'b', 'c']));
    }

    // -------------------------------------------------------------------------
    // HAS_RELATION
    // -------------------------------------------------------------------------

    public function testHasRelationPassesOnNonEmptyArray(): void
    {
        $rule = $this->makeRule(ConditionType::HAS_RELATION);
        self::assertTrue($this->evaluator->evaluate($rule, [new \stdClass()]));
    }

    public function testHasRelationFailsOnEmptyArray(): void
    {
        $rule = $this->makeRule(ConditionType::HAS_RELATION);
        self::assertFalse($this->evaluator->evaluate($rule, []));
    }

    public function testHasRelationFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::HAS_RELATION);
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // IMAGE_HAS_ALT
    // -------------------------------------------------------------------------

    public function testImageHasAltPassesWithGetAlt(): void
    {
        $rule = $this->makeRule(ConditionType::IMAGE_HAS_ALT);

        $image = new class () {
            public function getAlt(): string
            {
                return 'A product photo';
            }
        };

        self::assertTrue($this->evaluator->evaluate($rule, $image));
    }

    public function testImageHasAltFailsWithEmptyAlt(): void
    {
        $rule = $this->makeRule(ConditionType::IMAGE_HAS_ALT);

        $image = new class () {
            public function getAlt(): string
            {
                return '';
            }
        };

        self::assertFalse($this->evaluator->evaluate($rule, $image));
    }

    public function testImageHasAltFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::IMAGE_HAS_ALT);
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // BOOLEAN_TRUE
    // -------------------------------------------------------------------------

    public function testBooleanTruePasses(): void
    {
        $rule = $this->makeRule(ConditionType::BOOLEAN_TRUE);
        self::assertTrue($this->evaluator->evaluate($rule, true));
    }

    public function testBooleanTrueFailsOnFalse(): void
    {
        $rule = $this->makeRule(ConditionType::BOOLEAN_TRUE);
        self::assertFalse($this->evaluator->evaluate($rule, false));
    }

    public function testBooleanTrueFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::BOOLEAN_TRUE);
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    public function testBooleanTrueFailsOnTruthyInt(): void
    {
        $rule = $this->makeRule(ConditionType::BOOLEAN_TRUE);
        // Must be strictly true, not just truthy.
        self::assertFalse($this->evaluator->evaluate($rule, 1));
    }

    // -------------------------------------------------------------------------
    // DATE_NOT_PAST
    // -------------------------------------------------------------------------

    public function testDateNotPastPassesWithFutureDate(): void
    {
        $rule = $this->makeRule(ConditionType::DATE_NOT_PAST);
        $future = new \DateTimeImmutable('+1 year');
        self::assertTrue($this->evaluator->evaluate($rule, $future));
    }

    public function testDateNotPastFailsWithPastDate(): void
    {
        $rule = $this->makeRule(ConditionType::DATE_NOT_PAST);
        $past = new \DateTimeImmutable('-1 day');
        self::assertFalse($this->evaluator->evaluate($rule, $past));
    }

    public function testDateNotPastPassesWithTodayDateString(): void
    {
        $rule = $this->makeRule(ConditionType::DATE_NOT_PAST);
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        self::assertTrue($this->evaluator->evaluate($rule, $today));
    }

    public function testDateNotPastFailsWithPastDateString(): void
    {
        $rule = $this->makeRule(ConditionType::DATE_NOT_PAST);
        self::assertFalse($this->evaluator->evaluate($rule, '2000-01-01'));
    }

    public function testDateNotPastFailsOnNull(): void
    {
        $rule = $this->makeRule(ConditionType::DATE_NOT_PAST);
        self::assertFalse($this->evaluator->evaluate($rule, null));
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeRule(ConditionType $type, ?string $conditionValue = null): ReadinessRule
    {
        $rule = new ReadinessRule('someField', $type, 10.0, 'Some Field Label');
        $rule->setConditionValue($conditionValue);

        return $rule;
    }
}
