<?php

namespace Tito10047\PersistentStateBundle\Enum;

enum SelectionMode: string
{
    /**
     * Default mode.
     * The stored identifiers represent the items that are explicitly selected.
     */
    case INCLUDE = 'include';

    /**
     * Inverse mode (usually for "Select All" functionality).
     * ALL items in the source are considered selected.
     * The stored identifiers represent the exceptions (items explicitly deselected).
     */
    case EXCLUDE = 'exclude';
}
