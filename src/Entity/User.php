<?php

namespace App\Entity;

use App\Enum\UserLevel;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true, unique: true)]
    #[Assert\Length(max: 100)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', enumType: UserLevel::class)]
    private UserLevel $level;

    #[ORM\Column(options: ['default' => false])]
    private bool $active = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getLevel(): UserLevel
    {
        return $this->level;
    }

    public function setLevel(UserLevel $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = match ($this->level) {
            UserLevel::ADMIN => ['ROLE_ADMIN', 'ROLE_USER'],
            UserLevel::ADVANCED => ['ROLE_ADVANCED', 'ROLE_USER'],
            UserLevel::BASIC => ['ROLE_USER'],
        };

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Not needed for stateless JWT
    }

    public function getUserIdentifier(): string
    {
        return $this->username ?? '';
    }
}
