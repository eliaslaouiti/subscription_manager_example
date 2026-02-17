<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
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

    /**
     * @var Collection<int, Subscription>
     */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['user:read'])]
    public private(set) Collection $subscriptions {
        get {
            return $this->subscriptions;
        }
    }

    public function __construct()
    {
        $this->id = Uuid::v4()->toString();
        $this->subscriptions = new ArrayCollection();
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->user = $this;
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            if ($subscription->user === $this) {
                $subscription->user = null;
            }
        }

        return $this;
    }
}
