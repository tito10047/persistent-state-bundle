<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#configuration
 */
return static function (DefinitionConfigurator $definition): void {
    $rootNode = $definition->rootNode();
	$children = $rootNode->children();

	$children
            ->arrayNode('preference')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('managers')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('storage')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->info('The service ID of the storage backend to use (e.g. "@app.storage.doctrine").')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('selection')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('managers')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('storage')
									->defaultValue('@persistent_state.selection.storage.session')
                                    ->cannotBeEmpty()
                                    ->info('The service ID of the storage backend to use (e.g. "@app.storage.doctrine").')
                                ->end()
                                ->scalarNode('transformer')
                                    ->defaultValue('@persistent_state.transformer.scalar')
                                    ->cannotBeEmpty()
                                    ->info('The service ID of the transformer used for identifiers (e.g. "@persistent_state.transformer.scalar").')
                                ->end()
                                ->scalarNode('metadata_transformer')
                                    ->defaultValue('@persistent_state.transformer.array')
                                    ->cannotBeEmpty()
                                    ->info('The service ID of the transformer used for metadata (e.g. "@persistent_state.transformer.array").')
                                ->end()
								->integerNode('ttl')
                                    ->defaultNull()
                                    ->min(0)
                                    ->info('Default TTL (in seconds) for selection sources. If null, sources might not expire or use storage defaults.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


	$children->end();

};
