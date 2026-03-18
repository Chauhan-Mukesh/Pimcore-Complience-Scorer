<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Controller\Admin;

use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ConditionType;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessProfile;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Entity\ReadinessRule;
use CauhanMukesh\PimcoreMarketReadinessShieldBundle\Repository\ReadinessProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Admin CRUD API for managing Readiness Profiles and their Rules.
 *
 * Requires ROLE_PIMCORE_ADMIN for all state-changing operations.
 */
#[Route('/api/readiness/admin/profiles', name: 'pimcore_market_readiness_shield_admin_profile_')]
#[IsGranted('ROLE_PIMCORE_ADMIN')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ReadinessProfileRepository $profileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Returns a list of all profiles (active and inactive).
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $profiles = $this->profileRepository->findBy([], ['name' => 'ASC']);

        $data = array_map(static fn (ReadinessProfile $p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'description' => $p->getDescription(),
            'pimcoreClassName' => $p->getPimcoreClassName(),
            'isActive' => $p->isActive(),
            'totalWeight' => $p->getTotalWeight(),
            'ruleCount' => $p->getRules()->count(),
            'createdAt' => $p->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $p->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ], $profiles);

        return $this->json($data);
    }

    /**
     * Returns a single profile with its rules.
     */
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $profile = $this->profileRepository->find($id);

        if (!$profile instanceof ReadinessProfile) {
            return $this->json(['error' => 'Profile not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeProfile($profile));
    }

    /**
     * Creates a new Readiness Profile.
     *
     * Request body (JSON):
     *   { "name": "...", "description": "...", "pimcoreClassName": "...", "rules": [...] }
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $profile = new ReadinessProfile(
            (string) ($data['name'] ?? ''),
            (string) ($data['pimcoreClassName'] ?? ''),
        );
        $profile->setDescription(isset($data['description']) ? (string) $data['description'] : null);

        $this->applyRules($profile, $data['rules'] ?? []);

        $violations = $this->validator->validate($profile);
        if (\count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        return $this->json($this->serializeProfile($profile), Response::HTTP_CREATED);
    }

    /**
     * Updates an existing Readiness Profile.
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $profile = $this->profileRepository->find($id);

        if (!$profile instanceof ReadinessProfile) {
            return $this->json(['error' => 'Profile not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if (null === $data) {
            return $this->json(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $profile->setName((string) $data['name']);
        }

        if (\array_key_exists('description', $data)) {
            $profile->setDescription(null !== $data['description'] ? (string) $data['description'] : null);
        }

        if (isset($data['pimcoreClassName'])) {
            $profile->setPimcoreClassName((string) $data['pimcoreClassName']);
        }

        if (\array_key_exists('isActive', $data)) {
            $profile->setIsActive((bool) $data['isActive']);
        }

        if (\array_key_exists('rules', $data)) {
            // Remove all existing rules and re-apply.
            foreach ($profile->getRules()->toArray() as $rule) {
                $profile->removeRule($rule);
            }
            $this->applyRules($profile, $data['rules']);
        }

        $violations = $this->validator->validate($profile);
        if (\count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeProfile($profile));
    }

    /**
     * Soft-deletes a profile by marking it inactive.
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $profile = $this->profileRepository->find($id);

        if (!$profile instanceof ReadinessProfile) {
            return $this->json(['error' => 'Profile not found.'], Response::HTTP_NOT_FOUND);
        }

        $profile->setIsActive(false);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Applies a rules payload to a profile (replaces existing rules in memory).
     *
     * @param array<mixed> $rulesData
     */
    private function applyRules(ReadinessProfile $profile, array $rulesData): void
    {
        foreach ($rulesData as $index => $ruleData) {
            if (!\is_array($ruleData)) {
                continue;
            }

            $conditionTypeValue = (string) ($ruleData['conditionType'] ?? '');
            $conditionType = ConditionType::tryFrom($conditionTypeValue);

            if (null === $conditionType) {
                continue;
            }

            $rule = new ReadinessRule(
                (string) ($ruleData['fieldPath'] ?? ''),
                $conditionType,
                (float) ($ruleData['weight'] ?? 0.0),
                (string) ($ruleData['label'] ?? ''),
            );

            $rule->setConditionValue(isset($ruleData['conditionValue']) ? (string) $ruleData['conditionValue'] : null);
            $rule->setTabHint(isset($ruleData['tabHint']) ? (string) $ruleData['tabHint'] : null);
            $rule->setSortOrder((int) ($ruleData['sortOrder'] ?? $index));

            $profile->addRule($rule);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(Request $request): ?array
    {
        try {
            $content = $request->getContent();
            if ('' === $content) {
                return null;
            }

            $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

            return \is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(ReadinessProfile $profile): array
    {
        $rules = array_map(static fn (ReadinessRule $r) => [
            'id' => $r->getId(),
            'fieldPath' => $r->getFieldPath(),
            'conditionType' => $r->getConditionType()->value,
            'conditionValue' => $r->getConditionValue(),
            'weight' => $r->getWeight(),
            'label' => $r->getLabel(),
            'tabHint' => $r->getTabHint(),
            'sortOrder' => $r->getSortOrder(),
        ], $profile->getRules()->toArray());

        return [
            'id' => $profile->getId(),
            'name' => $profile->getName(),
            'description' => $profile->getDescription(),
            'pimcoreClassName' => $profile->getPimcoreClassName(),
            'isActive' => $profile->isActive(),
            'totalWeight' => $profile->getTotalWeight(),
            'rules' => $rules,
            'createdAt' => $profile->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $profile->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param ConstraintViolationListInterface<\Symfony\Component\Validator\ConstraintViolationInterface> $violations
     *
     * @return array<string, string>
     */
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        return $errors;
    }
}
