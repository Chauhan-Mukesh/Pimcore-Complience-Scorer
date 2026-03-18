<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Tests\Unit\Entity;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ConditionType;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessProfile;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReadinessProfile domain logic.
 */
final class ReadinessProfileTest extends TestCase
{
    public function testNewProfileHasCorrectDefaults(): void
    {
        $profile = new ReadinessProfile('EU Medical Device', 'Medicine');

        self::assertSame('EU Medical Device', $profile->getName());
        self::assertSame('Medicine', $profile->getPimcoreClassName());
        self::assertTrue($profile->isActive());
        self::assertNull($profile->getDescription());
        self::assertCount(0, $profile->getRules());
        self::assertSame(0.0, $profile->getTotalWeight());
        self::assertNotEmpty($profile->getId());
    }

    public function testAddRuleSetsProfileOnRule(): void
    {
        $profile = new ReadinessProfile('Test', 'Product');
        $rule = new ReadinessRule('sku', ConditionType::NOT_EMPTY, 50.0, 'SKU');

        $profile->addRule($rule);

        self::assertCount(1, $profile->getRules());
        self::assertSame($profile, $rule->getProfile());
    }

    public function testAddSameRuleTwiceDoesNotDuplicate(): void
    {
        $profile = new ReadinessProfile('Test', 'Product');
        $rule = new ReadinessRule('sku', ConditionType::NOT_EMPTY, 50.0, 'SKU');

        $profile->addRule($rule);
        $profile->addRule($rule);

        self::assertCount(1, $profile->getRules());
    }

    public function testRemoveRuleDecreasesCount(): void
    {
        $profile = new ReadinessProfile('Test', 'Product');
        $rule = new ReadinessRule('sku', ConditionType::NOT_EMPTY, 50.0, 'SKU');

        $profile->addRule($rule);
        $profile->removeRule($rule);

        self::assertCount(0, $profile->getRules());
    }

    public function testTotalWeightSumsRuleWeights(): void
    {
        $profile = new ReadinessProfile('Test', 'Product');
        $profile->addRule(new ReadinessRule('sku',  ConditionType::NOT_EMPTY, 40.0, 'SKU'));
        $profile->addRule(new ReadinessRule('name', ConditionType::NOT_EMPTY, 35.0, 'Name'));
        $profile->addRule(new ReadinessRule('ean',  ConditionType::NOT_EMPTY, 25.0, 'EAN'));

        self::assertSame(100.0, $profile->getTotalWeight());
    }

    public function testSetActiveToFalseSoftDeletes(): void
    {
        $profile = new ReadinessProfile('Test', 'Product');
        $profile->setIsActive(false);

        self::assertFalse($profile->isActive());
    }

    public function testIdIsUuidV7Format(): void
    {
        $profile = new ReadinessProfile('Test', 'Product');
        // UUID v7 format: xxxxxxxx-xxxx-7xxx-yxxx-xxxxxxxxxxxx
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $profile->getId(),
        );
    }
}
