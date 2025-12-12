<?php

namespace Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class RecordInteger
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestCategory::class)]
    private ?TestCategory $category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

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

    public function getCategory(): ?TestCategory
    {
        return $this->category;
    }

    public function setCategory(?TestCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
