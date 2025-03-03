<?php

namespace App\Repository;

use App\Entity\CommandeProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommandeProduit>
 */
class CommandeProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduit::class);
    }

    public function getSumOfOrderedQuantity($productId)
    {
        return $this->createQueryBuilder('cp')
            ->select('SUM(cp.quantite)') // Summing the quantities of ordered products
            ->leftJoin('cp.commande', 'c') // Join with the Commande entity
            ->where('cp.produit = :product') // Filter by the product
            ->andWhere('c.status = 1') // Only consider orders with a status of 1 (Validated)
            ->setParameter('product', $productId) // Set the product ID parameter
            ->getQuery()
            ->getSingleScalarResult() ?? 0; // Return the result or 0 if no result found
    }
    
    //    /**
    //     * @return CommandeProduit[] Returns an array of CommandeProduit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CommandeProduit
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
