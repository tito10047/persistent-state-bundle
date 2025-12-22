<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Preference\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

final readonly class PreferenceFactory implements PreferenceFactoryInterface
{
    /**
     * @param iterable<ValueTransformerInterface> $transformers
     */
    public function __construct(
        private iterable $transformers,
        private PreferenceStorageInterface $storage,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function create(string $context): PreferenceInterface
    {
        return new Preference($this->transformers, $context, $this->storage, $this->dispatcher);
    }
}
