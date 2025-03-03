<?php

namespace App\Entity;

class DonationItem
{
    private string $type;
    private string $name;
    private float $price;
    private string $image;
    private int $quantity;

    public function __construct(string $type, string $name, float $price, string $image, int $quantity = 1)
    {
        $this->type = $type;
        $this->name = $name;
        $this->price = $price;
        $this->image = $image;
        $this->quantity = $quantity;
    }

    public function getType(): string { return $this->type; }
    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function getImage(): string { return $this->image; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): void { $this->quantity = $quantity; }
}