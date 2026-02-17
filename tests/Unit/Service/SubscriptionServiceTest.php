<?php

namespace App\Tests\Unit\Service;

use App\Entity\{ProductPrice, Subscription, User};
use App\Enum\ProductPricePeriod;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Clock\MockClock;

final class SubscriptionServiceTest extends TestCase
{
    private EntityManagerInterface&Stub $entityManagerStub;
    private SubscriptionRepository&Stub $subscriptionRepositoryStub;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->entityManagerStub = $this->createStub(EntityManagerInterface::class);
        $this->subscriptionRepositoryStub = $this->createStub(SubscriptionRepository::class);
        $this->clock = new MockClock('2026-02-16');
    }

    /******************************************
     * subscribe()
     ******************************************/

    public function testSubscribeCreatesSubscription(): void
    {
        $user = new User();
        $price = $this->createProductPrice(ProductPricePeriod::MONTHLY);

        $this->subscriptionRepositoryStub
            ->method('findActiveByUserAndProductPrice')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Subscription::class));
        $entityManager->expects(self::once())->method('flush');

        $service = $this->buildService(entityManager: $entityManager);
        $result = $service->subscribe($user, $price);

        self::assertInstanceOf(Subscription::class, $result);
    }

    public function testSubscribeReturnsNullWhenDuplicate(): void
    {
        $user = new User();
        $price = $this->createProductPrice(ProductPricePeriod::MONTHLY);

        $this->subscriptionRepositoryStub
            ->method('findActiveByUserAndProductPrice')
            ->willReturn(new Subscription());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $service = $this->buildService(entityManager: $entityManager);
        $result = $service->subscribe($user, $price);

        self::assertNull($result);
    }

    public function testSubscribeAssignsUserAndProductPrice(): void
    {
        $user = new User();
        $price = $this->createProductPrice(ProductPricePeriod::MONTHLY);

        $this->subscriptionRepositoryStub
            ->method('findActiveByUserAndProductPrice')
            ->willReturn(null);

        $service = $this->buildService();
        $result = $service->subscribe($user, $price);

        self::assertSame($user, $result->user);
        self::assertSame($price, $result->productPrice);
    }

    /******************************************
     * unSubscribe() — yearly
     ******************************************/

    public function testUnSubscribeYearly(): void
    {
        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2025-06-15'),
            ProductPricePeriod::YEARLY,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = $this->buildService(entityManager: $entityManager);
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2026-06-15'), $subscription->endDate);
    }

    public function testUnSubscribeYearlyLeapDay(): void
    {
        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2024-02-29'),
            ProductPricePeriod::YEARLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        // PHP's modify('+1 year') on Feb 29 overflows to March 1 in non-leap years
        self::assertEquals(new DateTimeImmutable('2025-03-01'), $subscription->endDate);
    }

    /******************************************
     * unSubscribe() — monthly (nextBillingDate)
     ******************************************/

    public function testMonthlyBillingDayInFuture(): void
    {
        $this->clock = new MockClock('2026-02-10');

        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2026-01-20'),
            ProductPricePeriod::MONTHLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2026-02-20'), $subscription->endDate);
    }

    public function testMonthlyBillingDayPassed(): void
    {
        $this->clock = new MockClock('2026-02-16');

        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2026-01-05'),
            ProductPricePeriod::MONTHLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2026-03-05'), $subscription->endDate);
    }

    public function testMonthlyBillingDayIsToday(): void
    {
        $this->clock = new MockClock('2026-02-16');

        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2026-01-16'),
            ProductPricePeriod::MONTHLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2026-03-16'), $subscription->endDate);
    }

    public function testMonthlyDay31InFebruary(): void
    {
        $this->clock = new MockClock('2026-02-01');

        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2025-12-31'),
            ProductPricePeriod::MONTHLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2026-02-28'), $subscription->endDate);
    }

    public function testMonthlyDay31NextMonthHas30(): void
    {
        $this->clock = new MockClock('2026-03-31');

        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2025-12-31'),
            ProductPricePeriod::MONTHLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2026-04-30'), $subscription->endDate);
    }

    public function testMonthlyDay29InLeapFebruary(): void
    {
        $this->clock = new MockClock('2028-02-01');

        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2028-01-29'),
            ProductPricePeriod::MONTHLY,
        );

        $service = $this->buildService();
        $service->unSubscribe($subscription);

        self::assertEquals(new DateTimeImmutable('2028-02-29'), $subscription->endDate);
    }

    public function testUnSubscribeMonthlyFlushCalled(): void
    {
        $subscription = $this->createSubscriptionWithStartDate(
            new DateTimeImmutable('2026-01-10'),
            ProductPricePeriod::MONTHLY,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = $this->buildService(entityManager: $entityManager);
        $service->unSubscribe($subscription);
    }

    /******************************************
     * Helpers
     ******************************************/

    private function buildService(
        ?EntityManagerInterface $entityManager = null,
    ): SubscriptionService {
        return new SubscriptionService(
            $entityManager ?? $this->entityManagerStub,
            $this->subscriptionRepositoryStub,
            $this->clock,
        );
    }

    private function createSubscriptionWithStartDate(
        DateTimeImmutable $startDate,
        ProductPricePeriod $period,
    ): Subscription {
        $subscription = new Subscription();

        $ref = new ReflectionProperty(Subscription::class, 'startDate');
        $ref->setValue($subscription, $startDate);

        $price = $this->createProductPrice($period);
        $subscription->productPrice = $price;

        return $subscription;
    }

    private function createProductPrice(ProductPricePeriod $period): ProductPrice
    {
        $price = new ProductPrice();
        $price->pricePeriod = $period;
        $price->price = 999;

        return $price;
    }
}
