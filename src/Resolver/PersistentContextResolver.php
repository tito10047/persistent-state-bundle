<?php

namespace Tito10047\PersistentStateBundle\Resolver;

use Tito10047\PersistentStateBundle\Service\PersistentContextInterface;

class PersistentContextResolver implements ContextKeyResolverInterface
{
    public function supports(object $context): bool
    {
        return $context instanceof PersistentContextInterface;
    }

    public function resolve(object $context): string
    {
        return $context->getPersistentContext();
    }
}
