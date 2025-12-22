<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class PreferenceEvent extends Event
{
    public function __construct(
        public readonly string $context,
        public readonly string $key,
        public readonly mixed $value,
    ) {
    }
}
