<?php

namespace App\Repository;

use App\Entity\ProductPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductPrice>
 */
class ProductPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPrice::class);
    }

    public function findOneByIdAndProduct(string $id, string $productId): ?ProductPrice
    {
        return $this->createQueryBuilder('pp')
            ->where('pp.id = :id')
            ->andWhere('pp.product = :productId')
            ->setParameter('id', $id)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
