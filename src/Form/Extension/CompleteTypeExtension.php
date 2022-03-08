<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Form\Extension;

use BitBag\SyliusMolliePlugin\Factory\MollieSubscriptionGatewayFactory;
use BitBag\SyliusMolliePlugin\Form\Type\DirectDebitType;
use Mollie\Api\Types\PaymentMethod;
use Sylius\Bundle\CoreBundle\Form\Type\Checkout\CompleteType;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

final class CompleteTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var OrderInterface $order */
        $order = $builder->getData();

        /** @var ?PaymentInterface $payment */
        $payment = $order->getLastPayment();
        /** @var PaymentMethodInterface|null $method */
        $method = null !== $payment ? $payment->getMethod() : null;
        $details = null !== $payment ? $payment->getDetails() : [];

        if (
            null !== $method &&
            null !== $method->getGatewayConfig() &&
            MollieSubscriptionGatewayFactory::FACTORY_NAME === $method->getGatewayConfig()->getFactoryName() &&
            true === array_key_exists('molliePaymentMethods', $details) &&
            PaymentMethod::DIRECTDEBIT == $details['molliePaymentMethods']
        ) {
            $builder
                ->add('directDebit', DirectDebitType::class, [
                    'mapped' => false,
                    'validation_groups' => ['sylius'],
                    'constraints' => [
                        new Valid(),
                    ],
                ])
            ;
        }
    }

    public static function getExtendedTypes(): array
    {
        return [CompleteType::class];
    }
}
