<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CommandeProduitRepository;


#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
class CommandeProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'commandeProduits')]
    #[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id_commande', nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commandeProduits')]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
    private ?Produit $produit = null;


    #[ORM\Column(type: "integer")]
    private int $quantite;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }
}
