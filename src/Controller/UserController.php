<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\DTO\User\{CreateUserDto, UpdateUserDto};
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Users')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface $objectMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/users', name: 'cget_users', methods: [Request::METHOD_GET])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns a collection of users',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['user:read']))
        )
    )]
    public function cgetAction(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json(
            $users,
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/users/{id}', name: 'get_users', methods: [Request::METHOD_GET])]
    #[OA\Parameter(name: 'id', description: 'The user identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns a user',
        content: new Model(type: User::class, groups: ['user:read'])
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'User not found'
    )]
    public function getAction(
        #[MapEntity] User $user,
    ): JsonResponse {
        return $this->json(
            $user,
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/users', name: 'post_user', methods: [Request::METHOD_POST])]
    #[OA\Parameter(name: 'id', description: 'The user identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Returns a user',
        content: new Model(type: User::class, groups: ['user:read'])
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Validation issue'
    )]
    public function postAction(
        #[MapRequestPayload] CreateUserDto $createUserDto,
    ): JsonResponse {
        $user = $this->objectMapper->map($createUserDto, User::class);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(
            $user,
            Response::HTTP_CREATED,
            context: ['groups' => ['user:read']]
        );
    }

    #[Route('/users/{id}', name: 'patch_user', methods: [Request::METHOD_PATCH])]
    #[OA\Parameter(name: 'id', description: 'The user identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns a user',
        content: new Model(type: User::class, groups: ['user:read'])
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Validation issue'
    )]
    public function patchAction(
        #[MapEntity()] User $user,
        #[MapRequestPayload] UpdateUserDto $updateUserDto,
    ): JsonResponse {
        $user = $this->objectMapper->map($updateUserDto, $user);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(
            $user,
            Response::HTTP_OK,
            context: ['groups' => ['user:read']]
        );
    }
}
