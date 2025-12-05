<?php

namespace Tito10047\PersistentPreferenceBundle\Resolver;

use Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope;

interface StorableObjectConverterInterface
{
	public function supports(mixed $subject): bool;

	public function toStorable(object $subject): StorableEnvelope;

	public function fromStorable(StorableEnvelope $envelope): object;
}