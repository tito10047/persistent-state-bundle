<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Service;

interface SelectionFactoryInterface
{
    public function create(string $namespace): SelectionInterface;
}
