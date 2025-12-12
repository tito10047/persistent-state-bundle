<?php

namespace Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Company
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $uuid = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    public function getUuid(): ?int
    {
        return $this->uuid;
    }

    public function setUuid(?int $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
