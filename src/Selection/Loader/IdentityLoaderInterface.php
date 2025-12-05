<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Loader;


use Tito10047\PersistentPreferenceBundle\Transformer\ValueTransformerInterface;

interface IdentityLoaderInterface {

	public function loadAllIdentifiers(?ValueTransformerInterface $transformer, mixed $source): array;


	public function getTotalCount(mixed $source): int;

	public function supports(mixed $source):bool;

	public function getCacheKey(mixed $source):string;
}