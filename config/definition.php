<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#configuration
 */
return static function (DefinitionConfigurator $definition): void {
	$addManagerSection = static function (\Symfony\Component\Config\Definition\Builder\NodeBuilder $nodeBuilder, string $sectionName): void
    {
        $nodeBuilder
            ->arrayNode($sectionName)
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
            ->end();
    };
    $rootNode = $definition->rootNode();
	$children = $rootNode->children();

	$addManagerSection($children, 'preference');

	$addManagerSection($children, 'selection');

	$children->end();

};
