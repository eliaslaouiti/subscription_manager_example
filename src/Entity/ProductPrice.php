<?php

namespace App\Entity;

use App\Enum\ProductPricePeriod;
use App\Repository\ProductPriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductPriceRepository::class)]
class ProductPrice
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[Groups(['product:read', 'product_price:read'])]
    public private(set) string $id {
        get {
            return $this->id;
        }
    }

    #[Assert\NotNull]
    #[Assert\Type(Product::class)]
    #[ORM\ManyToOne(inversedBy: 'prices')]
    #[ORM\JoinColumn(nullable: false)]
    public ?Product $product {
        get {
            return $this->product;
        }
        set {
            $this->product = $value;
        }
    }

    #[Assert\NotNull]
    #[Assert\Type(ProductPricePeriod::class)]
    #[ORM\Column(enumType: ProductPricePeriod::class)]
    #[Groups(['product:read', 'product_price:read'])]
    public ProductPricePeriod $pricePeriod {
        get {
            return $this->pricePeriod;
        }
        set {
            $this->pricePeriod = $value;
        }
    }

    #[Assert\NotNull]
    #[Assert\Type('int')]
    #[Assert\GreaterThanOrEqual(0)]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['product:read', 'product_price:read'])]
    public int $price {
        get {
            return $this->price;
        }
        set {
            $this->price = $value;
        }
    }

    public function __construct()
    {
        $this->id = Uuid::v4()->toString();
    }
}
