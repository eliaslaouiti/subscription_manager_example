<?php

namespace App\Factory;

use App\Entity\Subscription;
use Override;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Subscription>
 */
final class SubscriptionFactory extends PersistentObjectFactory
{
    #[Override]
    public static function class(): string
    {
        return Subscription::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'productPrice' => ProductPriceFactory::new(),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
