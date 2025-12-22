<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Loader;

use Doctrine\Common\Collections\Collection;
use Tito10047\PersistentStateBundle\Exception\InvalidArgumentException;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

/**
 * Loader responsible for extracting identifiers from Doctrine Collection objects.
 */
final class DoctrineCollectionLoader implements IdentityLoaderInterface
{
    private const DEFAULT_IDENTIFIER_PATH = 'id';

    public function supports(mixed $source): bool
    {
        return $source instanceof Collection;
    }

    public function getTotalCount(mixed $source): int
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source must be a Doctrine Collection.');
        }

        /* @var Collection $source */
        return $source->count();
    }

    public function loadAllIdentifiers(?ValueTransformerInterface $transformer, mixed $source): array
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source must be a Doctrine Collection.');
        }

        /** @var Collection $source */
        $identifiers = [];

        foreach ($source as $item) {
            $identifiers[] = $transformer->transform($item)->data;
        }

        return $identifiers;
    }

    public function getCacheKey(mixed $source): string
    {
        if (!$this->supports($source)) {
            throw new \InvalidArgumentException('Source must be a Doctrine Collection.');
        }

        /** @var Collection $source */
        $items = $source->toArray();

        $normalized = array_map(function ($item) {
            return self::normalizeValue($item);
        }, $items);

        return 'doctrine_collection:'.md5(serialize($normalized));
    }

    /**
     * Produce a deterministic scalar/array representation for hashing.
     */
    private static function normalizeValue(mixed $value): mixed
    {
        if (is_scalar($value) || null === $value) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return ['__dt__' => true, 'v' => $value->format(DATE_ATOM)];
        }
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $k => $v) {
                $normalized[$k] = self::normalizeValue($v);
            }
            if (!array_is_list($normalized)) {
                ksort($normalized);
            }

            return $normalized;
        }
        if (is_object($value)) {
            $vars = get_object_vars($value);
            ksort($vars);

            return ['__class__' => get_class($value), 'props' => self::normalizeValue($vars)];
        }

        // Fallback – stringify
        return (string) $value;
    }
}
