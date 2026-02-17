<?php

namespace App\Repository;

use App\Entity\Subscription;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * @return Subscription[]
     */
    public function findActiveByUser(string $userId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->andWhere('s.endDate IS NULL OR s.endDate > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findActiveByUserAndProductPrice(string $userId, string $productPriceId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :userId')
            ->andWhere('s.productPrice = :productPriceId')
            ->andWhere('s.endDate IS NULL OR s.endDate > :now')
            ->setParameter('userId', $userId)
            ->setParameter('productPriceId', $productPriceId)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findOneByIdAndUser(string $id, string $userId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :id')
            ->andWhere('s.user = :userId')
            ->setParameter('id', $id)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
