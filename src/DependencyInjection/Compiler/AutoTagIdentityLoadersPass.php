<?php

namespace Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tito10047\PersistentPreferenceBundle\Selection\Loader\IdentityLoaderInterface;

/**
 * Automatically adds the persistent_selection.identity_loader tag to any
 * service definition whose class implements IdentityLoaderInterface,
 * even in the host application using this bundle.
 *
 * Mirrors the behavior of AutoTagIdentifierNormalizersPass but for loaders.
 */
final class AutoTagIdentityLoadersPass implements CompilerPassInterface
{
    public const TAG = 'persistent.selection.identity_loader';

    public function process(ContainerBuilder $container): void
    {
        $parameterBag = $container->getParameterBag();

        /** @var array<string, Definition> $definitions */
        $definitions = $container->getDefinitions();

        foreach ($definitions as $id => $definition) {
            // Skip non-instantiable or special definitions
            if ($definition->isAbstract() || $definition->isSynthetic()) {
                continue;
            }

            // If it already has the tag, skip (idempotent)
            if ($definition->hasTag(self::TAG)) {
                continue;
            }

            // Try to resolve the class name
            $class = $definition->getClass() ?: $id; // FQCN service id fallback
            if (!is_string($class) || $class === '') {
                continue;
            }

            // Resolve parameters like "%foo.class%"
            $class = $parameterBag->resolveValue($class);
            if (!is_string($class)) {
                continue;
            }

            // Safe reflection via container helper
            $reflection = $container->getReflectionClass($class, false);
            if (!$reflection) {
                continue;
            }

            if ($reflection->implementsInterface(IdentityLoaderInterface::class)) {
                $definition->addTag(self::TAG)->setPublic(true);
            }
        }
    }
}
