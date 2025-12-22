<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\PersistentStateBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentStateBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentStateBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentStateBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\PersistentStateBundle\DependencyInjection\Compiler\TraceableManagersPass;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceFactory;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceFactoryInterface;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManager;
use Tito10047\PersistentStateBundle\Resolver\ContextResolver;
use Tito10047\PersistentStateBundle\Resolver\ContextResolverInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionFactory;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionFactoryInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManager;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @see https://symfony.com/doc/current/bundles/best_practices.html
 */
class PersistentStateBundle extends AbstractBundle
{
    public const STIMULUS_CONTROLLER = 'tito10047--persistent-state-bundle--selection';

    protected string $extensionAlias = 'persistent_state';
    public const TRANSFORMER_TAG = 'persistent_state.preference.value_transformer';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $services = $container->services();

        // Register core services if they are not already registered (from services.php)
        $services->set('persistent_state.resolver.context', ContextResolver::class)
            ->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG));
        $services->alias(ContextResolverInterface::class, 'persistent_state.resolver.context');

        // Default metadata converter service
        $services->set('persistent_state.preference.converter.object_vars', ObjectVarsConverter::class)
            ->alias(MetadataConverterInterface::class, 'persistent_state.preference.converter.object_vars');

        $configManagers = $config['preference']['managers'] ?? [];
        foreach ($configManagers as $name => $subConfig) {
            $storageId = $subConfig['storage'] ?? 'persistent_state.preference.storage.session';
            $storageId = ltrim($storageId, '@');

            $factoryId = 'persistent_state.preference.factory.'.$name;
            $services->set($factoryId, PreferenceFactory::class)
                ->arg('$transformers', tagged_iterator(self::TRANSFORMER_TAG))
                ->arg('$storage', service($storageId))
                ->arg('$dispatcher', service('event_dispatcher'));

            $services
                ->set('persistent_state.preference.manager.'.$name, PreferenceManager::class)
                ->public()
                ->arg('$contextResolver', service(ContextResolverInterface::class))
                ->arg('$factory', service($factoryId))
                ->arg('$storage', service($storageId))
                ->tag('persistent_state.preference.manager', ['name' => $name]);
        }

        $configManagers = $config['selection']['managers'] ?? [];
        foreach ($configManagers as $name => $subConfig) {
            $storageId = ltrim($subConfig['storage'], '@');
            $transformerId = ltrim($subConfig['transformer'], '@');
            $metadataTransformerId = ltrim($subConfig['metadata_transformer'], '@');
            $ttl = $subConfig['ttl'] ?? null;

            $factoryId = 'persistent_state.selection.factory.'.$name;
            $services->set($factoryId, SelectionFactory::class)
                ->arg('$storage', service($storageId))
                ->arg('$transformer', service($transformerId))
                ->arg('$metadataTransformer', service($metadataTransformerId));

            $services
                ->set('persistent_state.selection.manager.'.$name, SelectionManager::class)
                ->public()
                ->arg('$factory', service($factoryId))
                ->arg('$transformer', service($transformerId))
                ->arg('$loaders', tagged_iterator(AutoTagIdentityLoadersPass::TAG))
                ->arg('$contextResolver', service(ContextResolverInterface::class))
                ->arg('$ttl', $ttl)
                ->tag('persistent_state.selection.manager', ['name' => $name])
            ;
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AutoTagContextKeyResolverPass());
        $container->addCompilerPass(new AutoTagIdentityLoadersPass());
        $container->addCompilerPass(new TraceableManagersPass());
    }
}
