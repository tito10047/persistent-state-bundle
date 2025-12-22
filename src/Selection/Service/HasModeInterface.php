<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Service;

use Tito10047\PersistentStateBundle\Enum\SelectionMode;

interface HasModeInterface
{
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
