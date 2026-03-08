<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tito10047\PersistentStateBundle\Selection\Storage\BaseSelection;

#[ORM\Entity]
#[ORM\Table(name: 'user_selections')]
class UserSelection extends BaseSelection
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
