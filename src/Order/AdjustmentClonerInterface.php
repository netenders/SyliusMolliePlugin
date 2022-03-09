<?php

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Order;

use Sylius\Component\Core\Model\AdjustmentInterface as BaseAdjustmentInterface;

interface AdjustmentClonerInterface
{
    public function clone(BaseAdjustmentInterface $adjustment): BaseAdjustmentInterface;
}
