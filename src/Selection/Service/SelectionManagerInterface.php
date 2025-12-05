<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Service;

interface SelectionManagerInterface
{

	public function registerSelection(string $namespace, mixed $source, int|\DateInterval|null $ttl = null): SelectionInterface;
	public function getSelection(string $namespace, mixed $owner = null): SelectionInterface;
}