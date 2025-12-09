<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Definícia rout pre PersistentSelectionBundle.
 *
 * @link https://symfony.com/doc/current/bundles/best_practices.html#routing
 */
return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('persistent_selection_toggle', '/_persistent-state-selection/toggle')
            ->controller([SelectController::class, 'rowSelectorToggle'])
            ->methods(['GET'])
    ;

    // Označiť/odznačiť všetky riadky podľa kľúča
    $routes
        ->add('persistent_selection_select_all', '/_persistent-state-selection/select-all')
            ->controller([SelectController::class, 'rowSelectorSelectAll'])
            ->methods(['GET'])
    ;

    $routes
        ->add('persistent_selection_select_range', '/_persistent-state-selection/select-range')
            ->controller([SelectController::class, 'rowSelectorSelectRange'])
            ->methods(['POST'])
    ;
};
