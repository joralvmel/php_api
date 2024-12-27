<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JetBrains\PhpStorm\ArrayShape;

#[ORM\Entity, ORM\Table(name: 'results')]
class Result implements \JsonSerializable
{
    public final const RESULT_ATTR = 'result';
    public final const TIME_ATTR = 'time';

    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    #[Serializer\XmlAttribute]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'float')]
    #[Serializer\SerializedName(Result::RESULT_ATTR), Serializer\XmlElement(cdata: false)]
    private float $result;

    #[ORM\Column(type: 'datetime')]
    #[Serializer\SerializedName(Result::TIME_ATTR), Serializer\XmlElement(cdata: false)]
    private \DateTimeInterface $time;

    /**
     * Constructor
     *
     * @param User                $user
     * @param float               $result
     * @param \DateTimeInterface  $time
     */
    public function __construct(int $result, \DateTime $time, User $user)
    {
        $this->result = $result;
        $this->time = $time;
        $this->user = $user;
    }

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

    /**
     * @inheritDoc
     *
     * @return array<string, int|float|string|null>
     */
    #[ArrayShape([
        'Id' => "int|null",
        self::RESULT_ATTR => 'float',
        self::TIME_ATTR => '\DateTimeInterface'
    ])]
    public function jsonSerialize(): array
    {
        return [
            'Id' => $this->getId(),
            self::RESULT_ATTR => $this->getResult(),
            self::TIME_ATTR => $this->getTime()->format('c'),
        ];
    }
}