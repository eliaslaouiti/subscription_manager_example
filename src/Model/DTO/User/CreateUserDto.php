<?php

namespace App\Model\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDto
{
    public function __construct(
        #[Assert\Email]
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 255)]
        public string $firstName,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(min: 2, max: 255)]
        public string $lastName,
    ) {
    }
}
