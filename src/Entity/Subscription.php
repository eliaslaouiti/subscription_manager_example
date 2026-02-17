<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    #[Groups(['subscription:read'])]
    public private(set) string $id {
        get {
            return $this->id;
        }
    }

    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    public ?User $user {
        get {
            return $this->user;
        }
        set {
            $this->user = $value;
        }
    }

    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subscription:read'])]
    public ?ProductPrice $productPrice {
        get {
            return $this->productPrice;
        }
        set {
            $this->productPrice = $value;
        }
    }

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['subscription:read'])]
    public private(set) DateTimeImmutable $startDate {
        get {
            return $this->startDate;
        }
        set {
            $this->startDate = $value;
        }
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['subscription:read'])]
    public ?DateTimeImmutable $endDate {
        get {
            return $this->endDate;
        }
        set {
            $this->endDate = $value;
        }
    }

    public function __construct()
    {
        $this->id = Uuid::v4()->toString();
        $this->startDate = new DateTimeImmutable();
    }
}
