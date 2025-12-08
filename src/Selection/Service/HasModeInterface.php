<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Service;


use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;

interface HasModeInterface {
    /**
     * Sets how the selection interprets stored identifiers.
     *
     * INCLUDE: stored IDs are the selected ones.
     * EXCLUDE: stored IDs are exclusions (everything else is considered selected).
     */
    public function setMode(SelectionMode $mode): void;

    /**
     * Returns the current selection interpretation mode.
     */
    public function getMode(): SelectionMode;
}