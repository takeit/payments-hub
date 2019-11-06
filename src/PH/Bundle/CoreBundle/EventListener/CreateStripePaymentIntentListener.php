<?php

declare(strict_types=1);

namespace PH\Bundle\CoreBundle\EventListener;

use Combodo\StripeV3\StripeV3GatewayFactory;
use http\Exception\RuntimeException;
use PH\Bundle\PayumBundle\Model\GatewayConfig;
use PH\Component\Core\Model\SubscriptionInterface;
use PH\Component\Subscription\Model\Metadata;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Sylius\Component\Resource\Exception\UnexpectedTypeException;
use Symfony\Component\EventDispatcher\GenericEvent;

final class CreateStripePaymentIntentListener
{
    public function createIntent(GenericEvent $event): void
    {
        /** @var SubscriptionInterface $subject */
        if (!($subject = $event->getSubject()) instanceof SubscriptionInterface) {
            throw new UnexpectedTypeException($subject, SubscriptionInterface::class);
        }

        /** @var GatewayConfig $gatewayConfig */
        $gatewayConfig = $subject->getMethod()->getGatewayConfig();

        if (!isset($gatewayConfig->getConfig()['method']) || (isset($gatewayConfig->getConfig()['method'])
            && StripeV3GatewayFactory::PAYMENT_METHOD_PAYMENT_INTENT !== $gatewayConfig->getConfig()['method'])) {
            return;
        }

        $config = $gatewayConfig->getConfig();
        Stripe::setApiKey($config['secretKey']);

        $metadata = $subject->getMetadata();

        if (!isset($metadata['productId']) && '' === $metadata['metadata']['productId']) {
            throw new RuntimeException('The product id has to be set.');
        }

        $intent = PaymentIntent::create([
            'amount' => $subject->getAmount(),
            'currency' => $subject->getCurrencyCode(),
            'payment_method_types' => ['card'],
            'setup_future_usage' => 'off_session',
            'metadata' => [
                'productId' => $metadata['productId'],
            ],
        ]);

        $metadata = new Metadata();
        $metadata->setKey('payment_intent_client_secret');
        $metadata->setValue($intent->client_secret);
        $subject->addMetadata($metadata);
        $metadata = new Metadata();
        $metadata->setKey('payment_intent_id');
        $metadata->setValue($intent->id);
        $subject->addMetadata($metadata);
    }
}
