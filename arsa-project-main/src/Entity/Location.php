<?php

namespace App\Entity;
use App\Repository\LocationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    #[ORM\Column(length: 255)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255)]
    private ?string $donationAmount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $youtubeEmbedUrl = null;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Event::class)]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getDonationAmount(): ?string
    {
        return $this->donationAmount;
    }

    public function setDonationAmount(string $donationAmount): self
    {
        $this->donationAmount = $donationAmount;
        return $this;
    }

    public function getYoutubeEmbedUrl(): ?string
    {
        return $this->youtubeEmbedUrl;
    }

    public function setYoutubeEmbedUrl(?string $youtubeEmbedUrl): self
    {
        $this->youtubeEmbedUrl = $youtubeEmbedUrl;
        return $this;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setLocation($this);
        }
        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            if ($event->getLocation() === $this) {
                $event->setLocation(null);
            }
        }
        return $this;
    }
}