<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\DTO;

use Mollie\Api\Resources\MethodCollection;

final class ApiKeyTest
{
    /** @var string */
    private $type;

    /** @var bool */
    private $key;

    /** @var MethodCollection|null */
    private $methods;

    /** @var string */
    private $status;

    /** @var string */
    private $message;

    public function __construct(
        string $type,
        bool $key = false,
        MethodCollection $methods = null,
        string $status = 'OK',
        string $message = ''
    ) {
        $this->type = $type;
        $this->key = $key;
        $this->methods = $methods;
        $this->status = $status;
        $this->message = $message;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): bool
    {
        return $this->key;
    }

    public function getMethods(): ?MethodCollection
    {
        return $this->methods;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setKey(bool $key): void
    {
        $this->key = $key;
    }

    public function setMethods(?MethodCollection $methods): void
    {
        $this->methods = $methods;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
