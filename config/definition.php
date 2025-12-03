<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#configuration
 */
return static function (DefinitionConfigurator $definition): void {
    $definition
        ->rootNode()
            ->children()
				// Sekcia 1: Resolvery (Ako z objektu spraviť kľúč)
				->arrayNode('context_providers')
					->info('Map entities to context prefixes (e.g. User -> "user_123")')
					->useAttributeAsKey('name')
					->arrayPrototype()
						->children()
							->scalarNode('class')->isRequired()->cannotBeEmpty()->end()
							->scalarNode('prefix')->isRequired()->cannotBeEmpty()->end()
							->scalarNode('identifier_method')->defaultValue('getId')->end()
						->end()
					->end()
				->end()

				// Sekcia 2: Storage (Kde to uložiť)
				->arrayNode('storage')
					->addDefaultsIfNotSet()
					->children()
						->arrayNode('doctrine')
							->canBeEnabled() // Creates an 'enabled' boolean key
							->children()
								->scalarNode('preference_class')
									->info('The Entity class that implements PreferenceEntityInterface')
									->isRequired()
								->end()
								->scalarNode('entity_manager')
									->defaultValue('default')
									->info('The Doctrine Entity Manager to use')
								->end()
							->end()
						->end()
						// Tu môžeme v budúcnosti pridať ->arrayNode('redis')...
					->end()
				->end()
                ->arrayNode('managers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()

                            // ID storage služby; ak nie je zadané, použije sa defaultná storage aliasovaná na StorageInterface
                            ->scalarNode('storage')->defaultNull()->end()

                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end()
    ;
};
