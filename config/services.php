<?php

use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tito10047\PersistentStateBundle\Command\DebugPreferenceCommand;
use Tito10047\PersistentStateBundle\Controller\SelectController;
use Tito10047\PersistentStateBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentStateBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentStateBundle\DependencyInjection\Compiler\AutoTagIdentityLoadersPass;
use Tito10047\PersistentStateBundle\DependencyInjection\Compiler\AutoTagValueTransformerPass;
use Tito10047\PersistentStateBundle\PersistentStateBundle;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManager;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManagerInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceSessionStorage;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentStateBundle\Selection\Loader\ArrayLoader;
use Tito10047\PersistentStateBundle\Selection\Loader\DoctrineCollectionLoader;
use Tito10047\PersistentStateBundle\Selection\Loader\DoctrineQueryBuilderLoader;
use Tito10047\PersistentStateBundle\Selection\Loader\DoctrineQueryLoader;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManager;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionSessionStorage;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionStorageInterface;
use Tito10047\PersistentStateBundle\Transformer\ArrayValueTransformer;
use Tito10047\PersistentStateBundle\Transformer\ScalarValueTransformer;
use Tito10047\PersistentStateBundle\Twig\PreferenceExtension;
use Tito10047\PersistentStateBundle\Twig\PreferenceRuntime;
use Tito10047\PersistentStateBundle\Twig\SelectionExtension;
use Tito10047\PersistentStateBundle\Twig\SelectionRuntime;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * Konfigurácia služieb pre PersistentStateBundle – bez autowire/autoconfigure.
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
            ->arg('$transformers', tagged_iterator(PersistentStateBundle::TRANSFORMER_TAG))
            ->arg('$storage', service('persistent.preference.storage.session'))
            ->tag('persistent.preference.manager', ['name' => 'default'])
    ;
    $services->alias(PreferenceManagerInterface::class, 'persistent.preference.manager.default');

	// --- SelectionManager ---
	$services
		->set('persistent.selection.manager.default', SelectionManager::class)
		->public()
		->arg('$storage', service('persistent.selection.storage.session'))
		->arg('$transformer',  service('persistent.transformer.scalar'))
		->arg('$metadataTransformer', service('persistent.transformer.array'))
		->arg('$loaders', tagged_iterator(AutoTagIdentityLoadersPass::TAG))
		->arg('$ttl', null)
		->tag('persistent.selection.manager', ['name' => 'default']);
	$services->alias(SelectionManagerInterface::class, 'persistent.selection.manager.default');


	// --- Twig Extension ---
    $services
        ->set(PreferenceExtension::class)
            ->public()
            ->tag('twig.extension')
    ;

	// --- Built-in Value Transformers ---
	$services->set("persistent.transformer.array",ArrayValueTransformer::class)
		->public()
		->tag(PersistentStateBundle::TRANSFORMER_TAG);
	$services->set("persistent.transformer.scalar",ScalarValueTransformer::class)
		->public()
		->tag(PersistentStateBundle::TRANSFORMER_TAG);

    // --- Twig Runtime ---
    $services
        ->set(PreferenceRuntime::class)
            ->public()
            ->arg('$preferenceManager', service(PreferenceManagerInterface::class))
            ->tag('twig.runtime')
    ;
// --- Loadery ---
	$services
		->set(ArrayLoader::class)
		->tag(AutoTagIdentityLoadersPass::TAG)
	;

	$services
		->set(DoctrineCollectionLoader::class)
		->tag(AutoTagIdentityLoadersPass::TAG)
	;

	$services
		->set(DoctrineQueryLoader::class)
		->tag(AutoTagIdentityLoadersPass::TAG)
	;

	$services
		->set(DoctrineQueryBuilderLoader::class)
		->tag(AutoTagIdentityLoadersPass::TAG)
	;
    // --- Console Command ---
    $services
        ->set(DebugPreferenceCommand::class)
            ->public()
            ->arg('$container', service('service_container'))
            ->tag('console.command')
    ;
	$services
		->set('persistent.selection.storage.session', SelectionSessionStorage::class)
		->arg('$requestStack', service(RequestStack::class))
	;
	$services->alias(SelectionStorageInterface::class, SelectionSessionStorage::class);

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
	// --- Controllers ---
	$services
		->set(SelectController::class)
		->public()
		->arg('$selectionManagers', tagged_iterator('persistent.selection.manager', 'name'));

	// --- Twig integration ---
	$services
		->set(SelectionExtension::class)
		->tag('twig.extension')
	;

	$services
		->set(SelectionRuntime::class)
		->arg('$selectionManagers', tagged_iterator('persistent.selection.manager', 'name'))
		->arg('$router', service(UrlGeneratorInterface::class))
		->tag('twig.runtime')
	;
};
