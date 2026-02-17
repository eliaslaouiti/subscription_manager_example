<?php

namespace App\Controller;

use App\Entity\{ProductPrice, Subscription, User};
use App\Model\DTO\Subscription\CreateSubscriptionDto;
use App\Repository\SubscriptionRepository;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Subscriptions')]
#[Route('/users/{userId}/subscriptions')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly SubscriptionService $subscriptionService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'cget_subscriptions', methods: [Request::METHOD_GET])]
    #[OA\Parameter(name: 'userId', description: 'The user identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns active subscriptions for a user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Subscription::class, groups: ['subscription:read']))
        )
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'User not found')]
    public function cgetAction(
        #[MapEntity(id: 'userId')] User $user,
    ): JsonResponse {
        $subscriptions = $this->subscriptionRepository->findActiveByUser($user->id);

        return $this->json($subscriptions, context: ['groups' => ['subscription:read']]);
    }

    #[Route('/{id}', name: 'get_subscription', methods: [Request::METHOD_GET])]
    #[OA\Parameter(name: 'userId', description: 'The user identifier', in: 'path')]
    #[OA\Parameter(name: 'id', description: 'The subscription identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns a subscription',
        content: new Model(type: Subscription::class, groups: ['subscription:read'])
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Subscription not found')]
    public function getAction(
        #[MapEntity(expr: 'repository.findOneByIdAndUser(id, userId)')] Subscription $subscription,
    ): JsonResponse {
        return $this->json($subscription, context: ['groups' => ['subscription:read']]);
    }

    #[Route('', name: 'post_subscription', methods: [Request::METHOD_POST])]
    #[OA\Parameter(name: 'userId', description: 'The user identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Returns the created subscription',
        content: new Model(type: Subscription::class, groups: ['subscription:read'])
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'User or product price not found')]
    #[OA\Response(response: Response::HTTP_CONFLICT, description: 'User already has an active subscription to this product price')]
    #[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation issue')]
    public function postAction(
        #[MapEntity(id: 'userId')] User $user,
        #[MapRequestPayload] CreateSubscriptionDto $createSubscriptionDto,
    ): JsonResponse {
        $productPrice = $this->entityManager->getRepository(ProductPrice::class)->find($createSubscriptionDto->productPriceId);

        if (null === $productPrice) {
            return $this->json(['error' => 'Product price not found'], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionService->subscribe($user, $productPrice);

        if (null === $subscription) {
            return $this->json(['error' => 'User already has an active subscription to this product price'], Response::HTTP_CONFLICT);
        }

        return $this->json($subscription, Response::HTTP_CREATED, context: ['groups' => ['subscription:read']]);
    }

    #[Route('/{id}', name: 'delete_subscription', methods: [Request::METHOD_DELETE])]
    #[OA\Parameter(name: 'userId', description: 'The user identifier', in: 'path')]
    #[OA\Parameter(name: 'id', description: 'The subscription identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns the unsubscribed subscription with end date',
        content: new Model(type: Subscription::class, groups: ['subscription:read'])
    )]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Subscription not found')]
    public function deleteAction(
        #[MapEntity(expr: 'repository.findOneByIdAndUser(id, userId)')] Subscription $subscription,
    ): JsonResponse {
        $this->subscriptionService->unSubscribe($subscription);

        return $this->json($subscription, Response::HTTP_OK, context: ['groups' => ['subscription:read']]);
    }
}
