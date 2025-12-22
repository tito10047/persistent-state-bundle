<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Preference\Storage;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PreferenceSessionStorage implements PreferenceStorageInterface
{
    private const SESSION_PREFIX = '_persistent_';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function get(string $context, string $key, mixed $default = null): mixed
    {
        $session = $this->getSession();
        if (!$session) {
            return $default;
        }

        $bucket = $session->get($this->contextKey($context), []);

        return array_key_exists($key, $bucket) ? $bucket[$key] : $default;
    }

    public function set(string $context, string $key, mixed $value): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $bucket = $session->get($this->contextKey($context), []);
        $bucket[$key] = $value;
        $session->set($this->contextKey($context), $bucket);
    }

    public function setMultiple(string $context, array $values): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $bucket = $session->get($this->contextKey($context), []);
        // overwrite existing keys with provided values
        $bucket = array_merge($bucket, $values);
        $session->set($this->contextKey($context), $bucket);
    }

    public function remove(string $context, string $key): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $bucket = $session->get($this->contextKey($context), []);
        if (array_key_exists($key, $bucket)) {
            unset($bucket[$key]);
            $session->set($this->contextKey($context), $bucket);
        }
    }

    public function has(string $context, string $key): bool
    {
        $session = $this->getSession();
        if (!$session) {
            return false;
        }

        $bucket = $session->get($this->contextKey($context), []);

        return array_key_exists($key, $bucket);
    }

    public function all(string $context): array
    {
        $session = $this->getSession();
        if (!$session) {
            return [];
        }

        $bucket = $session->get($this->contextKey($context), []);
        if (!is_array($bucket)) {
            return [];
        }

        return $bucket;
    }

    private function contextKey(string $context): string
    {
        return self::SESSION_PREFIX.$context;
    }

    private function getSession(): ?SessionInterface
    {
        // Prefer the session from the current request. If there is no current request, no session.
        $request = $this->requestStack->getCurrentRequest();

        return $request?->getSession();
    }
}
