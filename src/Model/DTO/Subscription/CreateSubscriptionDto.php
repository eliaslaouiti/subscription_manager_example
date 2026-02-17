<?php

namespace App\Model\DTO\Subscription;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriptionDto
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\NotBlank]
        public string $productPriceId,
    ) {
    }
}
