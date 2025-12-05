<?php

namespace Tito10047\PersistentPreferenceBundle\Transformer;

use Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope;

class ObjectIdValueTransformer implements ValueTransformerInterface{
	
	public function __construct(
		private readonly string $class,
		private readonly string $identifierMethod = 'getId',
	) { }

	public function supports(mixed $value): bool {
        return $value instanceof $this->class;
    }

    public function transform(mixed $value): StorableEnvelope {
		if (!$value instanceof $this->class) {
			throw new \InvalidArgumentException('Expected instance of ' . $this->class);
		}
        return new StorableEnvelope($this->class, $value->{$this->identifierMethod}());
    }

    public function supportsReverse(StorableEnvelope $value): bool {
        return $value->className === $this->class;
    }

    public function reverseTransform(StorableEnvelope $value): mixed {
        return $value->data;
    }
}