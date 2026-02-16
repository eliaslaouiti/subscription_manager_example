<?php

namespace App\Factory;

use App\Entity\ProductPrice;
use App\Enum\ProductPricePeriod;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductPrice>
 */
final class ProductPriceFactory extends PersistentObjectFactory
{
    #[Override]
    public static function class(): string
    {
        return ProductPrice::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'product' => ProductFactory::new(),
            'pricePeriod' => self::faker()->randomElement(ProductPricePeriod::cases()),
            'price' => self::faker()->numberBetween(100, 100000),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
