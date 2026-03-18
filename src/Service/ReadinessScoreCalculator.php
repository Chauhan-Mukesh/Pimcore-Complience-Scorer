<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ObjectScore;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\QualityDimension;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessProfile;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\SeverityLevel;
use Pimcore\Model\DataObject\Concrete;

/**
 * Calculates the readiness score for a DataObject against a ReadinessProfile.
 *
 * This service is called from the async Messenger handler — never on the request thread.
 * It returns a hydrated ObjectScore that is not yet persisted.
 *
 * Overall score formula:
 *   score = (passedWeight / totalWeight) * 100
 *   If totalWeight === 0.0 → score = 0.0
 *
 * Per-dimension scores follow the same formula restricted to rules belonging to
 * each QualityDimension.  Severity counts track how many rules failed per level.
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
     * @return ObjectScore hydrated but not yet persisted
     */
    public function calculate(Concrete $object, ReadinessProfile $profile): ObjectScore
    {
        /** @var ReadinessRule[] $rules */
        $rules = $profile->getRules()->toArray();

        $totalWeight = 0.0;
        $passedWeight = 0.0;

        // Per-dimension accumulators: [dimension => [total, passed]]
        /** @var array<string, array{total: float, passed: float}> $dimAccum */
        $dimAccum = [];

        // Severity violation counters.
        /** @var array<string, int> $severityCounts */
        $severityCounts = [
            SeverityLevel::ERROR->value => 0,
            SeverityLevel::WARNING->value => 0,
            SeverityLevel::INFO->value => 0,
        ];

        /** @var array<int, array{fieldPath: string, label: string, weight: float, tabHint: string|null, severity: string, dimension: string, errorMessage: string|null}> $missingFields */
        $missingFields = [];

        foreach ($rules as $rule) {
            $weight = $rule->getWeight();
            $dimKey = $rule->getDimension()->value;
            $sevKey = $rule->getSeverity()->value;

            $totalWeight += $weight;

            // Initialise dimension accumulator on first encounter.
            if (!isset($dimAccum[$dimKey])) {
                $dimAccum[$dimKey] = ['total' => 0.0, 'passed' => 0.0];
            }
            $dimAccum[$dimKey]['total'] += $weight;

            $value = $this->fieldAccessor->getValue($object, $rule->getFieldPath());
            $passes = $this->ruleEvaluator->evaluate($rule, $value);

            if ($passes) {
                $passedWeight += $weight;
                $dimAccum[$dimKey]['passed'] += $weight;
            } else {
                $missingFields[] = [
                    'fieldPath' => $rule->getFieldPath(),
                    'label' => $rule->getLabel(),
                    'weight' => $weight,
                    'tabHint' => $rule->getTabHint(),
                    'severity' => $sevKey,
                    'dimension' => $dimKey,
                    'errorMessage' => $rule->getErrorMessage(),
                ];
                $severityCounts[$sevKey] = ($severityCounts[$sevKey] ?? 0) + 1;
            }
        }

        $score = $totalWeight > 0.0
            ? round(($passedWeight / $totalWeight) * 100.0, 2)
            : 0.0;

        // Compute per-dimension sub-scores.
        /** @var array<string, float> $dimensionScores */
        $dimensionScores = [];
        foreach ($dimAccum as $dim => $acc) {
            $dimensionScores[$dim] = $acc['total'] > 0.0
                ? round(($acc['passed'] / $acc['total']) * 100.0, 2)
                : 0.0;
        }

        // Ensure all dimensions are represented (with 100.0 if no rules in that dimension).
        foreach (QualityDimension::cases() as $dimension) {
            $dimensionScores[$dimension->value] ??= 100.0;
        }

        $objectScore = new ObjectScore($object->getId() ?? 0, $profile->getId(), $score);
        $objectScore->setMissingFieldsJson($missingFields);
        $objectScore->setDimensionScores($dimensionScores);
        $objectScore->setSeverityCounts($severityCounts);
        $objectScore->setCalculatedAt(new \DateTimeImmutable());

        return $objectScore;
    }
}
