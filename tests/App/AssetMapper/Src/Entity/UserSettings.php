<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserSettings {

	#[ORM\Column(name: 'id', type: 'integer', nullable: false)]
	#[ORM\Id]
	#[ORM\GeneratedValue(strategy: 'IDENTITY')]
	private ?int $id = null;

	#[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
	private ?string $name = null;


	public function getId(): ?int {
		return $this->id;
	}

	public function setId(?int $id): self {
		$this->id = $id;
		return $this;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function setName(?string $name): self {
		$this->name = $name;
		return $this;
	}

}