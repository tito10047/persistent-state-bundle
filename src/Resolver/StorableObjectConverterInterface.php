<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Resolver;

use Tito10047\PersistentStateBundle\Preference\Storage\StorableEnvelope;

interface StorableObjectConverterInterface
{
    public function supports(mixed $subject): bool;

    public function toStorable(object $subject): StorableEnvelope;

    public function fromStorable(StorableEnvelope $envelope): object;
}
