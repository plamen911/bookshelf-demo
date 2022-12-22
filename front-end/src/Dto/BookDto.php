<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;

class BookDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(
        pattern: "/^[a-zA-z0-9\. ]+$/",
        message: "Allowed symbols: azAz0-9."
    )]
    private ?string $title;

    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    #[Assert\Regex(
        pattern: "/^[a-zA-z0-9\. ]+$/",
        message: "Allowed symbols must be azAz0-9. to enter"
    )]
    private ?string $author;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Range(
        notInRangeMessage: 'Pages must be between {{ min }} and {{ max }} to enter',
        min: 0,
        max: 1000,
    )]
    private ?int $pages;

    #[NotBlank]
    #[Assert\Regex(
        pattern: "/^\d{2}-\d{2}-\d{4}$/",
        message: "Date format must be dd-mm-yyyy to enter"
    )]
//    #[Assert\DateTime(format: 'd-m-Y')]
//    #[Assert\LessThan('+100 years')]
//    #[Assert\GreaterThan('-100 years')]
    private ?string $releaseDate;   // dd-mm-yyyy

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getPages(): ?int
    {
        return $this->pages;
    }

    public function setPages(?int $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    public function getReleaseDate(): ?string
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?string $releaseDate): self
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }
}
