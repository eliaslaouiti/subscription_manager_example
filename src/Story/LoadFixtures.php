<?php

namespace App\Story;

use App\Enum\ProductPricePeriod;
use App\Factory\{ProductFactory, ProductPriceFactory, UserFactory};
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'main')]
final class LoadFixtures extends Story
{
    public function build(): void
    {
        UserFactory::createMany(10);
        $products = ProductFactory::createMany(3);

        foreach ($products as $product) {
            ProductPriceFactory::createOne([
                'product' => $product,
                'pricePeriod' => ProductPricePeriod::MONTHLY,
            ]);
            ProductPriceFactory::createOne([
                'product' => $product,
                'pricePeriod' => ProductPricePeriod::YEARLY,
            ]);
        }
    }
}
