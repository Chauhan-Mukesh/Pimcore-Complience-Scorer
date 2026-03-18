<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Tests\Unit\Service;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ConditionType;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ObjectScore;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessProfile;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service\FieldAccessor;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service\ReadinessScoreCalculator;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service\RuleEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReadinessScoreCalculator covering score math, zero-weight guard,
 * and missing-field accumulation.
 */
final class ReadinessScoreCalculatorTest extends TestCase
{
    private ReadinessScoreCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ReadinessScoreCalculator(
            new FieldAccessor(),
            new RuleEvaluator(),
        );
    }

    public function testPerfectScoreWhenAllRulesPass(): void
    {
        $object = $this->makeMockObject(1, ['sku' => 'ABC123', 'name' => 'Test Product']);
        $profile = $this->makeProfile('Product', [
            ['fieldPath' => 'sku',  'weight' => 50.0, 'type' => ConditionType::NOT_EMPTY],
            ['fieldPath' => 'name', 'weight' => 50.0, 'type' => ConditionType::NOT_EMPTY],
        ]);

        $score = $this->calculator->calculate($object, $profile);

        self::assertSame(100.0, $score->getScore());
        self::assertSame([], $score->getMissingFieldsJson());
    }

    public function testZeroScoreWhenAllRulesFail(): void
    {
        $object = $this->makeMockObject(2, ['sku' => '', 'name' => null]);
        $profile = $this->makeProfile('Product', [
            ['fieldPath' => 'sku',  'weight' => 60.0, 'type' => ConditionType::NOT_EMPTY],
            ['fieldPath' => 'name', 'weight' => 40.0, 'type' => ConditionType::NOT_EMPTY],
        ]);

        $score = $this->calculator->calculate($object, $profile);

        self::assertSame(0.0, $score->getScore());
        self::assertCount(2, $score->getMissingFieldsJson());
    }

    public function testPartialScore(): void
    {
        $object = $this->makeMockObject(3, ['sku' => 'X1', 'name' => '']);
        $profile = $this->makeProfile('Product', [
            ['fieldPath' => 'sku',  'weight' => 60.0, 'type' => ConditionType::NOT_EMPTY],
            ['fieldPath' => 'name', 'weight' => 40.0, 'type' => ConditionType::NOT_EMPTY],
        ]);

        $score = $this->calculator->calculate($object, $profile);

        // passedWeight = 60, totalWeight = 100 → 60%
        self::assertSame(60.0, $score->getScore());
        self::assertCount(1, $score->getMissingFieldsJson());
        self::assertSame('name', $score->getMissingFieldsJson()[0]['fieldPath']);
    }

    public function testZeroScoreWhenProfileHasNoRules(): void
    {
        $object = $this->makeMockObject(4, []);
        $profile = new ReadinessProfile('Empty Profile', 'Product');

        $score = $this->calculator->calculate($object, $profile);

        self::assertSame(0.0, $score->getScore());
    }

    public function testScoreIsRoundedToTwoDecimalPlaces(): void
    {
        // 1 out of 3 rules pass, all with equal weight 33.33…
        // passedWeight = 33.33, totalWeight = 100 → 33.33%
        $object = $this->makeMockObject(5, ['a' => 'yes', 'b' => '', 'c' => '']);
        $profile = $this->makeProfile('Product', [
            ['fieldPath' => 'a', 'weight' => 33.33, 'type' => ConditionType::NOT_EMPTY],
            ['fieldPath' => 'b', 'weight' => 33.33, 'type' => ConditionType::NOT_EMPTY],
            ['fieldPath' => 'c', 'weight' => 33.34, 'type' => ConditionType::NOT_EMPTY],
        ]);

        $score = $this->calculator->calculate($object, $profile);

        // (33.33 / 100) * 100 = 33.33
        self::assertSame(round((33.33 / 100.0) * 100.0, 2), $score->getScore());
    }

    public function testMissingFieldIncludesTabHint(): void
    {
        $object = $this->makeMockObject(6, ['sku' => null]);
        $profile = new ReadinessProfile('Test', 'Product');

        $rule = new ReadinessRule('sku', ConditionType::NOT_EMPTY, 100.0, 'SKU Label');
        $rule->setTabHint('General Tab');
        $profile->addRule($rule);

        $score = $this->calculator->calculate($object, $profile);

        $missing = $score->getMissingFieldsJson();
        self::assertCount(1, $missing);
        self::assertSame('General Tab', $missing[0]['tabHint']);
    }

    public function testObjectIdIsPreservedInScore(): void
    {
        $object = $this->makeMockObject(99, []);
        $profile = new ReadinessProfile('P', 'Product');

        $score = $this->calculator->calculate($object, $profile);

        self::assertSame(99, $score->getObjectId());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a minimal mock Pimcore DataObject\Concrete that returns the given fields.
     *
     * @param array<string, mixed> $fields
     */
    private function makeMockObject(int $id, array $fields): \Pimcore\Model\DataObject\Concrete
    {
        $mock = $this->getMockBuilder(\Pimcore\Model\DataObject\Concrete::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getClassName'])
            ->addMethods(array_map(static fn (string $key) => 'get' . ucfirst($key), array_keys($fields)))
            ->getMock();

        $mock->method('getId')->willReturn($id);
        $mock->method('getClassName')->willReturn('Product');

        foreach ($fields as $key => $value) {
            $mock->method('get' . ucfirst($key))->willReturn($value);
        }

        return $mock;
    }

    /**
     * @param array<int, array{fieldPath: string, weight: float, type: ConditionType}> $ruleDefs
     */
    private function makeProfile(string $className, array $ruleDefs): ReadinessProfile
    {
        $profile = new ReadinessProfile('Test Profile', $className);

        foreach ($ruleDefs as $def) {
            $rule = new ReadinessRule($def['fieldPath'], $def['type'], $def['weight'], ucfirst($def['fieldPath']));
            $profile->addRule($rule);
        }

        return $profile;
    }
}
