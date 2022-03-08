<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Controller\Action\Admin;

use BitBag\SyliusMolliePlugin\Factory\MollieGatewayFactory;
use BitBag\SyliusMolliePlugin\Factory\MollieSubscriptionGatewayFactory;
use BitBag\SyliusMolliePlugin\Logger\MollieLoggerActionInterface;
use BitBag\SyliusMolliePlugin\Request\Api\RefundOrder;
use Doctrine\ORM\EntityManagerInterface;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Payum;
use Payum\Core\Request\Refund as RefundAction;
use Payum\Core\Security\TokenInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Resource\Exception\UpdateHandlingException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class Refund
{
    /** @var PaymentRepositoryInterface */
    private $paymentRepository;

    /** @var Payum */
    private $payum;

    /** @var Session */
    private $session;

    /** @var FactoryInterface */
    private $stateMachineFactory;

    /** @var EntityManagerInterface */
    private $paymentEntityManager;

    /** @var MollieLoggerActionInterface */
    private $loggerAction;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        Payum $payum,
        Session $session,
        FactoryInterface $stateMachineFactory,
        EntityManagerInterface $paymentEntityManager,
        MollieLoggerActionInterface $loggerAction
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->payum = $payum;
        $this->session = $session;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->paymentEntityManager = $paymentEntityManager;
        $this->loggerAction = $loggerAction;
    }

    public function __invoke(Request $request): Response
    {
        /** @var PaymentInterface|null $payment */
        $payment = $this->paymentRepository->find($request->get('id'));

        if (null === $payment) {
            $this->loggerAction->addNegativeLog(sprintf('Not fount payment in refund'));

            throw new NotFoundHttpException();
        }

        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $payment->getMethod();

        /** @var GatewayConfigInterface $gatewayConfig */
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        $factoryName = $gatewayConfig->getFactoryName() ?? null;

        if (false === in_array($factoryName, [MollieGatewayFactory::FACTORY_NAME, MollieSubscriptionGatewayFactory::FACTORY_NAME], true)) {
            $this->applyStateMachineTransition($payment);

            $this->session->getFlashBag()->add('success', 'sylius.payment.refunded');
            $this->loggerAction->addLog(sprintf('Refunded successfully'));

            return $this->redirectToReferer($request);
        }
        if (
            (!isset($payment->getDetails()['payment_mollie_id']) || !isset($payment->getDetails()['metadata']['refund_token'])) &&
            !isset($payment->getDetails()['order_mollie_id'])
        ) {
            $this->applyStateMachineTransition($payment);

            $this->session->getFlashBag()->add('info', 'bitbag_sylius_mollie_plugin.ui.refunded_only_locally');
            $this->loggerAction->addLog(sprintf('Refunded only locally'));

            return $this->redirectToReferer($request);
        }

        $hash = $payment->getDetails()['metadata']['refund_token'];

        /** @var TokenInterface|null $token */
        $token = $this->payum->getTokenStorage()->find($hash);

        if (!$token instanceof TokenInterface) {
            $this->loggerAction->addNegativeLog(sprintf('A token with hash `%s` could not be found.', $hash));

            throw new BadRequestHttpException(sprintf('A token with hash `%s` could not be found.', $hash));
        }

        $gateway = $this->payum->getGateway($token->getGatewayName());

        try {
            if (isset($payment->getDetails()['order_mollie_id'])) {
                $gateway->execute(new RefundOrder($token));
            } else {
                $gateway->execute(new RefundAction($token));
            }

            $this->applyStateMachineTransition($payment);

            $this->session->getFlashBag()->add('success', 'sylius.payment.refunded');
        } catch (UpdateHandlingException $e) {
            $this->loggerAction->addNegativeLog(sprintf('Error with refund: %s', $e->getMessage()));
            $this->session->getFlashBag()->add('error', $e->getMessage());
        }

        return $this->redirectToReferer($request);
    }

    private function applyStateMachineTransition(PaymentInterface $payment): void
    {
        $stateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);

        if (!$stateMachine->can(PaymentTransitions::TRANSITION_REFUND)) {
            throw new BadRequestHttpException();
        }

        $stateMachine->apply(PaymentTransitions::TRANSITION_REFUND);

        $this->paymentEntityManager->flush();
    }

    private function redirectToReferer(Request $request): Response
    {
        /** @var string $url */
        $url = $request->headers->get('referer');

        return new RedirectResponse($url);
    }
}
