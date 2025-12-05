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
									->defaultValue('@persistent.selection.storage.session')
                                    ->cannotBeEmpty()
                                    ->info('The service ID of the storage backend to use (e.g. "@app.storage.doctrine").')
                                ->end()
                                ->scalarNode('transformer')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->info('')
                                ->end()
                                ->scalarNode('metadata_transformer')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->info('')
                                ->end()
								->integerNode('ttl')->defaultNull()->min(0)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


	$children->end();

};
