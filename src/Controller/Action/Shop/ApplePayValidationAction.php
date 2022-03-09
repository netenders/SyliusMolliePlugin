<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Controller\Action\Shop;

use BitBag\SyliusMolliePlugin\Checker\ApplePay\ApplePayEnabledCheckerInterface;
use BitBag\SyliusMolliePlugin\Logger\MollieLoggerActionInterface;
use BitBag\SyliusMolliePlugin\Resolver\MollieApiClientKeyResolverInterface;
use Mollie\Api\Exceptions\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplePayValidationAction
{
    /** @var MollieLoggerActionInterface */
    private $loggerAction;

    /** @var MollieApiClientKeyResolverInterface */
    private $apiClientKeyResolver;

    /** @var ApplePayEnabledCheckerInterface */
    private $applePayEnabledChecker;

    public function __construct(
        MollieLoggerActionInterface $loggerAction,
        MollieApiClientKeyResolverInterface $apiClientKeyResolver,
        ApplePayEnabledCheckerInterface $applePayEnabledChecker
    ) {
        $this->loggerAction = $loggerAction;
        $this->apiClientKeyResolver = $apiClientKeyResolver;
        $this->applePayEnabledChecker = $applePayEnabledChecker;
    }

    public function __invoke(Request $request): Response
    {
        if (false === $this->applePayEnabledChecker->isEnabled()) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }

        $validateUrl = $request->get('validationUrl');

        if (null === $validateUrl) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $url = $request->getHost();
        /** @var string[] $domain */
        $domain = parse_url($url);

        try {
            $mollieClient = $this->apiClientKeyResolver->getClientWithKey();

            $json = $mollieClient->wallets->requestApplePayPaymentSession($domain['path'], $validateUrl);
        } catch (ApiException $e) {
            $this->loggerAction->addNegativeLog(\sprintf('Error with validate apple pay with: %s', $e->getMessage()));

            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }

        $response = [
            'success' => true,
            'data' => $json,
        ];

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
