<?php

namespace App\Controller;

use App\Entity\{Product, ProductPrice};
use App\Model\DTO\ProductPrice\{CreateProductPriceDto, UpdateProductPriceDto};
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Product Prices')]
#[Route('/products/{productId}/prices')]
class ProductPriceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ObjectMapperInterface $objectMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'cget_product_prices', methods: [Request::METHOD_GET])]
    public function cgetAction(
        #[MapEntity(id: 'productId')] Product $product,
    ): JsonResponse {
        return $this->json(
            $product->prices->toArray(),
            context: ['groups' => ['product_price:read']]
        );
    }

    #[Route('/{id}', name: 'get_product_price', methods: [Request::METHOD_GET])]
    public function getAction(
        #[MapEntity(expr: 'repository.findOneByIdAndProduct(id, productId)')] ProductPrice $productPrice,
    ): JsonResponse {
        return $this->json($productPrice, context: ['groups' => ['product_price:read']]);
    }

    #[Route('', name: 'post_product_price', methods: [Request::METHOD_POST])]
    public function postAction(
        #[MapEntity(id: 'productId')] Product $product,
        #[MapRequestPayload] CreateProductPriceDto $createProductPriceDto,
    ): JsonResponse {
        $productPrice = $this->objectMapper->map($createProductPriceDto, ProductPrice::class);
        $product->addPrice($productPrice);

        $errors = $this->validator->validate($productPrice);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($productPrice, Response::HTTP_CREATED, context: ['groups' => ['product_price:read']]);
    }

    #[Route('/{id}', name: 'patch_product_price', methods: [Request::METHOD_PATCH])]
    public function patchAction(
        #[MapEntity(expr: 'repository.findOneByIdAndProduct(id, productId)')] ProductPrice $productPrice,
        #[MapRequestPayload] UpdateProductPriceDto $updateProductPriceDto,
    ): JsonResponse {
        if (null !== $updateProductPriceDto->price) {
            $productPrice->price = $updateProductPriceDto->price;
        }
        if (null !== $updateProductPriceDto->pricePeriod) {
            $productPrice->pricePeriod = $updateProductPriceDto->pricePeriod;
        }

        $errors = $this->validator->validate($productPrice);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($productPrice);
        $this->entityManager->flush();

        return $this->json($productPrice, Response::HTTP_OK, context: ['groups' => ['product_price:read']]);
    }

    #[Route('/{id}', name: 'delete_product_price', methods: [Request::METHOD_DELETE])]
    public function deleteAction(
        #[MapEntity(expr: 'repository.findOneByIdAndProduct(id, productId)')] ProductPrice $productPrice,
    ): JsonResponse {
        $this->entityManager->remove($productPrice);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
