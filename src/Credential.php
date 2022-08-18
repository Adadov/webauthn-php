<?php

declare(strict_types=1);

namespace Firehed\WebAuthn;

/**
 * @internal
 */
class Credential implements CredentialInterface
{
    public function __construct(
        private readonly BinaryString $id,
        private readonly COSEKey $coseKey,
        private readonly int $signCount,
    ) {
    }

    public function getSafeId(): string
    {
        return bin2hex($this->id->unwrap());
    }

    public function getSignCount(): int
    {
        return $this->signCount;
    }

    public function getId(): BinaryString
    {
        return $this->id;
    }

    public function getCoseCbor(): BinaryString
    {
        return $this->coseKey->cbor;
    }

    public function getPublicKey(): PublicKey\PublicKeyInterface
    {
        return $this->coseKey->getPublicKey();
    }
}
