<?php

namespace App\Model\DTO\Product;

use App\Entity\{Product, ProductPrice};
use App\Model\DTO\ProductPrice\CreateProductPriceDto;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

class CreateProductDto
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 1, max: 255)]
        public string $name,

        #[Assert\Type('string')]
        #[Assert\NotNull]
        #[Assert\NotBlank]
        public string $description,

        /** @var CreateProductPriceDto[] */
        #[Assert\Type('array')]
        #[Assert\Valid]
        #[Map(if: false)]
        public array $prices = [],
    ) {
    }

    public function mapPricesTo(Product $product): void
    {
        foreach ($this->prices as $priceDto) {
            $productPrice = new ProductPrice();
            $productPrice->price = $priceDto->price;
            $productPrice->pricePeriod = $priceDto->pricePeriod;
            $product->addPrice($productPrice);
        }
    }
}
