<?php

declare(strict_types=1);

namespace CauhanMukesh\PimcoreMarketReadinessShieldBundle\Service;

use Pimcore\Model\DataObject\Concrete;

/**
 * Resolves a dot-notation field path to its raw value on a Pimcore DataObject.
 *
 * Supported path segments:
 *   - Simple field:          "sku"
 *   - Localised field:       "localizedfields.en.metaTitle"
 *   - Object brick field:    "bricks.NutritionBrick.calories"
 *   - Field collection:      "ingredients.0.name"
 *   - Relation / asset:      "mainImage", "documents"
 *
 * Returns null when:
 *   - A segment does not exist on the object/brick/collection
 *   - An array index is out of bounds
 *   - Any intermediate value is null
 */
final class FieldAccessor
{
    /**
     * Resolves the given dot-notation path on the DataObject and returns the raw value,
     * or null if the path cannot be resolved.
     */
    public function getValue(Concrete $object, string $fieldPath): mixed
    {
        $segments = explode('.', $fieldPath);

        return $this->resolveSegments($object, $segments);
    }

    /**
     * @param array<int, string> $segments
     */
    private function resolveSegments(mixed $current, array $segments): mixed
    {
        foreach ($segments as $index => $segment) {
            if (null === $current) {
                return null;
            }

            // Numeric segment — treat as array index.
            if (ctype_digit($segment)) {
                if (!\is_array($current) && !($current instanceof \Traversable)) {
                    return null;
                }

                $arr = \is_array($current) ? $current : iterator_to_array($current);
                $intIndex = (int) $segment;

                if (!\array_key_exists($intIndex, $arr)) {
                    return null;
                }

                $current = $arr[$intIndex];
                continue;
            }

            // Localised fields shorthand: "localizedfields.{locale}.{field}"
            if ('localizedfields' === $segment && $current instanceof Concrete) {
                $remainingSegments = \array_slice($segments, $index + 1);
                if (\count($remainingSegments) < 2) {
                    return null;
                }

                [$locale, $fieldName] = [$remainingSegments[0], $remainingSegments[1]];
                $localizedFields = $current->getLocalizedFields();
                if (null === $localizedFields) {
                    return null;
                }

                $value = $localizedFields->getLocalizedValue($fieldName, $locale);

                // If there are further segments after locale and field, recurse.
                $deeperSegments = \array_slice($remainingSegments, 2);
                if (\count($deeperSegments) > 0) {
                    return $this->resolveSegments($value, $deeperSegments);
                }

                return $value;
            }

            // Object Bricks shorthand: "bricks.{BrickType}.{field}"
            if ('bricks' === $segment && $current instanceof Concrete) {
                $remainingSegments = \array_slice($segments, $index + 1);
                if (\count($remainingSegments) < 2) {
                    return null;
                }

                [$brickType, $fieldName] = [$remainingSegments[0], $remainingSegments[1]];
                $getter = 'get' . ucfirst($brickType);

                $brickContainer = $current->getObjectVars()['objectbricks'] ?? null;
                if (null === $brickContainer) {
                    // Try via generic getter if available.
                    if (!method_exists($current, $getter)) {
                        return null;
                    }
                    $brick = $current->{$getter}();
                } else {
                    $brick = method_exists($brickContainer, $getter) ? $brickContainer->{$getter}() : null;
                }

                if (null === $brick) {
                    return null;
                }

                $deeperSegments = \array_slice($remainingSegments, 2);
                $fieldGetter = 'get' . ucfirst($fieldName);

                if (!method_exists($brick, $fieldGetter)) {
                    return null;
                }

                $value = $brick->{$fieldGetter}();

                if (\count($deeperSegments) > 0) {
                    return $this->resolveSegments($value, $deeperSegments);
                }

                return $value;
            }

            // Generic getter: try getFieldName() on the current object/value.
            $getter = 'get' . ucfirst($segment);

            if (\is_object($current) && method_exists($current, $getter)) {
                $current = $current->{$getter}();
                continue;
            }

            // Try direct array access.
            if (\is_array($current) && \array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            return null;
        }

        return $current;
    }
}
