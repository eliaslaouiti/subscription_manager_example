<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Product with this name already exists.')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[Groups(['product:read'])]
    public private(set) string $id {
        get {
            return $this->id;
        }
    }

    #[Assert\Type('string')]
    #[Assert\Length(min: 1, max: 255)]
    #[ORM\Column(length: 255)]
    #[Groups(['product:read'])]
    public string $name {
        get {
            return $this->name;
        }
        set {
            $this->name = $value;
        }
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read'])]
    public string $description {
        get {
            return $this->description;
        }
        set {
            $this->description = $value;
        }
    }

    /**
     * @var Collection<int, ProductPrice>
     */
    #[ORM\OneToMany(targetEntity: ProductPrice::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['product:read'])]
    public private(set) Collection $prices {
        get {
            return $this->prices;
        }
    }

    public function __construct()
    {
        $this->id = Uuid::v4()->toString();
        $this->prices = new ArrayCollection();
    }

    public function addPrice(ProductPrice $price): static
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->product = $this;
        }

        return $this;
    }

    public function removePrice(ProductPrice $price): static
    {
        if ($this->prices->removeElement($price)) {
            if ($price->product === $this) {
                $price->product = null;
            }
        }

        return $this;
    }
}
