<?php

declare(strict_types=1);

namespace PH\Bundle\PayumBundle\Extension;

use Combodo\StripeV3\Action\CheckoutCompletedInformationProvider;
use Combodo\StripeV3\Request\handleCheckoutCompletedEvent;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Security\HttpRequestVerifierInterface;
use Payum\Core\Security\TokenInterface;
use PH\Component\Core\PaymentTransitions;
use Psr\Log\LoggerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class StripeCheckoutCompletedExtension implements ExtensionInterface
{
    /** @var FactoryInterface */
    private $factory;

    /** @var HttpRequestVerifierInterface $httpRequestVerifier */
    private $httpRequestVerifier;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(FactoryInterface $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function setHttpRequestVerifier(HttpRequestVerifierInterface $httpRequestVerifier): void
    {
        if (null !== $this->httpRequestVerifier) {
            throw new \LogicException(__METHOD__.' is not meant to be called outside of the dependency injection!');
        }

        $this->httpRequestVerifier = $httpRequestVerifier;
    }

    public function onPreExecute(Context $context): void
    {
    }

    public function onExecute(Context $context): void
    {
    }

    public function onPostExecute(Context $context): void
    {
        $action = $context->getAction();

        if (!$action instanceof CheckoutCompletedInformationProvider) {
            return;
        }
        if (null !== $context->getException()) {
            return;
        }

        $token = $action->getToken();
        $status = $action->getStatus();

        if (empty($token)) {
            throw new BadRequestHttpException('The token provided was not found! (see previous exceptions)');
        }

        if (empty($status)) {
            throw new \LogicException('The request status could not be retrieved! (see previous exceptions)');
        }

        if (!$status->isCaptured()) {
            return;
        }

        $payment = $status->getFirstModel();
        $this->runPaymentWorkflow($payment);
        $this->invalidateToken($context, $token);
    }

    private function runPaymentWorkflow(PaymentInterface $payment): void
    {
        if (PaymentInterface::STATE_COMPLETED !== $payment->getState()) {
            $this->applyTransition($payment, PaymentInterface::STATE_COMPLETED);
        } else {
            $this->logger->debug(
                'Transition skipped',
                [
                    'target state' => PaymentInterface::STATE_COMPLETED,
                    'payment object' => $payment,
                ]
            );
        }
    }

    private function applyTransition(PaymentInterface $payment, string $nextState): void
    {
        $stateMachine = $this->factory->get($payment, PaymentTransitions::GRAPH);

        if (null !== $transition = $stateMachine->getTransitionToState($nextState)) {
            $stateMachine->apply($transition);
            $this->logger->debug('Transition applied', [
                'next state' => $nextState,
                'transition' => $transition,
                'payment object' => $payment,
            ]);
        } else {
            $this->logger->debug('No Transition to apply ?!?', [
                'next state' => $nextState,
                'transition' => $transition,
                'payment object' => $payment,
            ]);
        }
    }

    private function invalidateToken(Context $context, TokenInterface $token): void
    {
        /** @var handleCheckoutCompletedEvent $request */
        $request = $context->getRequest();
        //dump($request);die;
        if ($request->canTokenBeInvalidated()) {
            //$this->httpRequestVerifier->invalidate($token);
        } else {
            $this->logger->debug('The request asked to keep the token, it was not invalidated');
        }
    }
}
