<?php

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\PaymentProcessing;

use BitBag\SyliusMolliePlugin\Repository\MollieSubscriptionRepositoryInterface;
use BitBag\SyliusMolliePlugin\Request\StateMachine\StatusRecurringSubscription;
use Payum\Core\Payum;
use Sylius\Component\Core\Model\PaymentInterface;

final class SubscriptionPaymentProcessor implements SubscriptionPaymentProcessorInterface
{
    private MollieSubscriptionRepositoryInterface $subscriptionRepository;

    private Payum $payum;

    public function __construct(
        MollieSubscriptionRepositoryInterface $subscriptionRepository,
        Payum $payum
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->payum = $payum;
    }

    public function processSuccess(PaymentInterface $payment): void
    {
        $this->handlePayment($payment);
    }

    public function processFailed(PaymentInterface $payment): void
    {
        $this->handlePayment($payment);
    }

    private function handlePayment(PaymentInterface $payment): void
    {
        $details = $payment->getDetails();

        if (false === isset($details['metadata']['gateway'])) {
            return;
        }

        $gateway = $this->payum->getGateway($details['metadata']['gateway']);
        $subscriptions = $this->subscriptionRepository->findByPayment($payment);

        foreach ($subscriptions as $subscription) {
            $gateway->execute(new StatusRecurringSubscription($subscription, $details['payment_mollie_id'] ?? null, $payment));
            $this->subscriptionRepository->add($subscription);
        }
    }
}
