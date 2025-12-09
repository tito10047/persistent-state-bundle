<?php

namespace Tito10047\PersistentStateBundle\Selection\Service;

use Tito10047\PersistentStateBundle\Selection\Loader\IdentityLoaderInterface;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionStorageInterface;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

final class SelectionManager implements SelectionManagerInterface {

	public function __construct(
		private readonly SelectionStorageInterface $storage,
		private readonly ValueTransformerInterface $transformer,
		private readonly ValueTransformerInterface $metadataTransformer,
		/** @var IdentityLoaderInterface[] */
		private readonly iterable                      $loaders,
		private readonly ?string $ttl,
	) { }

	public function registerSelection(string $namespace, mixed $source, int|\DateInterval|null $ttl = null): SelectionInterface {
		$loader = $this->findLoader($source);

		$selection = new Selection(
			$namespace,
			$this->storage,
			$this->transformer,
			$this->metadataTransformer,
		);

		foreach ($source as $item) {
			if (!$this->transformer->supports($item)) {
				throw new \InvalidArgumentException(sprintf('Item of type "%s" is not supported.', gettype($item)));
			}
		}
		$cacheKey = $loader->getCacheKey($source);
		if (!$selection->hasSource($cacheKey)) {
			$selection->registerSource($cacheKey,
				$loader->loadAllIdentifiers($this->transformer, $source),
				$ttl?? $this->ttl
			);
		}

		return $selection;
	}

	public function getSelection(string $namespace, mixed $owner = null): SelectionInterface {
		return new Selection($namespace, $this->storage, $this->transformer, $this->metadataTransformer);
	}

	private function findLoader(mixed $source): IdentityLoaderInterface {
		$loader = null;
		foreach ($this->loaders as $_loader) {
			if ($_loader->supports($source)) {
				$loader = $_loader;
				break;
			}
		}
		if ($loader === null) {
			throw new \InvalidArgumentException('No suitable loader found for the given source.');
		}
		return $loader;
	}


}