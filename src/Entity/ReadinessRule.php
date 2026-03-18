<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A single evaluatable rule within a ReadinessProfile.
 *
 * Defines which field to check, how to evaluate it, and how much it contributes
 * to the overall readiness score.
 */
#[ORM\Entity(repositoryClass: \CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ReadinessRuleRepository::class)]
#[ORM\Table(name: 'bundle_readiness_rules')]
class ReadinessRule
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: ReadinessProfile::class, inversedBy: 'rules')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ReadinessProfile $profile;

    /**
     * Dot-notation path to the field on the DataObject.
     * Examples: "sku", "images", "bricks.NutritionBrick.calories", "localizedfields.en.metaTitle"
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $fieldPath;

    #[ORM\Column(type: 'string', length: 50, enumType: ConditionType::class)]
    private ConditionType $conditionType;

    /**
     * The threshold value for conditions that require one (e.g. min_length, min_value, regex).
     * Null for conditions like not_empty and file_attached.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $conditionValue = null;

    /**
     * Percentage contribution of this rule to the total profile score (0–100).
     */
    #[ORM\Column(type: 'float')]
    private float $weight;

    /**
     * Human-readable label shown in the Studio missing-fields list.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $label;

    /**
     * Optional hint for which Pimcore Studio tab contains this field (used to build jump links).
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $tabHint = null;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    public function __construct(
        string $fieldPath,
        ConditionType $conditionType,
        float $weight,
        string $label,
    ) {
        $this->id = (string) Uuid::v7();
        $this->fieldPath = $fieldPath;
        $this->conditionType = $conditionType;
        $this->weight = $weight;
        $this->label = $label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProfile(): ReadinessProfile
    {
        return $this->profile;
    }

    public function setProfile(ReadinessProfile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getFieldPath(): string
    {
        return $this->fieldPath;
    }

    public function setFieldPath(string $fieldPath): static
    {
        $this->fieldPath = $fieldPath;

        return $this;
    }

    public function getConditionType(): ConditionType
    {
        return $this->conditionType;
    }

    public function setConditionType(ConditionType $conditionType): static
    {
        $this->conditionType = $conditionType;

        return $this;
    }

    public function getConditionValue(): ?string
    {
        return $this->conditionValue;
    }

    public function setConditionValue(?string $conditionValue): static
    {
        $this->conditionValue = $conditionValue;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getTabHint(): ?string
    {
        return $this->tabHint;
    }

    public function setTabHint(?string $tabHint): static
    {
        $this->tabHint = $tabHint;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }
}
