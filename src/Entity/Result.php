<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[ORM\Entity, ORM\Table(name: 'results')]
class Result
{
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Serializer\XmlAttribute]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'float')]
    private float $result;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getResult(): float
    {
        return $this->result;
    }

    public function setResult(float $result): void
    {
        $this->result = $result;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): void
    {
        $this->time = $time;
    }
}