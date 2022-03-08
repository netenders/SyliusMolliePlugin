<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Entity;

class MollieMethodImage implements MollieMethodImageInterface
{
    /** @var int|null */
    protected $id;

    /** @var string|null */
    protected $name;

    /** @var \SplFileInfo|null */
    protected $file;

    /** @var string|null */
    protected $path;

    /** @var MollieGatewayConfigInterface */
    protected $mollieGatewayConfig;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getFile(): ?\SplFileInfo
    {
        return $this->file;
    }

    public function setFile(?\SplFileInfo $file): void
    {
        $this->file = $file;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getMollieGatewayConfig(): MollieGatewayConfigInterface
    {
        return $this->mollieGatewayConfig;
    }

    public function setMollieGatewayConfig(MollieGatewayConfigInterface $mollieGatewayConfig): void
    {
        $this->mollieGatewayConfig = $mollieGatewayConfig;
    }

    public function hasFile(): bool
    {
        return null !== $this->file;
    }
}
