<?php

namespace App\Model\DTO\ProductPrice;

use App\Enum\ProductPricePeriod;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateProductPriceDto
{
    public function __construct(
        #[Assert\Type('int')]
        #[Assert\GreaterThanOrEqual(0)]
        public ?int $price = null,

        #[Assert\Type(ProductPricePeriod::class)]
        public ?ProductPricePeriod $pricePeriod = null,
    ) {
    }
}
