<?php

namespace Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tito10047\PersistentPreferenceBundle\Resolver\ContextKeyResolverInterface;
use Tito10047\PersistentPreferenceBundle\Transformer\ValueTransformerInterface;

final class AutoTagValueTransformerPass
{
    public const TAG = 'persistent_preference.value_transformer';

}
