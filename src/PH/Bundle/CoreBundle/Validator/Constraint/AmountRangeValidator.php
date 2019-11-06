<?php

declare(strict_types=1);

namespace PH\Bundle\CoreBundle\Validator\Constraint;

use PH\Component\Subscription\Model\SubscriptionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AmountRangeValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AmountRange) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\AmountRange');
        }

        if (null === $value) {
            return;
        }

        if (null === $value->getMethod()) {
            return;
        }

        if (SubscriptionInterface::TYPE_RECURRING === $value->getType()) {
            if (SubscriptionInterface::MODE_PLAN_BASED === $value->getMode()) {
                if ($value->getAmount() > 0) {
                    $this->context->buildViolation('The amount field must not be set in this mode.')
                        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                        ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                        ->atPath('amount')
                        ->addViolation();
                }

                if (null !== $value->getInterval()) {
                    $this->context->buildViolation('The interval field must not be set in this mode.')
                        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                        ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                        ->atPath('interval')
                        ->addViolation();
                }

                if ('' !== $value->getCurrencyCode()) {
                    $this->context->buildViolation('The currency code field must not be set in this mode.')
                        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                        ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                        ->atPath('currencyCode')
                        ->addViolation();
                }

                if (null === $value->getPlan()) {
                    $this->context->buildViolation('The plan field must be set in this mode.')
                        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                        ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                        ->atPath('plan')
                        ->addViolation();
                }

                return;
            }

            if (null !== $value->getPlan() && in_array($value->getMode(), [SubscriptionInterface::MODE_DONATION, SubscriptionInterface::MODE_SUBSCRIPTION], true)) {
                $this->context->buildViolation('The plan field must not be set in this mode.')
                    ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                    ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                    ->atPath('plan')
                    ->addViolation();

                return;
            }

            if (null === $value->getInterval() && in_array($value->getMode(), [SubscriptionInterface::MODE_DONATION, SubscriptionInterface::MODE_SUBSCRIPTION], true)) {
                $this->context->buildViolation('The interval field must be set in this mode.')
                        ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                        ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                        ->atPath('interval')
                        ->addViolation();

                return;
            }

            if (0 === $value->getAmount() && in_array($value->getMode(), [SubscriptionInterface::MODE_DONATION, SubscriptionInterface::MODE_SUBSCRIPTION], true)) {
                $this->context->buildViolation('The amount field must be set in this mode.')
                    ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                    ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                    ->atPath('amount')
                    ->addViolation();

                return;
            }

            if ('' === $value->getCurrencyCode() && in_array($value->getMode(), [SubscriptionInterface::MODE_DONATION, SubscriptionInterface::MODE_SUBSCRIPTION], true)) {
                $this->context->buildViolation('The currency code field must be set in this mode.')
                    ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                    ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                    ->atPath('currencyCode')
                    ->addViolation();

                return;
            }
        }

        if (!is_numeric($value->getAmount())) {
            $this->context->buildViolation($constraint->invalidMessage)
                ->setParameter('{{ value }}', $this->formatValue($value, self::PRETTY_DATE))
                ->setCode(AmountRange::INVALID_CHARACTERS_ERROR)
                ->atPath('amount')
                ->addViolation();

            return;
        }

        $gatewayConfig = $value->getMethod()->getGatewayConfig()->getConfig();

        if (!isset($gatewayConfig['minAmount']) && !isset($gatewayConfig['maxAmount'])) {
            return;
        }

        $min = $gatewayConfig['minAmount'];
        $max = $gatewayConfig['maxAmount'];

        if (null !== $max && null !== $value->getAmount() && $value->getAmount() > $max) {
            $this->context->buildViolation($constraint->maxMessage)
                ->setParameter('{{ value }}', $this->formatValue($value->getAmount() / 100, self::PRETTY_DATE))
                ->setParameter('{{ limit }}', $this->formatValue($max / 100, self::PRETTY_DATE))
                ->setCode(AmountRange::TOO_HIGH_ERROR)
                ->atPath('amount')
                ->addViolation();

            return;
        }

        if (null !== $min && null !== $value->getAmount() && $value->getAmount() < $min) {
            $this->context->buildViolation($constraint->minMessage)
                ->setParameter('{{ value }}', $this->formatValue($value->getAmount() / 100, self::PRETTY_DATE))
                ->setParameter('{{ limit }}', $this->formatValue($min / 100, self::PRETTY_DATE))
                ->setCode(AmountRange::TOO_LOW_ERROR)
                ->atPath('amount')
                ->addViolation();
        }
    }
}
