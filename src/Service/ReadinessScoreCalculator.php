<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ObjectScore;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessProfile;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use Pimcore\Model\DataObject\Concrete;

/**
 * Calculates the readiness score for a DataObject against a ReadinessProfile.
 *
 * This service is called from the async Messenger handler — never on the request thread.
 * It returns a hydrated ObjectScore that is not yet persisted.
 *
 * Score formula:
 *   score = (passedWeight / totalWeight) * 100
 *   If totalWeight === 0.0 → score = 0.0
 */
final class ReadinessScoreCalculator
{
    public function __construct(
        private readonly FieldAccessor $fieldAccessor,
        private readonly RuleEvaluator $ruleEvaluator,
    ) {
    }

    /**
     * Calculates the score for the given object against the given profile.
     *
     * @return ObjectScore Hydrated but not yet persisted.
     */
    public function calculate(Concrete $object, ReadinessProfile $profile): ObjectScore
    {
        $rules = $profile->getRules()->toArray();
        $totalWeight = 0.0;
        $passedWeight = 0.0;

        /** @var array<int, array{fieldPath: string, label: string, weight: float, tabHint: string|null}> $missingFields */
        $missingFields = [];

        foreach ($rules as $rule) {
            /** @var ReadinessRule $rule */
            $totalWeight += $rule->getWeight();
            $value = $this->fieldAccessor->getValue($object, $rule->getFieldPath());
            $passes = $this->ruleEvaluator->evaluate($rule, $value);

            if ($passes) {
                $passedWeight += $rule->getWeight();
            } else {
                $missingFields[] = [
                    'fieldPath' => $rule->getFieldPath(),
                    'label'     => $rule->getLabel(),
                    'weight'    => $rule->getWeight(),
                    'tabHint'   => $rule->getTabHint(),
                ];
            }
        }

        $score = $totalWeight > 0.0
            ? round(($passedWeight / $totalWeight) * 100.0, 2)
            : 0.0;

        $objectScore = new ObjectScore($object->getId() ?? 0, $profile->getId(), $score);
        $objectScore->setMissingFieldsJson($missingFields);
        $objectScore->setCalculatedAt(new \DateTimeImmutable());

        return $objectScore;
    }
}
