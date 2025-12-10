<?php

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
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManager;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class PersistentStateBundle extends AbstractBundle {
	public const STIMULUS_CONTROLLER='tito10047--persistent-state-bundle--selection';

	protected string $extensionAlias = 'persistent_state';
	const TRANSFORMER_TAG = 'persistent_state.preference.value_transformer';

	public function configure(DefinitionConfigurator $definition): void {
		$definition->import('../config/definition.php');
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		$container->import('../config/services.php');
		$services = $container->services();
		// Default metadata converter service
		$services->set('persistent_state.preference.converter.object_vars', ObjectVarsConverter::class)
			->alias(MetadataConverterInterface::class, 'persistent_state.preference.converter.object_vars');


		$configManagers = $config['preference']['managers'] ?? [];
		foreach ($configManagers as $name => $subConfig) {
			$storage = service($subConfig['storage'] ?? '@persistent_state.preference.storage.session');
			$storage = ltrim($storage, '@');
			$services
				->set('persistent_state.preference.manager.' . $name, PreferenceManager::class)
				->public()
				->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG))
				->arg('$transformers', tagged_iterator(self::TRANSFORMER_TAG))
				->arg('$storage', service($storage))
				->arg('$dispatcher', service('event_dispatcher'))
				->tag('persistent_state.preference.manager', ['name' => $name]);
		}

		$configManagers = $config['selection']['managers'] ?? [];
		foreach($configManagers as $name=>$subConfig){
			$storage = service(ltrim($subConfig['storage'], '@'));
			$transformer = service(ltrim($subConfig['transformer'], '@'));
			$metadataTransformer = service(ltrim($subConfig['metadata_transformer'], '@'));
			$ttl = $subConfig['ttl']??null;
			$services
				->set('persistent_state.selection.manager.'.$name,SelectionManager::class)
				->public()
				->arg('$storage', $storage)
				->arg('$transformer', $transformer)
				->arg('$metadataTransformer', $metadataTransformer)
				->arg('$loaders', tagged_iterator(AutoTagIdentityLoadersPass::TAG))
				->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG))
				->arg('$ttl', $ttl)
				->tag('persistent_state.selection.manager', ['name' => $name])
			;
		}
	}

    public function build(ContainerBuilder $container): void {
        parent::build($container);
        $container->addCompilerPass(new AutoTagContextKeyResolverPass());
        $container->addCompilerPass(new AutoTagIdentityLoadersPass());
        $container->addCompilerPass(new TraceableManagersPass());
    }
}