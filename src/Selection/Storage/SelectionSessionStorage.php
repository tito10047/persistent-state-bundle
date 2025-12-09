<?php

namespace Tito10047\PersistentStateBundle\Selection\Storage;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;


final class SelectionSessionStorage implements SelectionStorageInterface
{
    private const SESSION_PREFIX = '_persistent_selection_';


	public function __construct(
		private readonly RequestStack $requestStack
	) {}

	public function set(string $context, int|array|string $identifier, ?array $metadata): void {
		[$ids, $meta, $mode] = $this->loadContext($context);

		// add identifier if not present (loose check)
		if (!in_array($identifier, $ids, false)) {
			$ids[] = $identifier;
		}
		// persist metadata if provided
		if ($metadata !== null) {
			$key        = $this->metaKey($identifier);
			$meta[$key] = $metadata;
		}

		$this->saveContext($context, $ids, $meta, $mode);
	}

	public function setMultiple(string $context, array $identifiers): void {
		[$ids, $meta, $mode] = $this->loadContext($context);
		foreach ($identifiers as $id) {
			if (!in_array($id, $ids, false)) {
				$ids[] = $id;
			}
		}
		$this->saveContext($context, $ids, $meta, $mode);
	}

	public function remove(string $context, array $identifier): void {
		$this->removeMultiple($context, $identifier);
	}

	public function removeMultiple(string $context, array $identifiers): void {
		[$ids, $meta, $mode] = $this->loadContext($context);
		if ($ids === []) {
			return;
		}
		// remove ids and related metadata
		$remaining = [];
		foreach ($ids as $existing) {
			if (!in_array($existing, $identifiers, false)) {
				$remaining[] = $existing;
			} else {
				unset($meta[$this->metaKey($existing)]);
			}
		}
		$this->saveContext($context, array_values($remaining), $meta, $mode);
	}

	public function clear(string $context): void {
		$this->saveContext($context, [], [], SelectionMode::INCLUDE);
	}

	public function getStored(string $context): array {
		[$ids] = $this->loadContext($context);
		return $ids;
	}

	public function getMetadata(string $context, int|array|string $identifiers): array {
		[, $meta] = $this->loadContext($context);
		$key = $this->metaKey($identifiers);
		return $meta[$key] ?? [];
	}

	public function hasIdentifier(string $context, int|array|string $identifiers): bool {
		[$ids] = $this->loadContext($context);
		return in_array($identifiers, $ids, false);
	}

	public function setMode(string $context, SelectionMode $mode): void {
		[$ids, $meta] = $this->loadContext($context);
		$this->saveContext($context, $ids, $meta, $mode);
	}

	public function getMode(string $context): SelectionMode {
		[, , $mode] = $this->loadContext($context);
		return $mode;
	}

	/**
	 * Internal helpers
	 */
	private function loadContext(string $context): array {
		$session = $this->getSession();
		$key     = self::SESSION_PREFIX . $context;
		$bag     = $session->get($key, null);
		if (!is_array($bag) || !isset($bag['ids'], $bag['meta'], $bag['mode'])) {
			return [[], [], SelectionMode::INCLUDE];
		}
		$mode = $bag['mode'];
		if (!$mode instanceof SelectionMode) {
			$mode = SelectionMode::tryFrom((string) $mode) ?? SelectionMode::INCLUDE;
		}
		return [$bag['ids'], $bag['meta'], $mode];
	}

	private function saveContext(string $context, array $ids, array $meta, SelectionMode $mode): void {
		$session = $this->getSession();
		$key     = self::SESSION_PREFIX . $context;
		$session->set($key, [
			'ids'  => array_values($ids),
			'meta' => $meta,
			'mode' => $mode,
		]);
	}

	private function metaKey(int|array|string $identifier): string {
		if (is_array($identifier)) {
			return 'arr:' . json_encode($identifier, JSON_THROW_ON_ERROR);
		}
		return (string) $identifier;
	}

    private function getSession(): SessionInterface {
        try {
           return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
			throw new SessionNotFoundException('No session found. Make sure to start a session before using the selection service.');
        }
    }
}