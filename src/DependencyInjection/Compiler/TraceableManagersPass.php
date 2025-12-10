<?php

namespace Tito10047\PersistentStateBundle\DependencyInjection\Compiler;

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Preference\Service\TraceablePersistentManager;

/**
 * Decorates all preference managers with TraceablePreferenceManager in debug mode
 * when the WebProfiler bundle is available. This allows tracing multiple managers.
 */
final class TraceableManagersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Enable only if profiler package is present and kernel debug is on
        $debug = (bool) ($container->hasParameter('kernel.debug') ? $container->getParameter('kernel.debug') : false);
        if (!class_exists(WebProfilerBundle::class) || !$debug) {
            return;
        }

        if (!$container->hasDefinition(PreferenceDataCollector::class) && !$container->hasAlias(PreferenceDataCollector::class)) {
            return; // collector not registered, nothing to do
        }

        $tag = AutoTagContextKeyResolverPass::TAG; // ensure class is loaded
        unset($tag);

        // Find all managers tagged with our custom tag
        $tagName = 'persistent_state.preference.manager';
        foreach ($container->findTaggedServiceIds($tagName, true) as $serviceId => $tagAttrsList) {
            // Determine manager name from tag attribute 'name' if available
            $managerName = $tagAttrsList[0]['name'] ?? $serviceId;

            $decoratorId = $serviceId . '.traceable';
            if ($container->hasDefinition($decoratorId)) {
                continue; // already decorated
            }

            $definition = new Definition(TraceablePersistentManager::class);
            $definition->setPublic(true);
            $definition->setDecoratedService($serviceId);
            $definition->setArguments([
                new Reference($decoratorId . '.inner'),
                new Reference(PreferenceDataCollector::class),
                $managerName,
            ]);

            $container->setDefinition($decoratorId, $definition);
        }
    }
}
