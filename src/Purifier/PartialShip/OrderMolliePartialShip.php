<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Purifier\PartialShip;

use BitBag\SyliusMolliePlugin\Client\MollieApiClient;
use BitBag\SyliusMolliePlugin\Factory\MollieGatewayFactory;
use BitBag\SyliusMolliePlugin\Form\Type\MollieGatewayConfigurationType;
use BitBag\SyliusMolliePlugin\Logger\MollieLoggerActionInterface;
use BitBag\SyliusMolliePlugin\Resolver\PartialShip\FromSyliusToMollieLinesResolverInterface;
use Doctrine\Common\Collections\Collection;
use Mollie\Api\Exceptions\ApiException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Webmozart\Assert\Assert;

final class OrderMolliePartialShip implements OrderMolliePartialShipInterface
{
    /** @var MollieApiClient */
    private $apiClient;

    /** @var MollieLoggerActionInterface */
    private $loggerAction;

    /** @var FromSyliusToMollieLinesResolverInterface */
    private $mollieUnitsResolver;

    public function __construct(
        MollieApiClient $apiClient,
        MollieLoggerActionInterface $loggerAction,
        FromSyliusToMollieLinesResolverInterface $mollieUnitsResolver
    ) {
        $this->apiClient = $apiClient;
        $this->loggerAction = $loggerAction;
        $this->mollieUnitsResolver = $mollieUnitsResolver;
    }

    public function partialShip(OrderInterface $order): void
    {
        /** @var Collection $shipments */
        $shipments = $order->getShipments();
        $units = $shipments->last()->getUnits();

        if ($units->isEmpty()) {
            return;
        }

        $payment = $order->getLastPayment();
        Assert::notNull($payment);

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();

        if (null === $paymentMethod->getGatewayConfig()) {
            return;
        }
        $factoryName = $paymentMethod->getGatewayConfig()->getFactoryName() ?? null;

        if (!isset($payment->getDetails()['order_mollie_id']) || MollieGatewayFactory::FACTORY_NAME !== $factoryName) {
            return;
        }

        $modusKey = $this->getModus($paymentMethod->getGatewayConfig()->getConfig());

        try {
            $this->apiClient->setApiKey($modusKey);
            $mollieOrder = $this->apiClient->orders->get($payment->getDetails()['order_mollie_id']);

            $lines = $this->mollieUnitsResolver->resolve($units, $mollieOrder);

            $mollieOrder->createShipment(['lines' => $lines->getArrayFromObject()]);

            $this->loggerAction->addLog(sprintf('Partial ship with order id %s: ', $mollieOrder->id));
        } catch (ApiException $e) {
            $this->loggerAction->addNegativeLog(sprintf('Error partial ship with message %s: ', $e->getMessage()));
        }
    }

    private function getModus(array $config): string
    {
        if ($config['environment']) {
            return $config[MollieGatewayConfigurationType::API_KEY_LIVE];
        }

        return $config[MollieGatewayConfigurationType::API_KEY_TEST];
    }
}
