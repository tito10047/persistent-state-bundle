<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Tito10047\PersistentPreferenceBundle\Resolver\ContextKeyResolverInterface;
use Tito10047\PersistentPreferenceBundle\Selection\Service\SelectionManagerInterface;

class ServiceHelper {

	/**
	 * @param ContextKeyResolverInterface[] $resolvers
	 * @param AutoTagValueTransformerPass[] $transformers
	 */
	public function __construct(
		private readonly iterable                 $resolvers,
		private readonly iterable                 $transformers,
		#[Autowire(service: 'persistent.selection.manager.array')]
		public readonly SelectionManagerInterface $arraySelectionManager,
	) { }
}