<?php

namespace App\Model\DTO\User;

use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDto
{
    public function __construct(
        #[Map(if: 'strlen')]
        #[Assert\Email]
        #[Assert\Length(min: 1, max: 255)]
        public ?string $email = null,

        #[Map(if: 'strlen')]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 255)]
        public ?string $firstName = null,

        #[Map(if: 'strlen')]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 255)]
        public ?string $lastName = null,
    ) {
    }
}
