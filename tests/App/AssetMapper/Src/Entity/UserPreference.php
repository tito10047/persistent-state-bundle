<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tito10047\PersistentPreferenceBundle\Storage\BasePreference;
use Tito10047\PersistentPreferenceBundle\Storage\PreferenceEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_preferences')]
#[ORM\UniqueConstraint(name: 'uniq_preference_context_key', columns: ['context', 'name'])]
class UserPreference extends BasePreference
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	public function getId(): ?int
	{
		return $this->id;
	}
}