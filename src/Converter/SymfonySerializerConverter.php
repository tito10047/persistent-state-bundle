<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Converter;

use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerConverter implements MetadataConverterInterface
{
    // Konštruktor vyžaduje rozhranie, nie konkrétnu triedu
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function convertToStorable(object $metadataObject): array
    {
        return $this->serializer->normalize($metadataObject, 'array');
    }

    public function convertFromStorable(array $storedData, ?string $targetClass): ?object
    {
        if (!$targetClass) {
            throw new \LogicException('Missing target class for denormalization.');
        }

        return $this->serializer->denormalize($storedData['data'], $targetClass, 'array');
    }
}
