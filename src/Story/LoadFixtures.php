<?php

namespace App\Story;

use App\Enum\ProductPricePeriod;
use App\Factory\{ProductFactory, ProductPriceFactory, SubscriptionFactory, UserFactory};
use DateTimeImmutable;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'main')]
final class LoadFixtures extends Story
{
    public function build(): void
    {
        $users = UserFactory::createMany(10);
        $products = ProductFactory::createMany(3);

        $prices = [];
        foreach ($products as $product) {
            $prices[] = ProductPriceFactory::createOne([
                'product' => $product,
                'pricePeriod' => ProductPricePeriod::MONTHLY,
            ]);
            ProductPriceFactory::createOne([
                'product' => $product,
                'pricePeriod' => ProductPricePeriod::YEARLY,
            ]);
        }

        $firstPrice = $prices[0];

        SubscriptionFactory::createOne([
            'user' => $users[0],
            'productPrice' => $firstPrice,
        ]);

        SubscriptionFactory::createOne([
            'user' => $users[1],
            'productPrice' => $firstPrice,
        ]);

        SubscriptionFactory::createOne([
            'user' => $users[1],
            'productPrice' => $firstPrice,
            'endDate' => new DateTimeImmutable('2022-01-01'),
        ]);
    }
}
