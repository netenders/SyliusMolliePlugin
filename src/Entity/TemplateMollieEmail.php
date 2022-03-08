<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace BitBag\SyliusMolliePlugin\Entity;

use Sylius\Component\Resource\Model\TranslatableTrait;
use Sylius\Component\Resource\Model\TranslationInterface;

class TemplateMollieEmail implements TemplateMollieEmailInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    /** @var int|null */
    protected $id;

    /** @var string|null */
    protected $type;

    /** @var string|null */
    protected $styleCss;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getStyleCss(): ?string
    {
        return $this->styleCss;
    }

    public function setStyleCss(?string $styleCss): void
    {
        $this->styleCss = $styleCss;
    }

    public function getName(): ?string
    {
        /** @var TemplateMollieEmailTranslationInterface $translation */
        $translation = $this->getBlockTranslation();

        return $translation->getName();
    }

    public function setName(?string $name): void
    {
        /** @var TemplateMollieEmailTranslationInterface $translation */
        $translation = $this->getBlockTranslation();

        $translation->setName($name);
    }

    public function getSubject(): ?string
    {
        /** @var TemplateMollieEmailTranslationInterface $translation */
        $translation = $this->getBlockTranslation();

        return $translation->getSubject();
    }

    public function setSubject(?string $subject): void
    {
        /** @var TemplateMollieEmailTranslationInterface $translation */
        $translation = $this->getBlockTranslation();

        $translation->setSubject($subject);
    }

    public function getContent(): ?string
    {
        /** @var TemplateMollieEmailTranslationInterface $translation */
        $translation = $this->getBlockTranslation();

        return $translation->getContent();
    }

    public function setContent(?string $content): void
    {
        /** @var TemplateMollieEmailTranslationInterface $translation */
        $translation = $this->getBlockTranslation();

        $translation->setContent($content);
    }

    protected function getBlockTranslation(): TranslationInterface
    {
        return $this->getTranslation();
    }

    protected function createTranslation(): TemplateMollieEmailTranslation
    {
        return new TemplateMollieEmailTranslation();
    }
}
