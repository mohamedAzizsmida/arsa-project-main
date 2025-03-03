<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

use App\Entity\CategorieProduit;



#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[Broadcast]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_produit;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $quantite = null; // Changed from "quantité" to "quantite" to avoid encoding issues

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descriptionProduit = null; // Changed variable name for consistency

    #[ORM\ManyToOne(targetEntity: CategorieProduit::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategorieProduit $categorie = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;  // New property for the image


    // New property to store the user who added the product
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;  // User who added the product

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $quantiteReelle = null;  // New property for the real quantity after donation

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $dateAjout = null;

    public function __construct()
    {
        $this->dateAjout = new \DateTime();
    }

  
    public function getIdProduit(): ?int
    {
        return $this->id_produit;
    }

    public function setIdProduit(int $id_produit): static
    {
        $this->id_produit = $id_produit;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getDescriptionProduit(): ?string
    {
        return $this->descriptionProduit;
    }

    public function setDescriptionProduit(?string $descriptionProduit): static
    {
        $this->descriptionProduit = $descriptionProduit;
        return $this;
    }

    public function getCategorie(): ?CategorieProduit
{
    return $this->categorie;
}

public function setCategorie(?CategorieProduit $categorie): static
{
    $this->categorie = $categorie;
    return $this;
}

    // Getter and setter for the image property
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }
    // Getter and setter for the user who added the product
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // Getter and setter for the quantiteReelle property
    public function getQuantiteReelle(): ?int
    {
        return $this->quantiteReelle;
    }

    public function setQuantiteReelle(?int $quantiteReelle): static
    {
        $this->quantiteReelle = $quantiteReelle;
        return $this;
    }
    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout;
    }

    public function setDateAjout(?\DateTimeInterface $dateAjout): static
    {
        $this->dateAjout = $dateAjout ?? new \DateTime(); // Ensures default value if null
        return $this;
    }
    

}
