<?php

namespace App\Entity;

use App\Repository\PlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
class Plan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_plan;

    #[ORM\Column(nullable: true)]
    private ?int $langitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $latitude = null;

    public function getIdPlan(): ?int
    {
        return $this->id_plan;
    }

    public function setIdPlan(int $id_plan): static
    {
        $this->id_plan = $id_plan;

        return $this;
    }

    public function getLangitude(): ?int
    {
        return $this->langitude;
    }

    public function setLangitude(?int $langitude): static
    {
        $this->langitude = $langitude;

        return $this;
    }

    public function getLatitude(): ?int
    {
        return $this->latitude;
    }

    public function setLatitude(?int $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }
}
