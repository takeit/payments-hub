<?php

declare(strict_types=1);

namespace PH\Bundle\CoreBundle\EventListener;

use Combodo\StripeV3\StripeV3GatewayFactory;
use PH\Bundle\PayumBundle\Model\GatewayConfig;
use PH\Component\Core\Model\SubscriptionInterface;
use Stripe\Plan;
use Stripe\Stripe;
use Sylius\Component\Resource\Exception\UnexpectedTypeException;
use Symfony\Component\EventDispatcher\GenericEvent;

final class PopulateSubscriptionBasedOnStripePlanListener
{
    public function populate(GenericEvent $event): void
    {
        /** @var SubscriptionInterface $subject */
        if (!($subject = $event->getSubject()) instanceof SubscriptionInterface) {
            throw new UnexpectedTypeException($subject, SubscriptionInterface::class);
        }

        /** @var GatewayConfig $gatewayConfig */
        $gatewayConfig = $subject->getMethod()->getGatewayConfig();

        if (null === $subject->getPlan() || (!class_exists(StripeV3GatewayFactory::class)
                && StripeV3GatewayFactory::FACTORY_NAME !== $gatewayConfig->getFactoryName())) {
            return;
        }

        $config = $gatewayConfig->getConfig();
        Stripe::setApiKey($config['secretKey']);
        $plan = Plan::retrieve($subject->getPlan());

        $subject->setAmount($plan->amount);
        $subject->setCurrencyCode(strtoupper($plan->currency));
        $subject->setInterval($this->convertInterval($plan->interval));
    }

    private function convertInterval(string $interval): string
    {
        if ('year' === $interval) {
            return '1 year';
        }

        if ('month' === $interval) {
            return '1 month';
        }
    }
}
