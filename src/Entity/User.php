<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(columns: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'A user with this email already exists.')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[Groups(['user:read'])]
    public private(set) string $id {
        get {
            return $this->id;
        }
    }

    #[Assert\Email]
    #[Assert\NotNull]
    #[Assert\Length(min: 1, max: 255)]
    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read'])]
    public ?string $email {
        get {
            return $this->email;
        }
        set {
            $this->email = $value;
        }
    }

    #[Assert\NotNull]
    #[Assert\Length(min: 1, max: 255)]
    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    public string $firstName {
        get {
            return $this->firstName;
        }
        set {
            $this->firstName = $value;
        }
    }

    #[Assert\NotNull]
    #[Assert\Length(min: 1, max: 255)]
    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    public string $lastName {
        get {
            return $this->lastName;
        }
        set {
            $this->lastName = $value;
        }
    }

    public function __construct()
    {
        $this->id = Uuid::v4()->toString();
    }
}
