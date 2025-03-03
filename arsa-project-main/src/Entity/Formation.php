<?php
    namespace App\Entity;

    use App\Repository\FormationRepository;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\UX\Turbo\Attribute\Broadcast;
    use Symfony\Component\Validator\Constraints as Assert;
    use DateTime;


    #[ORM\Entity(repositoryClass: FormationRepository::class)]
    #[Broadcast]
    class Formation
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column(type: 'integer')]
        private ?int $id_formation = null;

        #[ORM\Column(length: 255, nullable: true)]
        #[Assert\NotBlank(message:'type obligatoire' )]
        private ?string $titre = null;

        #[ORM\Column(length: 255, nullable: true)]
        #[Assert\NotBlank(message:'type obligatoire' )]
        #[Assert\Length(
            max: 100,
            maxMessage: 'La description ne doit pas dépasser {{ limit }} caractères.'
        )]
        private ?string $description = null;

        #[ORM\Column(length: 255, nullable: true)]
        #[Assert\NotBlank(message:'type obligatoire' )]
        private ?string $formateur = null;

        #[ORM\Column(type: 'datetime', nullable: true)]
        #[Assert\GreaterThanOrEqual("today", message: 'date invalide')]
        private ?\DateTimeInterface $date_debut = null;

        public function getIdFormation(): ?int
        {
            return $this->id_formation;
        }

        public function setIdFormation(int $id_formation): static
        {
            $this->id_formation = $id_formation;
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
        public function __construct(DateTime $currentDate)
        {
            $this->date_debut = $currentDate;
        }
    }
