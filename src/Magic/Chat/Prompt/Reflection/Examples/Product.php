<?php

namespace Mateffy\Magic\Chat\Prompt\Reflection\Examples;

use Mateffy\Magic\Chat\Prompt\Reflection\Description;
use Mateffy\Magic\Chat\Prompt\Reflection\Min;

#[Description('A product from a catalog or magazine page')]
class Product
{
    #[Description('The products label or name. Minimum 5 characters.')]
    #[Min(5)]
    public string $name;

    #[Description('Price in EUR')]
    #[Min(0)]
    public float $price;

    #[Description('Price in EUR, if it is discounted. Make sure to only include this for general discounts for everyone, not special offers for members etc.')]
    public ?float $discountedPrice;

    #[Description('The type of product, e.g. book, magazine, etc.')]
    public ProductType $type;
}
