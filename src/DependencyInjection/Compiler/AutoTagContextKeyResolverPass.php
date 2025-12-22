<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tito10047\PersistentStateBundle\Resolver\ContextKeyResolverInterface;

// no need to reference specific resolver classes here

/**
 * Automatically adds the persistent_selection.identifier_normalizer tag to any
 * service definition whose class implements IdentifierNormalizerInterface,
 * even in the host application using this bundle.
 *
 * This keeps the bundle manual configuration style (no autowire/autoconfigure)
 * while still providing a convenient way for apps to plug in their own
 * normalizers without having to remember the tag.
 */
final class AutoTagContextKeyResolverPass implements CompilerPassInterface
{
    public const TAG = 'persistent_state.preference.context_key_resolver';

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
            $class = $definition->getClass() ?: $id; // Fallback: service id can be FQCN
            if (!is_string($class) || '' === $class) {
                continue;
            }

            // Resolve parameters like "%foo.class%"
            $class = $parameterBag->resolveValue($class);
            if (!is_string($class)) {
                continue;
            }

            // Use ContainerBuilder's reflection helper to avoid triggering
            // autoload errors for vendor/dev classes that may not be present.
            $reflection = $container->getReflectionClass($class, false);
            if (!$reflection) {
                continue; // cannot reflect, skip silently
            }

            if ($reflection->implementsInterface(ContextKeyResolverInterface::class)) {
                $definition->addTag(self::TAG)->setPublic(true);
            }
        }
    }
}
