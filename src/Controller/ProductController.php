<?php

namespace App\Controller;

use App\Entity\Product;
use App\Model\DTO\Product\{CreateProductDto, UpdateProductDto};
use App\Repository\ProductRepository;
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

#[OA\Tag(name: 'Products')]
#[Route('/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface $objectMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'cget_products', methods: [Request::METHOD_GET])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns a collection of products',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class, groups: ['product:read']))
        )
    )]
    public function cgetAction(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        return $this->json(
            $products,
            context: ['groups' => ['product:read']]
        );
    }

    #[Route('/{id}', name: 'get_product', methods: [Request::METHOD_GET])]
    #[OA\Parameter(name: 'id', description: 'The product identifier', in: 'path')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns a product',
        content: new Model(type: Product::class, groups: ['product:read'])
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Product not found'
    )]
    public function getAction(
        #[MapEntity] Product $product,
    ): JsonResponse {
        return $this->json($product, context: ['groups' => ['product:read']]);
    }

    #[Route('', name: 'post_product', methods: [Request::METHOD_POST])]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Returns the created product',
        content: new Model(type: Product::class, groups: ['product:read'])
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Validation issue'
    )]
    public function postAction(
        #[MapRequestPayload] CreateProductDto $createProductDto,
    ): JsonResponse {
        $product = $this->objectMapper->map($createProductDto, Product::class);
        $createProductDto->mapPricesTo($product);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_CREATED, context: ['groups' => ['product:read']]);
    }

    #[Route('/{id}', name: 'patch_product', methods: [Request::METHOD_PATCH])]
    #[OA\Parameter(name: 'id', description: 'The product identifier', in: 'path')]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Product deleted')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found')]
    public function patchAction(
        #[MapEntity] Product $product,
        #[MapRequestPayload] UpdateProductDto $updateProductDto,
    ): JsonResponse {
        $product = $this->objectMapper->map($updateProductDto, $product);

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_OK, context: ['groups' => ['product:read']]);
    }

    #[Route('/{id}', name: 'delete_product', methods: [Request::METHOD_DELETE])]
    #[OA\Parameter(name: 'id', description: 'The product identifier', in: 'path')]
    #[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Product deleted')]
    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found')]
    public function deleteAction(Product $product): JsonResponse
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
