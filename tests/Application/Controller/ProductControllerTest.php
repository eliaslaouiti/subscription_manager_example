<?php

namespace App\Tests\Application\Controller;

use App\Factory\{ProductFactory, ProductPriceFactory};
use App\Tests\Application\Helpers;
use Faker\{Factory, Generator as FakerGenerator};
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\{Request, Response};
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

#[ResetDatabase]
final class ProductControllerTest extends WebTestCase
{
    use Factories;

    protected FakerGenerator $faker;

    public function setUp(): void
    {
        $this->faker = Factory::create();
    }

    /******************************************
     * CGET
     ******************************************/

    public function testCGetAction(): void
    {
        $client = self::createClient();

        [$product1, $product2, $product3] = ProductFactory::createMany(3);
        ProductPriceFactory::new()->create(['product' => $product1]);
        ProductPriceFactory::new()->create(['product' => $product2]);
        ProductPriceFactory::new()->create(['product' => $product3]);

        $client->request(Request::METHOD_GET, '/api/products');

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        self::assertCount(3, $decoded);

        foreach ($decoded as $item) {
            Helpers::validateBody($item, ['id', 'name', 'description', 'prices']);
            self::assertNotEmpty($item['prices']);
            foreach ($item['prices'] as $price) {
                Helpers::validateBody($price, ['id', 'price', 'pricePeriod']);
            }
        }
    }

    /******************************************
     * GET
     ******************************************/

    public function testGetActionNotFound(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/products/bad_product_id');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $client->request(Request::METHOD_GET, sprintf('/api/products/%s', $product->id));

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        Helpers::validateBody($decoded, ['id', 'name', 'description', 'prices']);
        self::assertSame($decoded['id'], $product->id);
        self::assertSame($decoded['name'], $product->name);
        self::assertSame($decoded['description'], $product->description);
        self::assertCount(1, $decoded['prices']);
        Helpers::validateBody($decoded['prices'][0], ['id', 'price', 'pricePeriod']);
        self::assertSame($decoded['prices'][0]['id'], $price->id);
    }

    /******************************************
     * POST
     ******************************************/

    public function testPostActionSuccessful(): void
    {
        $body = [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'prices' => [
                ['price' => 999, 'pricePeriod' => 'monthly'],
            ],
        ];

        $client = self::createClient();
        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);

        Helpers::validateBody($decoded, ['id', 'name', 'description', 'prices']);
        self::assertNotNull($decoded['id']);
        self::assertSame($decoded['name'], $body['name']);
        self::assertSame($decoded['description'], $body['description']);
        self::assertCount(1, $decoded['prices']);
        Helpers::validateBody($decoded['prices'][0], ['id', 'price', 'pricePeriod']);

        ProductFactory::assert()->exists(['name' => $body['name']]);
    }

    public function testPostActionWithValidationError(): void
    {
        $body = ['name' => '', 'description' => ''];

        $client = self::createClient();
        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::expectViolationMessage($decoded, 'name: This value should not be blank.');
        Helpers::expectViolationMessage($decoded, 'description: This value should not be blank.');
    }

    public function testPostActionWithDuplicateName(): void
    {
        $client = self::createClient();

        $existingProduct = ProductFactory::new()->create();

        $body = [
            'name' => $existingProduct->name,
            'description' => $this->faker->sentence(),
        ];
        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/products',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::expectViolationMessage($decoded, 'name: Product with this name already exists.');
    }

    /******************************************
     * PATCH
     ******************************************/

    public function testPatchActionNotFound(): void
    {
        $client = self::createClient();
        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s', 'bad_product_id'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatchActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();

        $newName = $this->faker->words(3, true);
        $body = ['name' => $newName, 'description' => $product->description];

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s', $product->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);

        Helpers::validateBody($decoded, ['id', 'name', 'description', 'prices']);
        self::assertSame($decoded['id'], $product->id);
        self::assertSame($decoded['name'], $newName);
        self::assertSame($decoded['description'], $product->description);
    }

    public function testPatchActionWithValidationError(): void
    {
        $body = ['name' => true];

        $client = self::createClient();
        $product = ProductFactory::new()->create();

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/products/%s', $product->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::expectViolationsCount($decoded, 1);
        Helpers::expectViolationMessage($decoded, 'name: This value should be of type null|string.');
    }

    /******************************************
     * DELETE
     ******************************************/

    public function testDeleteActionSuccessful(): void
    {
        $client = self::createClient();

        $product = ProductFactory::new()->create();
        $price = ProductPriceFactory::new()->create(['product' => $product]);

        $client->request(Request::METHOD_DELETE, sprintf('/api/products/%s', $product->id));

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        ProductFactory::assert()->notExists(['id' => $product->id]);
        ProductPriceFactory::assert()->notExists(['id' => $price->id]);
    }

    public function testDeleteActionNotFound(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_DELETE, '/api/products/bad_product_id');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
