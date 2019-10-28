<?php

declare(strict_types=1);

namespace PH\Bundle\SubscriptionBundle\Provider;

final class CurrenciesProvider implements CurrenciesProviderInterface
{
    /** @var array $currencies */
    private $currencies;

    public function __construct(array $currencies = [])
    {
        $this->currencies = $currencies;
    }

    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}
