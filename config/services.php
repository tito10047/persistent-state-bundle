<?php

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentPreferenceBundle\Command\DebugPreferenceCommand;
use Tito10047\PersistentPreferenceBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentPreferenceBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentPreferenceBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagValueTransformerPass;
use Tito10047\PersistentPreferenceBundle\PersistentPreferenceBundle;
use Tito10047\PersistentPreferenceBundle\Preference\Service\PreferenceManager;
use Tito10047\PersistentPreferenceBundle\Preference\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceSessionStorage;
use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentPreferenceBundle\Resolver\PersistentContextResolver;
use Tito10047\PersistentPreferenceBundle\Transformer\ArrayValueTransformer;
use Tito10047\PersistentPreferenceBundle\Transformer\ObjectIdValueTransformer;
use Tito10047\PersistentPreferenceBundle\Transformer\ScalarValueTransformer;
use Tito10047\PersistentPreferenceBundle\Twig\PreferenceExtension;
use Tito10047\PersistentPreferenceBundle\Twig\PreferenceRuntime;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * Konfigurácia služieb pre PersistentPreferenceBundle – bez autowire/autoconfigure.
 * Všetko je definované manuálne.
 */
return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();

    $services = $container->services();

	// --- Storage ---
    $services
        ->set('persistent.preference.storage.session',PreferenceSessionStorage::class)
            ->arg('$requestStack', service(RequestStack::class))
		->public()
    ;
    // Alias the interface to our concrete storage service id
    $services->alias(PreferenceStorageInterface::class, 'persistent.preference.storage.session');

    // --- Built-in Resolvers ---
    $services
        ->set(PersistentContextResolver::class)
        ->public()
    ;

    // --- Built-in Value Transformers ---
    $services
        ->set(ScalarValueTransformer::class)
        ->public()
    ;

    // --- Metadata Converters ---
    $services
        ->set('persistent.preference.converter.object_vars', ObjectVarsConverter::class)
		->public()
    ;
    $services->alias(MetadataConverterInterface::class, 'persistent.preference.converter.object_vars');

    // --- PreferenceManager ---
    $services
        ->set('persistent.preference.manager.default', PreferenceManager::class)
            ->public()
            ->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG))
            ->arg('$transformers', tagged_iterator(PersistentPreferenceBundle::TRANSFORMER_TAG))
            ->arg('$storage', service('persistent.preference.storage.session'))
            ->tag('persistent.preference.manager', ['name' => 'default'])
    ;
    $services->alias(PreferenceManagerInterface::class, 'persistent.preference.manager.default');

    // --- Twig Extension ---
    $services
        ->set(PreferenceExtension::class)
            ->public()
            ->tag('twig.extension')
    ;
	foreach([ArrayValueTransformer::class, ScalarValueTransformer::class] as $class){
		$services->set($class)
			->public()
			->tag(PersistentPreferenceBundle::TRANSFORMER_TAG);
	}

    // --- Twig Runtime ---
    $services
        ->set(PreferenceRuntime::class)
            ->public()
            ->arg('$preferenceManager', service(PreferenceManagerInterface::class))
            ->tag('twig.runtime')
    ;

    // --- Console Command ---
    $services
        ->set(DebugPreferenceCommand::class)
            ->public()
            ->arg('$container', service('service_container'))
            ->tag('console.command')
    ;

    // --- Data Collector ---
    // Register only when WebProfiler is installed AND Symfony debug is enabled
    if (class_exists(WebProfilerBundle::class)) {
        $appDebug = in_array($container->env(),['dev','test']);
        if ($appDebug) {
            $services
                ->set(PreferenceDataCollector::class)
                    ->public()
                    ->arg('$storage', service(PreferenceStorageInterface::class))
                    ->tag('data_collector', [
                        'id' => 'app.preference_collector',
                        'template' => 'data_collector/panel.html.twig',
                    ])
            ;

            // Note: decoration of all managers is handled by a CompilerPass (TraceableManagersPass)
        }
    }

};
