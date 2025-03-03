<?php

namespace App\Entity;

use App\Repository\CourRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourRepository::class)]
class Cour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_cour = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message:'type obligatoire' )]
    private ?string $cour = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message:'type obligatoire' )]
    private ?string $decription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message:'type obligatoire' )]
    private ?string $contenu = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'cours')]
    #[ORM\JoinColumn(name: 'id_formation_FK', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;


    public function getIdCour(): ?int
    {
        return $this->id_cour;
    }

    public function setIdCour(int $id_cour): static
    {
        $this->id_cour = $id_cour;

        return $this;
    }

    public function getCour(): ?string
    {
        return $this->cour;
    }

    public function setCour(?string $cour): static
    {
        $this->cour = $cour;

        return $this;
    }

    public function getDecription(): ?string
    {
        return $this->decription;
    }

    public function setDecription(?string $decription): static
    {
        $this->decription = $decription;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

}
