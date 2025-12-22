<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Converter;

/**
 * @deprecated in favor of SymfonySerializerConverter
 */
class ObjectVarsConverter implements MetadataConverterInterface
{
    public function convertToStorable(object $metadataObject): array
    {
        return get_object_vars($metadataObject);
    }

    public function convertFromStorable(array $storedData, string $targetClass): ?object
    {
        return (object) $storedData;
    }
}
