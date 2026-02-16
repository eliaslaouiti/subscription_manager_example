<?php

namespace App\Factory;

use App\Entity\Product;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
{
    #[Override]
    public static function class(): string
    {
        return Product::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->words(3, true),
            'description' => self::faker()->sentence(),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
