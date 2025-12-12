<?php

namespace Tito10047\PersistentStateBundle\Selection\Loader;

use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

final class ArrayLoader implements IdentityLoaderInterface
{
    public function supports(mixed $source): bool
    {
        return is_array($source);
    }

    public function loadAllIdentifiers(?ValueTransformerInterface $transformer, mixed $source): array
    {
        if (!is_array($source)) {
            throw new \InvalidArgumentException('Source must be an array.');
        }

        $identifiers = [];

        foreach ($source as $item) {
            $identifiers[] = $transformer->transform($item)->data;
        }

        return $identifiers;
    }

    public function getTotalCount(mixed $source): int
    {
        return count($source);
    }

    public function getCacheKey(mixed $source): string
    {
        if (!is_array($source)) {
            throw new \InvalidArgumentException('Source must be an array.');
        }

        // Use a deterministic hash of the full source structure. serialize() preserves
        // structure and values for arrays/objects commonly used in tests.
        return 'array:'.md5(serialize($source));
    }
}
