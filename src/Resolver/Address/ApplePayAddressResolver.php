<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Resolver\Address;

use Sylius\Component\Core\Model\OrderInterface;

final class ApplePayAddressResolver implements ApplePayAddressResolverInterface
{
    /** @var AddressResolverInterface */
    private $addressResolver;

    public function __construct(AddressResolverInterface $addressResolver)
    {
        $this->addressResolver = $addressResolver;
    }

    public function resolve(OrderInterface $order, array $applePayData): void
    {
        $appleShippingAddress = $this->addressResolver->resolve($applePayData['shippingContact']);
        $appleBillingAddress = $this->addressResolver->resolve($applePayData['billingContact']);

        try {
            $order->setShippingAddress($appleShippingAddress);
            $order->setBillingAddress($appleBillingAddress);
        } catch (\Exception $e) {
            throw new \Exception(\sprintf('Some error with create address to order'));
        }
    }
}
