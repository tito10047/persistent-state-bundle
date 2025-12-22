<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Preference\Service;

interface PreferenceFactoryInterface
{
    public function create(string $context): PreferenceInterface;
}
