<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getConseils'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu du conseil est obligatoire.")]
    #[Assert\Length(min: 1, minMessage:  "Le contenu du conseil est trop court.")]
    #[Groups(['getConseils'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'conseils')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['getConseils'])]
    private ?User $User = null;

    #[Assert\NotBlank(message: "Le mois du conseil est obligatoire.")]
    #[Assert\LessThanOrEqual(12, message: "Vous devez entrer le numéro d'un mois valide.")]
    #[Assert\GreaterThanOrEqual(1, message: "Vous devez entrer le numéro d'un mois valide.")]
    #[Groups(['getConseils'])]
    #[ORM\Column]
    private ?int $month = null;


    public function getId(): ?int {
        return $this->id;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(string $description): static {
        $this->description = $description;

        return $this;
    }

    public function getUser(): ?User {
        return $this->User;
    }

    public function setUser(?User $User): static {
        $this->User = $User;

        return $this;
    }

    public function getMonth(): ?int {
        return $this->month;
    }

    public function setMonth(int $month): static {
        $this->month = $month;

        return $this;
    }
}
