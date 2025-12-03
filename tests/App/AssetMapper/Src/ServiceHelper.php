<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Tito10047\PersistentPreferenceBundle\DependencyInjection\Compiler\AutoTagContextKeyResolverPass;
use Tito10047\PersistentPreferenceBundle\Resolver\ContextKeyResolverInterface;

class ServiceHelper {

	/**
	 * @param ContextKeyResolverInterface[] $resolvers
	 */
	public function __construct(
		#[Autowire(service:AutoTagContextKeyResolverPass::TAG)]
		public readonly iterable $resolvers,
	) { }
}