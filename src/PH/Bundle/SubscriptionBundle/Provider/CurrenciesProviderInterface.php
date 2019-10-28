<?php

declare(strict_types=1);

namespace PH\Bundle\SubscriptionBundle\Provider;

interface CurrenciesProviderInterface
{
    public function getCurrencies(): array;
}
