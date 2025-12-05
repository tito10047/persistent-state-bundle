<?php

namespace Tito10047\PersistentPreferenceBundle\Resolver;

use Tito10047\PersistentPreferenceBundle\Service\PersistentContextInterface;

class ObjectContextResolver implements ContextKeyResolverInterface{


	public function __construct(
		private readonly string $class,
		private readonly string $prefix,
		private readonly string $identifierMethod = 'getId',

	) { }

	public function supports(object $context): bool {
		return $context instanceof $this->class;
	}

	public function resolve(object $context): string {
		if (!method_exists($context, $this->identifierMethod)){
			throw new \LogicException('Method ' . $this->identifierMethod . ' not found in ' . get_class($context));
		}
		return $this->prefix . $context->{$this->identifierMethod}();
	}
}