<?php

namespace Tito10047\PersistentPreferenceBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\PersistentPreferenceBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentPreferenceBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagIdentifierNormalizersPass;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagValueTransformerPass;
use Tito10047\PersistentPreferenceBundle\Resolver\ObjectContextResolver;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServiceConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class PersistentPreferenceBundle extends AbstractBundle {

	protected string $extensionAlias = 'persistent_preference';

	public function configure(DefinitionConfigurator $definition): void {
		$definition->import('../config/definition.php');
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		$container->import('../config/services.php');
		$services = $container->services();
		// Default metadata converter service
		$services->set('persistent_preference.converter.object_vars', ObjectVarsConverter::class)
			->alias(MetadataConverterInterface::class, 'persistent_preference.converter.object_vars');


		// 1) Register configured context providers as services
		$contextProviders = $config['context_providers'] ?? [];
		foreach ($contextProviders as $name => $providerCfg) {
			$serviceId = 'persistent_preference.context_resolver.' . $name;
			/** @var ServiceConfigurator $def */
			$def = $services
				->set($serviceId, ObjectContextResolver::class)
				->public()
			;
			$def->arg('$class', $providerCfg['class']);
			$def->arg('$prefix', $providerCfg['prefix']);
			$def->arg('$identifierMethod', $providerCfg['identifier_method'] ?? 'getId');

			// Manually tag as a context key resolver so it's injected into managers
			$def->tag(AutoTagContextKeyResolverPass::TAG);
		}

		// 2) Register managers
		$configManagers = $config['managers'] ?? [];
		foreach ($configManagers as $name => $subConfig) {
			$storage = service($subConfig['storage'] ?? 'persistent_preference.storage.session');
			$services
				->set('persistent_preference.manager.' . $name, PreferenceManager::class)
				->public()
				->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG))
				->arg('$transformers', tagged_iterator(AutoTagValueTransformerPass::TAG))
				->arg('$storage', $storage)
				->tag('persistent_preference.manager', ['name' => $name]);
		}
	}

	public function build(ContainerBuilder $container): void {
		parent::build($container);
		$container->addCompilerPass(new AutoTagContextKeyResolverPass());
		$container->addCompilerPass(new AutoTagValueTransformerPass());
	}
}