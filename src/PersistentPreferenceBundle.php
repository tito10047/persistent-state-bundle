<?php

namespace Tito10047\PersistentPreferenceBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\PersistentPreferenceBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentPreferenceBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\TraceableManagersPass;
use Tito10047\PersistentPreferenceBundle\Preference\Service\PreferenceManager;
use Tito10047\PersistentPreferenceBundle\Selection\Service\SelectionManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class PersistentPreferenceBundle extends AbstractBundle {

	protected string $extensionAlias = 'persistent';
	const TRANSFORMER_TAG = 'persistent_preference.value_transformer';

	public function configure(DefinitionConfigurator $definition): void {
		$definition->import('../config/definition.php');
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		$container->import('../config/services.php');
		$services = $container->services();
		// Default metadata converter service
		$services->set('persistent_preference.converter.object_vars', ObjectVarsConverter::class)
			->alias(MetadataConverterInterface::class, 'persistent_preference.converter.object_vars');


		$configManagers = $config['preference']['managers'] ?? [];
		foreach ($configManagers as $name => $subConfig) {
			$storage = service($subConfig['storage'] ?? '@persistent_preference.storage.session');
			$storage = ltrim($storage, '@');
			$services
				->set('persistent.preference.manager.' . $name, PreferenceManager::class)
				->public()
				->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG))
				->arg('$transformers', tagged_iterator(self::TRANSFORMER_TAG))
				->arg('$storage', service($storage))
				->arg('$dispatcher', service('event_dispatcher'))
				->tag('persistent_preference.manager', ['name' => $name]);
		}

		$configManagers = $config['selection']['managers'] ?? [];
		foreach($configManagers as $name=>$subConfig){
			$storage = service(ltrim($subConfig['storage'], '@'));
			$transformer = service(ltrim($subConfig['transformer'], '@')??null);
			$metadataTransformer = service(ltrim($subConfig['metadata_transformer'], '@')??null);
			$ttl = $subConfig['ttl']??null;
			$services
				->set('persistent.selection.manager.'.$name,SelectionManager::class)
				->public()
				->arg('$storage', $storage)
				->arg('$transformer', $transformer)
				->arg('$metadataTransformer', $metadataTransformer)
				->arg('$loaders', tagged_iterator(AutoTagIdentityLoadersPass::TAG))
				->arg('$ttl', $ttl)
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