<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Service;

use Tito10047\PersistentStateBundle\Selection\Storage\SelectionStorageInterface;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

final readonly class SelectionFactory implements SelectionFactoryInterface
{
    public function __construct(
        private SelectionStorageInterface $storage,
        private ValueTransformerInterface $transformer,
        private ValueTransformerInterface $metadataTransformer,
    ) {
    }

    public function create(string $namespace): SelectionInterface
    {
        return new Selection(
            $namespace,
            $this->storage,
            $this->transformer,
            $this->metadataTransformer,
        );
    }
}
