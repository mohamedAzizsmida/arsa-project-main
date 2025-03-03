<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Entity\User;
/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }
 /**
     * Get the latest products ordered by dateAjout (most recent first)
     */
    public function findLatestProducts(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dateAjout', 'DESC')  // Order by newest first
            ->setMaxResults($limit)           // Limit the number of results
            ->getQuery()
            ->getResult();
    }
    public function getTotalProduitsDistribues(?User $user = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id_produit)')
            ->where('p.quantiteReelle = 0');
    
        if ($user) {
            $qb->andWhere('p.user = :user')
               ->setParameter('user', $user);
        }
    
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    public function getTopOrderedProducts(?User $user = null)
    {
        $conn = $this->getEntityManager()->getConnection();
    
        $sql = "
            SELECT
                p.id_produit,
                p.nom as name,
                SUM(cp.quantite) AS totalOrdered 
            FROM produit p
            INNER JOIN commande_produit cp ON cp.produit_id = p.id_produit";
        
        if ($user) {
            $sql .= " WHERE p.user_id = :user_id";
        }
    
        $sql .= " GROUP BY p.id_produit ORDER BY totalOrdered DESC LIMIT 5";
    
        $stmt = $conn->prepare($sql);
    
        if ($user) {
            $stmt->bindValue('user_id', $user->getId());
        }
    
        return $stmt->executeQuery()->fetchAllAssociative();
    }
    
    public function getTotalQuantities(?User $user = null)
    {
        $conn = $this->getEntityManager()->getConnection();
    
        $sql = "
            SELECT SUM(p.quantite) AS totalOrdered
            FROM produit p
          ";
        
        if ($user) {
            $sql .= " WHERE p.user_id = :user_id";
        }
    
        $stmt = $conn->prepare($sql);
    
        if ($user) {
            $stmt->bindValue(':user_id', $user->getId());
        }
    
        $result = $stmt->executeQuery()->fetchAssociative();
    
        return $result['totalOrdered'] ? (int) $result['totalOrdered'] : 0;
    }
    
    public function getProduitsNonDistribuesByUser(?User $user = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id_produit)')
            ->where('p.quantiteReelle IS NULL OR p.quantiteReelle = p.quantite');
    
        if ($user) {
            $qb->andWhere('p.user = :user')
               ->setParameter('user', $user);
        }
    
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    public function getTotalProduitsDonnesByUser(?User $user = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id_produit)');
    
        if ($user) {
            $qb->where('p.user = :user')
               ->setParameter('user', $user);
        }
    
        return $qb->getQuery()->getSingleScalarResult();
    }
    
    public function getTop5LeastOrderedProducts(?User $user = null)
    {
        $conn = $this->getEntityManager()->getConnection();
    
        $sql = "
            SELECT
                p.id_produit,
                p.nom AS name,
                COALESCE(SUM(cp.quantite), 0) AS totalOrdered 
            FROM produit p
            LEFT JOIN commande_produit cp ON cp.produit_id = p.id_produit";
        
        if ($user) {
            $sql .= " WHERE p.user_id = :user_id";
        }
    
        $sql .= " GROUP BY p.id_produit ORDER BY totalOrdered ASC LIMIT 5";
    
        $stmt = $conn->prepare($sql);
    
        if ($user) {
            $stmt->bindValue('user_id', $user->getId());
        }
    
        return $stmt->executeQuery()->fetchAllAssociative();
    }
    
    public function getProduitsParMoisByUser(?User $user = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT
                DATE_FORMAT(p.date_ajout, '%Y-%m') AS mois,
                COUNT(p.id_produit) AS count
            FROM produit p";
        
        if ($user) {
            $sql .= " WHERE p.user_id = :user_id";
        }
    
        $sql .= " GROUP BY mois ORDER BY mois ASC";
    
        $stmt = $conn->prepare($sql);
    
        if ($user) {
            $stmt->bindValue('user_id', $user->getId());
        }
    
        return $stmt->executeQuery()->fetchAllAssociative();
    }
    
    public function getDonationsByCategory(?User $user = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->select("c.nom AS category, COUNT(p.id_produit) AS count")
            ->leftJoin('p.categorie', 'c')
            ->groupBy('c.nom')
            ->orderBy('count', 'DESC');
    
        if ($user) {
            $qb->where('p.user = :user')
               ->setParameter('user', $user);
        }
    
        return $qb->getQuery()->getResult();
    }
    
    public function getMonthlyDonationsByCategory(?User $user = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                DATE_FORMAT(p.date_ajout, '%Y-%m') AS mois,
                COALESCE(c.nom, 'Non classé') AS category,
                COALESCE(SUM(cp.quantite), 0) AS totalQuantity
            FROM produit p
            LEFT JOIN commande_produit cp ON cp.produit_id = p.id_produit
            LEFT JOIN categorie_produit c ON c.id = p.categorie_id";
        
        if ($user) {
            $sql .= " WHERE p.user_id = :user_id";
        }
    
        $sql .= " GROUP BY mois, category ORDER BY mois ASC, category ASC";
    
        $stmt = $conn->prepare($sql);
    
        if ($user) {
            $stmt->bindValue('user_id', $user->getId());
        }
    
        return $stmt->executeQuery()->fetchAllAssociative();
    }
    
    public function findByFilters($categorieId, $quantityFilter, $dateFilter, $typeEntrepriseFilter)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c');  // Join CategorieProduit as 'c'
    
        // Filter by category - Only apply if categorieId is not null
        if ($categorieId !== null) {
            $queryBuilder->andWhere('p.categorie = :categorieId')
                         ->setParameter('categorieId', $categorieId);
        }
    
        // Filter by quantity
        if ($quantityFilter == 'small') {
            $queryBuilder->andWhere('p.quantiteReelle < :quantity')
                         ->setParameter('quantity', 10);
        } elseif ($quantityFilter == 'medium') {
            $queryBuilder->andWhere('p.quantiteReelle BETWEEN :minQuantity AND :maxQuantity')
                         ->setParameter('minQuantity', 10)
                         ->setParameter('maxQuantity', 100);
        } elseif ($quantityFilter == 'large') {
            $queryBuilder->andWhere('p.quantiteReelle > :quantity')
                         ->setParameter('quantity', 100);
        }
    
        // Filter by date
        if ($dateFilter) {
            $currentDate = new \DateTime();
            
            if ($dateFilter == 'last_week') {
                $queryBuilder->andWhere('p.dateAjout >= :date')
                             ->setParameter('date', $currentDate->modify('-1 week'));
            } elseif ($dateFilter == 'last_month') {
                $queryBuilder->andWhere('p.dateAjout >= :date')
                             ->setParameter('date', $currentDate->modify('-1 month'));
            } elseif ($dateFilter == 'last_3_months') {
                $queryBuilder->andWhere('p.dateAjout >= :date')
                             ->setParameter('date', $currentDate->modify('-3 months'));
            } elseif ($dateFilter == 'last_6_months') {
                $queryBuilder->andWhere('p.dateAjout >= :date')
                             ->setParameter('date', $currentDate->modify('-6 months'));
            }
        }
    
        // Filter by Type d'Entreprise
        if ($typeEntrepriseFilter) {
            $queryBuilder->andWhere('c.typeentreprise = :typeEntrepriseFilter')  // Filtering based on CategorieProduit's typeEntreprise
                         ->setParameter('typeEntrepriseFilter', $typeEntrepriseFilter);
        }
    
        return $queryBuilder->getQuery()->getResult();
    }
    public function getVariationDemandesProduits(?User $user = null)
    {
        // Récupération de la connexion à la base de données
        $conn = $this->getEntityManager()->getConnection();
        
        // Requête SQL pour récupérer les variations de demandes de produits
        $sql = "
            SELECT 
                p.nom AS produit, 
                DATE_FORMAT(c.date_commande, '%Y-%m') AS mois, 
                COALESCE(p.quantite, 0) - COALESCE(SUM(cp.quantite), 0) AS quantite_restante
            FROM produit p
            LEFT JOIN commande_produit cp ON cp.produit_id = p.id_produit
            LEFT JOIN commande c ON cp.commande_id = c.id_commande
            WHERE DATE_FORMAT(c.date_commande, '%Y-%m') <= DATE_FORMAT(CURDATE(), '%Y-%m')
        ";
        
        // Ajout de la condition pour filtrer par utilisateur, si nécessaire
        if ($user) {
            $sql .= " AND p.user_id = :user_id";
        }
        
        // Groupement des résultats par produit et par mois
        $sql .= " GROUP BY produit, mois ORDER BY produit, mois";
        
        // Préparation et exécution de la requête SQL
        $stmt = $conn->prepare($sql);
        
        // Liaison de la variable utilisateur, si nécessaire
        if ($user) {
            $stmt->bindValue('user_id', $user->getId());
        }
        
        // Récupération des résultats
        $results = $stmt->executeQuery()->fetchAllAssociative();
        
        // Structure pour organiser les résultats (par produit et par mois)
        $formattedResults = [];
        
        // Traitement des résultats récupérés pour les organiser
        foreach ($results as $row) {
            $produit = $row['produit'];
            $mois = $row['mois'];
            $quantiteRestante = $row['quantite_restante'];
            
            // Initialisation du tableau pour le produit si nécessaire
            if (!isset($formattedResults[$produit])) {
                $formattedResults[$produit] = [];
            }
            
            // Stockage de la quantité restante pour le mois spécifié
            $formattedResults[$produit][$mois] = $quantiteRestante;
        }
    
        // Si aucun résultat n'est trouvé, retourner un tableau vide ou une structure par défaut
        if (empty($formattedResults)) {
            return [];
        }
        
        // Retour des résultats formatés pour une utilisation facile dans un graphique ou autre
        return $formattedResults;
    }
    

}    