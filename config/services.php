<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentPreferenceBundle\Converter\MetadataConverterInterface;
use Tito10047\PersistentPreferenceBundle\Converter\ObjectVarsConverter;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManager;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Storage\SessionStorage;
use Tito10047\PersistentPreferenceBundle\Storage\StorageInterface;
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
        ->set('persistent_preference.storage.session',SessionStorage::class)
            ->arg('$requestStack', service(RequestStack::class))
    ;
    $services->alias(StorageInterface::class, SessionStorage::class);

    // --- Metadata Converters ---
    $services
        ->set('persistent_preference.converter.object_vars', ObjectVarsConverter::class)
		->public()
    ;
    $services->alias(MetadataConverterInterface::class, 'persistent_preference.converter.object_vars');

    // --- PreferenceManager ---
    $services
        ->set('persistent_preference.manager.default',PreferenceManager::class)
		->public()
            ->arg('$storage', service('persistent_preference.storage.session'))
            ->arg('$resolvers', tagged_iterator(AutoTagContextKeyResolverPass::TAG))
		->arg('$metadataConverter', service('persistent_preference.converter.object_vars'))
		->arg('$ttl', null)
            ->tag('persistent_preference.manager', ['name' => 'default'])
		->alias(PreferenceManagerInterface::class, 'persistent_preference.manager.default')
    ;

};
