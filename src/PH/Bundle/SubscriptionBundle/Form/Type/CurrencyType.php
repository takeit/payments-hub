<?php

declare(strict_types=1);

namespace PH\Bundle\SubscriptionBundle\Form\Type;

use PH\Bundle\SubscriptionBundle\Provider\CurrenciesProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType as BaseCurrencyType;

final class CurrencyType extends AbstractType
{
    /**
     * @var CurrenciesProviderInterface
     */
    private $currenciesProvider;

    public function __construct(CurrenciesProviderInterface $currenciesProvider)
    {
        $this->currenciesProvider = $currenciesProvider;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $intlCurrencies = array_flip(Intl::getCurrencyBundle()->getCurrencyNames());
        $allowedCurrencies = $this->currenciesProvider->getCurrencies();

        $choices = [];
        foreach ($allowedCurrencies as $allowedCurrency) {
            foreach ($intlCurrencies as $name => $intlCurrency) {
                if ($allowedCurrency === $intlCurrency) {
                    $choices[$name] = $intlCurrency;
                }
            }
        }

        $resolver
            ->setDefaults([
                'choices' => $choices,
                'choice_loader' => null,
            ])
        ;
    }

    public function getParent(): string
    {
        return BaseCurrencyType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ph_currency_choice';
    }
}
