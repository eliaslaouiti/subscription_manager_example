<?php

namespace App\Model\DTO\Product;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProductDto
{
    public function __construct(
        #[Assert\Type('string')]
        #[Assert\Length(min: 1, max: 255)]
        public ?string $name = null,

        #[Assert\Type('string')]
        public ?string $description = null,
    ) {
    }
}
