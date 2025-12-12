<?php

namespace Tito10047\PersistentStateBundle\Resolver;

use Tito10047\PersistentStateBundle\Storage\StorableEnvelope;

interface StorableObjectConverterInterface
{
    public function supports(mixed $subject): bool;

    public function toStorable(object $subject): StorableEnvelope;

    public function fromStorable(StorableEnvelope $envelope): object;
}
