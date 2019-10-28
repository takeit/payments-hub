<?php

declare(strict_types=1);

namespace PH\Bundle\SubscriptionBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use PH\Component\Subscription\Model\Metadata;
use PH\Component\Subscription\Model\SubscriptionInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class SubscriptionType extends AbstractResourceType
{
    public function __construct(string $dataClass, array $validationGroups = [])
    {
        parent::__construct($dataClass, $validationGroups);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', MoneyType::class, [
                'currency' => false,
            ])
            ->add('mode', ChoiceType::class, [
                'choices' => [
                    'Subscription' => SubscriptionInterface::MODE_SUBSCRIPTION,
                    'Donation' => SubscriptionInterface::MODE_DONATION,
                    'Plan based' => SubscriptionInterface::MODE_PLAN_BASED,
                ],
            ])
            ->add('currencyCode', CurrencyType::class)
            ->add('plan', TextType::class)
            ->add('interval', IntervalChoiceType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Non-recurring' => SubscriptionInterface::TYPE_NON_RECURRING,
                    'Recurring' => SubscriptionInterface::TYPE_RECURRING,
                ],
            ])
            ->add('startDate', StartDateChoiceType::class)
        ;

        $builder->get('startDate')
            ->addModelTransformer(new CallbackTransformer(
                static function ($value) {
                    return $value;
                },
                static function ($value) {
                    if (is_string($value)) {
                        return new \DateTime($value);
                    }
                }
            ))
        ;

        $builder
            ->add('metadata', UnstructuredType::class);

        $builder->get('metadata')->addModelTransformer(new CallbackTransformer(
            static function ($value) {
                return $value;
            },
            static function ($value) {
                $collection = new ArrayCollection();

                foreach ((array) $value as $key => $item) {
                    $metadata = new Metadata();
                    $metadata->setKey($key);
                    $metadata->setValue($item);
                    $collection->add($metadata);
                }

                return $collection;
            }
        ));

        $builder->get('amount')->addModelTransformer(new CallbackTransformer(
            static function ($value) {
                return $value;
            },
            static function ($value) {
                return $value ?? 0;
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'ph_subscription';
    }
}
