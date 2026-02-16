<?php

namespace App\Model\DTO\ProductPrice;

use App\Enum\ProductPricePeriod;
use Symfony\Component\Validator\Constraints as Assert;

class CreateProductPriceDto
{
    public function __construct(
        #[Assert\Type('int')]
        #[Assert\GreaterThanOrEqual(0)]
        public int $price,

        #[Assert\Type(ProductPricePeriod::class)]
        #[Assert\NotNull]
        #[Assert\NotBlank]
        public ProductPricePeriod $pricePeriod,
    ) {
    }
}
