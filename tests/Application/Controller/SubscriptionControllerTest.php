<?php

namespace App\Tests\Application\Controller;

use App\Enum\ProductPricePeriod;
use App\Factory\{ProductFactory, ProductPriceFactory, SubscriptionFactory, UserFactory};
use App\Tests\Application\Helpers;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\{Request, Response};
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

#[ResetDatabase]
final class SubscriptionControllerTest extends WebTestCase
{
    use Factories;

    private const array SUBSCRIPTION_SCHEMA = ['id', 'productPrice', 'startDate'];
    private const array SUBSCRIPTION_SCHEMA_WITH_END_DATE = ['id', 'productPrice', 'startDate', 'endDate'];

    /******************************************
     * CGET
     ******************************************/

    public function testCGetActionReturnsActiveSubscriptions(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        // Active: no endDate
        SubscriptionFactory::new()->create(['user' => $user, 'productPrice' => $price]);
        // Active: future endDate
        SubscriptionFactory::new()->create([
            'user' => $user,
            'productPrice' => $price,
            'endDate' => new DateTimeImmutable('+1 month'),
        ]);
        // Expired: past endDate
        SubscriptionFactory::new()->create([
            'user' => $user,
            'productPrice' => $price,
            'endDate' => new DateTimeImmutable('-1 day'),
        ]);

        $client->request(Request::METHOD_GET, sprintf('/api/users/%s/subscriptions', $user->id));

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        self::assertCount(2, $decoded);

        foreach ($decoded as $item) {
            self::assertArrayHasKey('id', $item);
            self::assertArrayHasKey('productPrice', $item);
            self::assertArrayHasKey('startDate', $item);
        }
    }

    public function testCGetActionUserNotFound(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/users/bad_user_id/subscriptions');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /******************************************
     * GET
     ******************************************/

    public function testGetActionSuccessful(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $subscription = SubscriptionFactory::new()->create(['user' => $user]);

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/users/%s/subscriptions/%s', $user->id, $subscription->id)
        );

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        Helpers::validateBody($decoded, self::SUBSCRIPTION_SCHEMA);
        self::assertSame($subscription->id, $decoded['id']);
        self::assertArrayNotHasKey('endDate', $decoded);
    }

    public function testGetActionUserNotFound(): void
    {
        $client = self::createClient();

        $subscription = SubscriptionFactory::new()->create();

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/users/%s/subscriptions/%s', 'bad_user_id', $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActionSubscriptionNotFound(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/users/%s/subscriptions/%s', $user->id, 'bad_subscription_id')
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActionSubscriptionBelongsToDifferentUser(): void
    {
        $client = self::createClient();

        $userA = UserFactory::new()->create();
        $userB = UserFactory::new()->create();
        $subscription = SubscriptionFactory::new()->create(['user' => $userB]);

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/users/%s/subscriptions/%s', $userA->id, $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /******************************************
     * POST
     ******************************************/

    public function testPostActionSuccessful(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $body = ['productPriceId' => $price->id];

        $client->request(
            method: Request::METHOD_POST,
            uri: sprintf('/api/users/%s/subscriptions', $user->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::validateBody($decoded, self::SUBSCRIPTION_SCHEMA);
        self::assertNotNull($decoded['id']);
        self::assertNotNull($decoded['startDate']);
        self::assertArrayNotHasKey('endDate', $decoded);

        SubscriptionFactory::assert()->exists(['id' => $decoded['id']]);
    }

    public function testPostActionUserNotFound(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $body = ['productPriceId' => $price->id];

        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/users/bad_user_id/subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostActionProductPriceNotFound(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();

        $body = ['productPriceId' => 'bad_price_id'];

        $client->request(
            method: Request::METHOD_POST,
            uri: sprintf('/api/users/%s/subscriptions', $user->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostActionValidationError(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();

        $body = [];

        $client->request(
            method: Request::METHOD_POST,
            uri: sprintf('/api/users/%s/subscriptions', $user->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPostActionDuplicateActiveSubscription(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        // Create an active subscription
        SubscriptionFactory::new()->create(['user' => $user, 'productPrice' => $price]);

        // Try to subscribe again to the same product price
        $body = ['productPriceId' => $price->id];

        $client->request(
            method: Request::METHOD_POST,
            uri: sprintf('/api/users/%s/subscriptions', $user->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    /******************************************
     * DELETE (unsubscribe)
     ******************************************/

    public function testDeleteActionSuccessful(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $subscription = SubscriptionFactory::new()->create(['user' => $user]);

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/users/%s/subscriptions/%s', $user->id, $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::validateBody($decoded, self::SUBSCRIPTION_SCHEMA_WITH_END_DATE);
        self::assertNotNull($decoded['endDate']);
    }

    public function testDeleteActionUserNotFound(): void
    {
        $client = self::createClient();

        $subscription = SubscriptionFactory::new()->create();

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/users/%s/subscriptions/%s', 'bad_user_id', $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteActionSubscriptionNotFound(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/users/%s/subscriptions/%s', $user->id, 'bad_subscription_id')
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteActionSubscriptionBelongsToDifferentUser(): void
    {
        $client = self::createClient();

        $userA = UserFactory::new()->create();
        $userB = UserFactory::new()->create();
        $subscription = SubscriptionFactory::new()->create(['user' => $userB]);

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/users/%s/subscriptions/%s', $userA->id, $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteActionSetsEndDateBasedOnPeriodMonthly(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product, 'pricePeriod' => ProductPricePeriod::MONTHLY]);
        $subscription = SubscriptionFactory::new()->create(['user' => $user, 'productPrice' => $price]);

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/users/%s/subscriptions/%s', $user->id, $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $decoded = json_decode($client->getResponse()->getContent(), true);

        $endDate = new DateTimeImmutable($decoded['endDate']);
        $billingDay = (int) $subscription->startDate->format('j');
        $daysInEndMonth = (int) $endDate->format('t');

        // endDate day should match billing day (clamped if month is shorter)
        self::assertSame(min($billingDay, $daysInEndMonth), (int) $endDate->format('j'));
        // endDate should be in the future (or today)
        self::assertGreaterThanOrEqual(new DateTimeImmutable('today'), $endDate);
        // endDate should be at midnight (start of billing day)
        self::assertSame('00:00:00', $endDate->format('H:i:s'));
    }

    public function testDeleteActionSetsEndDateBasedOnPeriodYearly(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();
        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product, 'pricePeriod' => ProductPricePeriod::YEARLY]);
        $subscription = SubscriptionFactory::new()->create(['user' => $user, 'productPrice' => $price]);

        $expectedEndDate = $subscription->startDate->modify('+1 year');

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/users/%s/subscriptions/%s', $user->id, $subscription->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $decoded = json_decode($client->getResponse()->getContent(), true);

        self::assertSame($expectedEndDate->format('Y-m-d\TH:i:sP'), $decoded['endDate']);
    }
}
