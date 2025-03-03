<?php
// src/Entity/CategorieProduit.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CategorieProduitRepository;

#[ORM\Entity(repositoryClass: CategorieProduitRepository::class)]
class CategorieProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)] // New field
    private ?string $typeentreprise = null; // New field

    #[ORM\OneToMany(mappedBy: "categorie", targetEntity: Produit::class)]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getTypeentreprise(): ?string
    {
        return $this->typeentreprise;
    }

    public function setTypeentreprise(?string $typeentreprise): static
    {
        $this->typeentreprise = $typeentreprise;
        return $this;
    }

    public function getProduits(): Collection
    {
        return $this->produits;
    }
}
