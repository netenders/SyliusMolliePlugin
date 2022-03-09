<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Validator\Constraints;

use BitBag\SyliusMolliePlugin\Entity\GatewayConfigInterface;
use BitBag\SyliusMolliePlugin\Factory\MollieGatewayFactory;
use BitBag\SyliusMolliePlugin\Factory\MollieSubscriptionGatewayFactory;
use BitBag\SyliusMolliePlugin\Repository\PaymentMethodRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator as ConstraintValidatorAlias;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class PaymentMethodMollieChannelUniqueValidator extends ConstraintValidatorAlias
{
    /** @var PaymentMethodRepositoryInterface */
    private $paymentMethodRepository;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        TranslatorInterface $translator
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint): void
    {
        if ($value instanceof PaymentMethodInterface &&
            null !== $value->getCode() &&
            $this->isMolliePaymentMethod($value)
        ) {
            $this->validateMolliePaymentMethod($value, $constraint);
        }
    }

    private function validateMolliePaymentMethod(PaymentMethodInterface $paymentMethod, Constraint $constraint): void
    {
        if (null === $paymentMethod->getCode()) {
            return;
        }
        $molliePaymentMethods = $this->paymentMethodRepository->findAllByFactoryNameAndCode($paymentMethod->getCode());

        if (0 === count($molliePaymentMethods)) {
            return;
        }

        $separatedMethods = [
            MollieSubscriptionGatewayFactory::FACTORY_NAME => [],
            MollieGatewayFactory::FACTORY_NAME => [],
        ];

        /** @var PaymentMethodInterface $method */
        foreach ($molliePaymentMethods as $method) {
            Assert::notNull($method->getGatewayConfig());
            $separatedMethods[$method->getGatewayConfig()->getFactoryName()][] = $method;
        }

        foreach ($separatedMethods as $gatewayName => $methodsCollection) {
            $alreadyUsedChannels = $this->getAlreadyUsedChannels($methodsCollection);

            Assert::notNull($paymentMethod->getGatewayConfig());
            if ($paymentMethod->getGatewayConfig()->getFactoryName() !== $gatewayName) {
                continue;
            }

            if ($this->isTheSameChannel($paymentMethod->getChannels(), $alreadyUsedChannels)) {
                $translation = $this->translator->trans('bitbag_sylius_mollie_plugin.form.channel_should_be_unique', [
                    '{channels}' => $this->getChannelsNameByChannels($alreadyUsedChannels),
                ]);

                $this->context->buildViolation($translation)->atPath('channels')->addViolation()
                ;
            }
        }
    }

    private function getAlreadyUsedChannels(array $molliePaymentMethods): Collection
    {
        $alreadyUsedChannels = new ArrayCollection();

        /** @var PaymentMethodInterface $molliePaymentMethod */
        foreach ($molliePaymentMethods as $molliePaymentMethod) {
            /** @var ChannelInterface $channel */
            foreach ($molliePaymentMethod->getChannels() as $channel) {
                if (!$alreadyUsedChannels->contains($channel)) {
                    $alreadyUsedChannels->add($channel);
                }
            }
        }

        return $alreadyUsedChannels;
    }

    private function isTheSameChannel(Collection $newChannels, Collection $paymentMethodExistingChannels): bool
    {
        foreach ($paymentMethodExistingChannels as $paymentMethodExistingChannel) {
            if ($newChannels->contains($paymentMethodExistingChannel)) {
                return true;
            }
        }

        return false;
    }

    private function isMolliePaymentMethod(PaymentMethodInterface $paymentMethod): bool
    {
        /** @var GatewayConfigInterface $gateway */
        $gateway = $paymentMethod->getGatewayConfig();

        return true === in_array(
            $gateway->getFactoryName(),
            [MollieGatewayFactory::FACTORY_NAME, MollieSubscriptionGatewayFactory::FACTORY_NAME],
            true
        );
    }

    private function getChannelsNameByChannels(Collection $alreadyUsedChannels): string
    {
        $channelsNames = '';

        /** @var ChannelInterface $channel */
        foreach ($alreadyUsedChannels as $channel) {
            $channelsNames .= \sprintf('%s ', $channel->getName());
        }

        return $channelsNames;
    }
}
