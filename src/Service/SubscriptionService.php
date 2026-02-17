<?php

namespace App\Service;

use App\Entity\{ProductPrice, Subscription, User};
use App\Enum\ProductPricePeriod;
use App\Repository\SubscriptionRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

readonly class SubscriptionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository,
        private ClockInterface $clock,
    ) {
    }

    public function subscribe(User $user, ProductPrice $price): ?Subscription
    {
        $existing = $this->subscriptionRepository->findActiveByUserAndProductPrice($user->id, $price->id);

        if (null !== $existing) {
            return null;
        }

        $subscription = new Subscription();
        $subscription->user = $user;
        $subscription->productPrice = $price;

        $user->addSubscription($subscription);
        $price->addSubscription($subscription);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    public function unSubscribe(Subscription $subscription): void
    {
        $period = $subscription->productPrice->pricePeriod;

        if (ProductPricePeriod::YEARLY === $period) {
            $subscription->endDate = $subscription->startDate->modify('+1 year');
        } else {
            $subscription->endDate = $this->nextBillingDate($subscription->startDate);
        }

        $this->entityManager->flush();
    }

    private function nextBillingDate(DateTimeImmutable $startDate): DateTimeImmutable
    {
        $today = DateTimeImmutable::createFromInterface($this->clock->now())->setTime(0, 0);
        $billingDay = (int) $startDate->format('j');

        $daysInMonth = (int) $today->format('t');
        $day = min($billingDay, $daysInMonth);
        $candidate = $today->setDate((int) $today->format('Y'), (int) $today->format('n'), $day);

        if ($candidate > $today) {
            return $candidate;
        }

        $nextMonth = $today->modify('first day of next month');
        $daysInNextMonth = (int) $nextMonth->format('t');
        $day = min($billingDay, $daysInNextMonth);

        return $nextMonth->setDate((int) $nextMonth->format('Y'), (int) $nextMonth->format('n'), $day);
    }
}
