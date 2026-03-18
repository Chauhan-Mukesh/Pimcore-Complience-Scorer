<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Stores the calculated readiness score for a specific DataObject + ReadinessProfile combination.
 *
 * This is a flat, indexed table queried by the Studio widget for fast reads.
 * Scores are never calculated synchronously on read — they are always written asynchronously
 * by the Messenger handler after an object update event.
 */
#[ORM\Entity(repositoryClass: \CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ObjectScoreRepository::class)]
#[ORM\Table(name: 'bundle_readiness_scores')]
#[ORM\Index(columns: ['object_id'], name: 'idx_object_id')]
#[ORM\Index(columns: ['profile_id'], name: 'idx_profile_id')]
#[ORM\UniqueConstraint(name: 'uq_object_profile', columns: ['object_id', 'profile_id'])]
class ObjectScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Pimcore DataObject ID.
     */
    #[ORM\Column(type: 'integer')]
    private int $objectId;

    /**
     * UUID of the ReadinessProfile this score belongs to.
     */
    #[ORM\Column(type: 'string', length: 36)]
    private string $profileId;

    /**
     * Calculated score in the range [0.0, 100.0].
     */
    #[ORM\Column(type: 'float')]
    private float $score;

    /**
     * JSON array of missing field descriptors.
     *
     * Each element has the shape:
     *   {
     *     "fieldPath": "sku",
     *     "label": "SKU / Article Number",
     *     "weight": 10.0,
     *     "tabHint": "General",
     *     "severity": "error",
     *     "dimension": "completeness",
     *     "errorMessage": null
     *   }
     *
     * @var array<int, array{fieldPath: string, label: string, weight: float, tabHint: string|null, severity: string, dimension: string, errorMessage: string|null}>
     */
    #[ORM\Column(type: 'json')]
    private array $missingFieldsJson = [];

    /**
     * Per-dimension sub-scores (JSON map).
     * Shape: { "completeness": 80.0, "format": 100.0, ... }
     *
     * @var array<string, float>
     */
    #[ORM\Column(type: 'json')]
    private array $dimensionScores = [];

    /**
     * Violation counts per severity level.
     * Shape: { "error": 2, "warning": 1, "info": 0 }
     *
     * @var array<string, int>
     */
    #[ORM\Column(type: 'json')]
    private array $severityCounts = ['error' => 0, 'warning' => 0, 'info' => 0];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $calculatedAt;

    public function __construct(int $objectId, string $profileId, float $score)
    {
        $this->objectId = $objectId;
        $this->profileId = $profileId;
        $this->score = $score;
        $this->calculatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function getProfileId(): string
    {
        return $this->profileId;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }

    /**
     * @return array<int, array{fieldPath: string, label: string, weight: float, tabHint: string|null, severity: string, dimension: string, errorMessage: string|null}>
     */
    public function getMissingFieldsJson(): array
    {
        return $this->missingFieldsJson;
    }

    /**
     * @param array<int, array{fieldPath: string, label: string, weight: float, tabHint: string|null, severity: string, dimension: string, errorMessage: string|null}> $missingFieldsJson
     */
    public function setMissingFieldsJson(array $missingFieldsJson): static
    {
        $this->missingFieldsJson = $missingFieldsJson;

        return $this;
    }

    /**
     * @return array<string, float>
     */
    public function getDimensionScores(): array
    {
        return $this->dimensionScores;
    }

    /**
     * @param array<string, float> $dimensionScores
     */
    public function setDimensionScores(array $dimensionScores): static
    {
        $this->dimensionScores = $dimensionScores;

        return $this;
    }

    /**
     * @return array<string, int>
     */
    public function getSeverityCounts(): array
    {
        return $this->severityCounts;
    }

    /**
     * @param array<string, int> $severityCounts
     */
    public function setSeverityCounts(array $severityCounts): static
    {
        $this->severityCounts = $severityCounts;

        return $this;
    }

    public function getCalculatedAt(): \DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(\DateTimeImmutable $calculatedAt): static
    {
        $this->calculatedAt = $calculatedAt;

        return $this;
    }
}
