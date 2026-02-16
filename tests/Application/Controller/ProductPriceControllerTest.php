<?php

namespace App\Tests\Application\Controller;

use App\Factory\{ProductFactory, ProductPriceFactory};
use App\Tests\Application\Helpers;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\{Request, Response};
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

#[ResetDatabase]
final class ProductPriceControllerTest extends WebTestCase
{
    use Factories;

    private const array PRICE_SCHEMA = ['id', 'price', 'pricePeriod', 'subscriptions'];

    /******************************************
     * CGET
     ******************************************/

    public function testCGetAction(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        ProductPriceFactory::new()->create(['product' => $product]);
        ProductPriceFactory::new()->create(['product' => $product]);
        ProductPriceFactory::new()->create(['product' => $product]);

        $client->request(Request::METHOD_GET, sprintf('/api/products/%s/prices', $product->id));

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        self::assertCount(3, $decoded);

        foreach ($decoded as $item) {
            Helpers::validateBody($item, self::PRICE_SCHEMA);
        }
    }

    public function testCGetActionProductNotFound(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/products/bad_product_id/prices');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCGetActionReturnsOnlyPricesForProduct(): void
    {
        $client = self::createClient();

        $product1 = ProductFactory::new()->create();
        $product2 = ProductFactory::new()->create();
        ProductPriceFactory::new()->create(['product' => $product1]);
        ProductPriceFactory::new()->create(['product' => $product1]);
        ProductPriceFactory::new()->create(['product' => $product2]);

        $client->request(Request::METHOD_GET, sprintf('/api/products/%s/prices', $product1->id));

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        self::assertCount(2, $decoded);
    }

    /******************************************
     * GET
     ******************************************/

    public function testGetActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/products/%s/prices/%s', $product->id, $price->id)
        );

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        Helpers::validateBody($decoded, self::PRICE_SCHEMA);
        self::assertSame($price->id, $decoded['id']);
        self::assertSame($price->price, $decoded['price']);
        self::assertSame($price->pricePeriod->value, $decoded['pricePeriod']);
    }

    public function testGetActionProductNotFound(): void
    {
        $client = self::createClient();

        $price = ProductPriceFactory::new()->create();

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/products/%s/prices/%s', 'bad_product_id', $price->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActionPriceNotFound(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/products/%s/prices/%s', $product->id, 'bad_price_id')
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActionPriceBelongsToDifferentProduct(): void
    {
        $client = self::createClient();

        $productA = ProductFactory::new()->create();
        $productB = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $productB]);

        $client->request(
            Request::METHOD_GET,
            sprintf('/api/products/%s/prices/%s', $productA->id, $price->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /******************************************
     * POST
     ******************************************/

    public function testPostActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();

        $body = [
            'price' => 999,
            'pricePeriod' => 'monthly',
        ];

        $client->request(
            method: Request::METHOD_POST,
            uri: sprintf('/api/products/%s/prices', $product->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::validateBody($decoded, self::PRICE_SCHEMA);
        self::assertNotNull($decoded['id']);
        self::assertSame(999, $decoded['price']);
        self::assertSame('monthly', $decoded['pricePeriod']);

        ProductPriceFactory::assert()->exists(['id' => $decoded['id']]);
    }

    public function testPostActionProductNotFound(): void
    {
        $client = self::createClient();

        $body = [
            'price' => 999,
            'pricePeriod' => 'monthly',
        ];

        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/products/bad_product_id/prices',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostActionWithValidationError(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();

        $body = [
            'price' => -5,
            'pricePeriod' => 'monthly',
        ];

        $client->request(
            method: Request::METHOD_POST,
            uri: sprintf('/api/products/%s/prices', $product->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /******************************************
     * PATCH
     ******************************************/

    public function testPatchActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product, 'price' => 1000]);

        $body = ['price' => 2500];

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s/prices/%s', $product->id, $price->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::validateBody($decoded, self::PRICE_SCHEMA);
        self::assertSame($price->id, $decoded['id']);
        self::assertSame(2500, $decoded['price']);
    }

    public function testPatchActionProductNotFound(): void
    {
        $client = self::createClient();

        $price = ProductPriceFactory::new()->create();

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s/prices/%s', 'bad_product_id', $price->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['price' => 500], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatchActionPriceNotFound(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s/prices/%s', $product->id, 'bad_price_id'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['price' => 500], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatchActionPriceBelongsToDifferentProduct(): void
    {
        $client = self::createClient();

        $productA = ProductFactory::new()->create();
        $productB = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $productB]);

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s/prices/%s', $productA->id, $price->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['price' => 500], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatchActionWithValidationError(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $body = ['price' => -10];

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s/prices/%s', $product->id, $price->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /******************************************
     * DELETE
     ******************************************/

    public function testDeleteActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/products/%s/prices/%s', $product->id, $price->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        ProductPriceFactory::assert()->notExists(['id' => $price->id]);
        ProductFactory::assert()->exists(['id' => $product->id]);
    }

    public function testDeleteActionProductNotFound(): void
    {
        $client = self::createClient();

        $price = ProductPriceFactory::new()->create();

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/products/%s/prices/%s', 'bad_product_id', $price->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteActionPriceNotFound(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/products/%s/prices/%s', $product->id, 'bad_price_id')
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteActionPriceBelongsToDifferentProduct(): void
    {
        $client = self::createClient();

        $productA = ProductFactory::new()->create();
        $productB = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $productB]);

        $client->request(
            Request::METHOD_DELETE,
            sprintf('/api/products/%s/prices/%s', $productA->id, $price->id)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        ProductPriceFactory::assert()->exists(['id' => $price->id]);
    }
}
