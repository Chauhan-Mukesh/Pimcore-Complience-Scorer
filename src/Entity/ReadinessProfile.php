<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A named set of rules that define what "ready" means for a specific Pimcore class.
 *
 * Examples: "EU Medical Device Profile", "US Food Safety Profile", "Amazon DE Readiness"
 */
#[ORM\Entity(repositoryClass: \CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ReadinessProfileRepository::class)]
#[ORM\Table(name: 'bundle_readiness_profiles')]
#[ORM\HasLifecycleCallbacks]
class ReadinessProfile
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * The Pimcore DataObject class key this profile applies to (e.g. "Product", "Medicine").
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $pimcoreClassName;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ReadinessRule> */
    #[ORM\OneToMany(mappedBy: 'profile', targetEntity: ReadinessRule::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $rules;

    public function __construct(string $name, string $pimcoreClassName)
    {
        $this->id = (string) Uuid::v7();
        $this->name = $name;
        $this->pimcoreClassName = $pimcoreClassName;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->rules = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPimcoreClassName(): string
    {
        return $this->pimcoreClassName;
    }

    public function setPimcoreClassName(string $pimcoreClassName): static
    {
        $this->pimcoreClassName = $pimcoreClassName;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @return Collection<int, ReadinessRule> */
    public function getRules(): Collection
    {
        return $this->rules;
    }

    public function addRule(ReadinessRule $rule): static
    {
        if (!$this->rules->contains($rule)) {
            $this->rules->add($rule);
            $rule->setProfile($this);
        }

        return $this;
    }

    public function removeRule(ReadinessRule $rule): static
    {
        $this->rules->removeElement($rule);

        return $this;
    }

    /**
     * Returns the sum of all rule weights. Should equal 100.0 for a complete profile.
     */
    public function getTotalWeight(): float
    {
        return array_sum(
            $this->rules->map(static fn (ReadinessRule $r): float => $r->getWeight())->toArray()
        );
    }
}
