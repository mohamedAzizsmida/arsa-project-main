<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }



    /**
     * Recherche les posts par contenu et date de création
     */
 // App\Repository\PostRepository.php

 public function searchPosts(array $criteria): array
{
    $qb = $this->createQueryBuilder('p');

    if (!empty($criteria['contenu'])) {
        $qb->andWhere('LOWER(p.contenu) LIKE LOWER(:contenu)')
           ->setParameter('contenu', '%' . $criteria['contenu'] . '%');
    }

    return $qb->getQuery()->getResult();
}




    public function findCommentairesByPostId($postId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('c')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.id', 'DESC') // Trie les commentaires par date descendante
            ->getQuery()
            ->getResult();
    }
    








}
