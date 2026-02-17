<?php

namespace App\Tests\Application\Controller;

use App\Factory\UserFactory;
use App\Tests\Application\Helpers;
use Faker\{Factory, Generator as FakerGenerator};
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\{Request, Response};
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

#[ResetDatabase]
final class UserControllerTest extends WebTestCase
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
        UserFactory::createMany(3);
        $client->request(Request::METHOD_GET, '/api/users');

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        self::assertCount(3, $decoded);

        foreach ($decoded as $item) {
            Helpers::validateBody($item, ['id', 'firstName', 'email', 'lastName']);
        }
    }

    /******************************************
     * GET
     ******************************************/
    public function testGetActionNotFound(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/users/bad_user_id');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetActionSuccessful(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();

        $client->request(Request::METHOD_GET, sprintf('/api/users/%s', $user->id));

        self::assertResponseIsSuccessful();
        $body = $client->getResponse()->getContent();
        self::assertJson($body);

        $decoded = json_decode($body, true);
        Helpers::validateBody($decoded, ['id', 'firstName', 'email', 'lastName']);
        self::assertSame($decoded['id'], $user->id);
        self::assertSame($decoded['firstName'], $user->firstName);
        self::assertSame($decoded['lastName'], $user->lastName);
        self::assertSame($decoded['email'], $user->email);
    }

    /******************************************
     * POST
     ******************************************/
    public function testPostActionSuccessful(): void
    {
        $body = ['firstName' => $this->faker->firstName(), 'lastName' => $this->faker->lastName(), 'email' => $this->faker->email()];

        $client = self::createClient();
        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);

        Helpers::validateBody($decoded, ['id', 'firstName', 'email', 'lastName']);
        self::assertNotNull($decoded['id']);
        self::assertSame($decoded['firstName'], $body['firstName']);
        self::assertSame($decoded['lastName'], $body['lastName']);
        self::assertSame($decoded['email'], $body['email']);

        UserFactory::assert()->exists($body);
    }

    public function testPostActionWithValidationError(): void
    {
        $body = ['firstName' => '', 'lastName' => '', 'email' => ''];

        $client = self::createClient();
        $client->request(
            method: Request::METHOD_POST,
            uri: '/api/users',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::expectViolationsCount($decoded, 6);
        Helpers::expectViolationMessage($decoded, 'email: This value is too short. It should have 1 character or more.');
        Helpers::expectViolationMessage($decoded, 'firstName: This value should not be blank.');
        Helpers::expectViolationMessage($decoded, 'firstName: This value is too short. It should have 2 characters or more.');
        Helpers::expectViolationMessage($decoded, 'lastName: This value should not be blank.');
        Helpers::expectViolationMessage($decoded, 'lastName: This value is too short. It should have 2 characters or more.');
    }

    /******************************************
     * PATCH
     ******************************************/
    public function testPatchActionNotFound(): void
    {
        $client = self::createClient();
        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/users/%s', 'bad_user_id'),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPatchActionSuccessful(): void
    {
        $client = self::createClient();

        $user = UserFactory::new()->create();

        $body = ['email' => $this->faker->email()];

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/users/%s', $user->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);

        Helpers::validateBody($decoded, ['id', 'firstName', 'email', 'lastName']);
        self::assertSame($decoded['id'], $user->id);
        self::assertSame($decoded['firstName'], $user->firstName);
        self::assertSame($decoded['lastName'], $user->lastName);
        self::assertSame($decoded['email'], $user->email);

        UserFactory::assert()->exists([
            ...$body,
            'id' => $user->id,
            'email' => $user->email,
            'lastName' => $user->lastName,
        ]);
    }

    public function testPatchActionWithValidationError(): void
    {
        $body = ['firstName' => true];

        $client = self::createClient();
        $user = UserFactory::new()->create();

        $client->request(
            method: Request::METHOD_PATCH,
            uri: sprintf('/api/users/%s', $user->id),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $resp = $client->getResponse()->getContent();
        self::assertJson($resp);

        $decoded = json_decode($resp, true);
        Helpers::expectViolationsCount($decoded, 1);
        Helpers::expectViolationMessage($decoded, 'firstName: This value should be of type null|string.');
    }
}
