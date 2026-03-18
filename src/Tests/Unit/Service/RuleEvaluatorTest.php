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
    // Helper
    // -------------------------------------------------------------------------

    private function makeRule(ConditionType $type, ?string $conditionValue = null): ReadinessRule
    {
        $rule = new ReadinessRule('someField', $type, 10.0, 'Some Field Label');
        $rule->setConditionValue($conditionValue);

        return $rule;
    }
}
