<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]  // Ensure password is here
    private ?string $password = null; // ✅ Make sure this is named "password"

    #[ORM\Column(nullable: true)]
    private ?int $tel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adress = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: "json", nullable: true)]
    private array $roles = [];

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password; // Set the hashed password
        return $this;
    }
    

    public function getTel(): ?int
    {
        return $this->tel;
    }

    public function setTel(?int $tel): static
    {
        $this->tel = $tel;
        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): static
    {
        $this->adress = $adress;
        return $this;
    }

    public function getRoles(): array
    {
        // Ensure at least 'ROLE_USER' is always assigned
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
    
        return array_unique($roles);
    }
    
    public function setRoles(array $roles): self
    {
        if (empty($roles)) {
            $roles = ['ROLE_USER']; // Default role if none is assigned
        }
        $this->roles = $roles;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }


    public function eraseCredentials() {}

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    
}
