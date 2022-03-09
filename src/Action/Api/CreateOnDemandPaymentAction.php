<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Action\Api;

use BitBag\SyliusMolliePlugin\Logger\MollieLoggerActionInterface;
use BitBag\SyliusMolliePlugin\Parser\Response\GuzzleNegativeResponseParserInterface;
use BitBag\SyliusMolliePlugin\Request\Api\CreateOnDemandSubscriptionPayment;
use BitBag\SyliusMolliePlugin\Request\Api\CreateSepaMandate;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Payment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

final class CreateOnDemandPaymentAction extends BaseApiAwareAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;

    private MollieLoggerActionInterface $loggerAction;

    private GuzzleNegativeResponseParserInterface $guzzleNegativeResponseParser;

    public function __construct(
        MollieLoggerActionInterface $loggerAction,
        GuzzleNegativeResponseParserInterface $guzzleNegativeResponseParser
    ) {
        $this->loggerAction = $loggerAction;
        $this->guzzleNegativeResponseParser = $guzzleNegativeResponseParser;
    }

    /** @param CreateSepaMandate|mixed $request */
    public function execute($request): void
    {
        $details = ArrayObject::ensureArrayObject($request->getModel());

        try {
            $paymentSettings = [
                'method' => $details['metadata']['molliePaymentMethods'],
                'issuer' => $details['metadata']['selected_issuer'] ?? null,
                'cardToken' => $details['metadata']['cartToken'],
                'amount' => $details['amount'],
                'customerId' => $details['customerId'] ?? null,
                'description' => $details['description'],
                'webhookUrl' => $details['webhookUrl'],
                'metadata' => $details['metadata'],
                'mandateId' => $details['mandateId'],
                'sequenceType' => 'recurring',
            ];
            /** @var Payment $payment */
            $payment = $this->mollieApiClient->payments->create($paymentSettings);
        } catch (ApiException $e) {
            $message = $this->guzzleNegativeResponseParser->parse($e);
            $this->loggerAction->addNegativeLog(sprintf('Error with create payment with: %s', $e->getMessage()));

            if ('' === $message) {
                throw new ApiException(sprintf('Error with create payment with: %s', $e->getMessage()));
            }

            $details['statusError'] = $message;

            return;
        } catch (\Exception $e) {
            $this->loggerAction->addNegativeLog(sprintf('Error with create payment with: %s', $e->getMessage()));

            throw new ApiException(sprintf('Error with create payment with: %s', $e->getMessage()));
        }

        $details['payment_mollie_id'] = $payment->id;

        $this->loggerAction->addLog(sprintf('Create payment in mollie with id: %s', $payment->id));
    }

    public function supports($request): bool
    {
        if (
            false === $request instanceof CreateOnDemandSubscriptionPayment
            || false === $request->getModel() instanceof \ArrayAccess) {
            return false;
        }
        $details = ArrayObject::ensureArrayObject($request->getModel());

        return 'recurring' === ($details['metadata']['sequenceType'] ?? 'first');
    }
}
