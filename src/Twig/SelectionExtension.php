<?php

namespace Tito10047\PersistentStateBundle\Twig;

use Tito10047\PersistentStateBundle\PersistentStateBundle;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class SelectionExtension extends AbstractExtension implements GlobalsInterface
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('persistent_selection_is_selected', [SelectionRuntime::class, 'isSelected']),
            new TwigFunction('persistent_selection_is_selected_all', [SelectionRuntime::class, 'isSelectedAll']),
            new TwigFunction('persistent_selection_row_selector', [SelectionRuntime::class, 'rowSelector'], ['is_safe' => ['html']]),
            new TwigFunction('persistent_selection_row_selector_all', [SelectionRuntime::class, 'rowSelectorAll'], ['is_safe' => ['html']]),
            new TwigFunction('persistent_selection_total', [SelectionRuntime::class, 'getTotal']),
            new TwigFunction('persistent_selection_count', [SelectionRuntime::class, 'getSelectedCount']),
            new TwigFunction('persistent_selection_stimulus_controller', [SelectionRuntime::class, 'getStimulusController'], ['is_safe' => ['html']]),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'persistent_selection_stimulus_controller_name' => PersistentStateBundle::STIMULUS_CONTROLLER,
        ];
    }
}
