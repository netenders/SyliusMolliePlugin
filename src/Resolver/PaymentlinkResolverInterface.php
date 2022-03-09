<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Resolver;

use Mollie\Api\Types\PaymentMethod;
use Sylius\Component\Core\Model\OrderInterface;

interface PaymentlinkResolverInterface
{
    public const NO_AVAILABLE_METHODS = [
        PaymentMethod::KLARNA_PAY_LATER,
        PaymentMethod::KLARNA_SLICE_IT,
    ];

    public function resolve(
        OrderInterface $order,
        array $data,
        string $templateName
    ): string;
}
