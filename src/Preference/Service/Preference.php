<?php

namespace Tito10047\PersistentPreferenceBundle\Preference\Service;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tito10047\PersistentPreferenceBundle\Event\PreferenceEvent;
use Tito10047\PersistentPreferenceBundle\Event\PreferenceEvents;
use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope;
use Tito10047\PersistentPreferenceBundle\Transformer\ValueTransformerInterface;

/**
 * Default implementation that encapsulates a concrete context and delegates
 * persistence to a StorageInterface with optional value transformers.
 */
final class Preference implements PreferenceInterface
{
    /**
     * @param iterable<ValueTransformerInterface> $transformers
     */
    public function __construct(
        private readonly iterable                   $transformers,
        private readonly string                     $context,
        private readonly PreferenceStorageInterface $storage,
		private readonly EventDispatcherInterface   $dispatcher,
    ) {}

    public function getContext(): string
    {
        return $this->context;
    }

    public function set(string $key, mixed $value): self
    {
		$event = new PreferenceEvent($this->context, $key, $value);
		$this->dispatcher->dispatch($event, PreferenceEvents::PRE_SET);

		if ($event->isPropagationStopped()) {
			return $this;
		}

        $toStore = $this->applyTransform($value);
        $this->storage->set($this->context, $key, $toStore);

		$postEvent = new PreferenceEvent($this->context, $key, $event->value);
		$this->dispatcher->dispatch($postEvent, PreferenceEvents::POST_SET);

        return $this;
    }

    public function import(array $values): self
    {
        $prepared = [];
		$processedEvents = [];

        foreach ($values as $key => $value) {
			$event = new PreferenceEvent($this->context, $key, $value);
			$this->dispatcher->dispatch($event, PreferenceEvents::PRE_SET);

			$processedEvents[$key] = $event;

			if ($event->isPropagationStopped()) {
				return $this;
			}

            $prepared[$key] = $this->applyTransform($value);
        }

        if ($prepared !== []) {
            $this->storage->setMultiple($this->context, $prepared);
        }

		foreach ($processedEvents as $key => $preEvent) {
			$postEvent = new PreferenceEvent($this->context, $key, $preEvent->value);
			$this->dispatcher->dispatch($postEvent, PreferenceEvents::POST_SET);
		}

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->storage->get($this->context, $key, $default);

        // Short-circuit default to avoid trying to reverse-transform default values
        if ($raw === $default) {
            return $raw;
        }

        return $this->applyReverseTransform($raw);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (is_bool($value)) {
            return $value;
        }

        // Normalize common truthy/falsey strings and numbers
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return match ($normalized) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => $default,
            };
        }

        if (is_numeric($value)) {
            return ((int) $value) !== 0;
        }

        return $default;
    }

    public function has(string $key): bool
    {
        return $this->storage->has($this->context, $key);
    }

    public function remove(string $key): self
    {
        $this->storage->remove($this->context, $key);
        return $this;
    }

    public function all(): array
    {
        $rawAll = $this->storage->all($this->context);
        $result = [];
        foreach ($rawAll as $k => $v) {
            $result[$k] = $this->applyReverseTransform($v);
        }

        return $result;
    }

    private function applyTransform(mixed $value): mixed
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($value)) {
                return $transformer->transform($value)->toArray();
            }
        }

		throw  new \RuntimeException("No transformer found for value of type " . gettype($value));
    }

    private function applyReverseTransform(array $value): mixed
    {
		$value = StorableEnvelope::fromArray($value);
        foreach ($this->transformers as $transformer) {
            if ($transformer->supportsReverse($value)) {
                return $transformer->reverseTransform($value);
            }
        }

        throw new \RuntimeException("No reverse transformer found for value of type " . $value->className);
    }
}
