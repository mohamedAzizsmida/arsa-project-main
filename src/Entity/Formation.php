<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
#[ORM\Index(name: "idx_formation_id", columns: ["id"])]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'type obligatoire')]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'type obligatoire')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'type obligatoire')]
    private ?string $formateur = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\GreaterThanOrEqual("today", message: 'date invalide')]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\ManyToOne(targetEntity: Association::class, inversedBy: 'formations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Association $association = null;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Cour::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $cours;

     #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'formations')]
    private Collection $users;

    public function __construct()
    {
        $this->cours = new ArrayCollection();
        $this->users = new ArrayCollection(); // initialize the collection
    }
    public function getIdFormation(): ?int
    {
        return $this->id;
    }

    public function setIdFormation(int $id_formation): static
    {
        $this->id = $id_formation;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getFormateur(): ?string
    {
        return $this->formateur;
    }

    public function setFormateur(?string $formateur): static
    {
        $this->formateur = $formateur;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->date_debut;
    }

    public function setDateDebut(?\DateTimeInterface $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getAssociation(): ?Association
    {
        return $this->association;
    }

    public function setAssociation(?Association $association): static
    {
        $this->association = $association;
        return $this;
    }

    /** @return Collection<int, Cour> */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cour $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
            $cour->setFormation($this);
        }
        return $this;
    }

    public function removeCour(Cour $cour): static
    {
        if ($this->cours->removeElement($cour)) {
            // Set the owning side to null (unless already changed)
            if ($cour->getFormation() === $this) {
                $cour->setFormation(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, User> */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }
        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);
        return $this;
    }
}
