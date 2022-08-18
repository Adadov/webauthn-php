<?php

declare(strict_types=1);

namespace Firehed\WebAuthn;

use UnexpectedValueException;

class CreateResponse
{
    public function __construct(
        private BinaryString $id,
        private Attestations\AttestationObject $ao,
        private BinaryString $clientDataJson,
    ) {
    }

    /**
     * Performs the verification described in s7.1: Registering a New
     * Credential
     */
    public function verify(
        Challenge $challenge,
        RelyingParty $rp,
    ): CredentialInterface {
        // 7.1.1 - 7.1.3 are client code
        // 7.1.4 is temporarily skpped
        // 7.1.5 is done in the response parser

        // 7.1.6
        $C = json_decode($this->clientDataJson->unwrap(), true);
        // {"type":"webauthn.create","challenge":"AAECAwQFBgcICQABAgMEBQYHCAkAAQIDBAUGBwgJAAEC","origin":"http://localhost:8888"}

        // 7.1.7
        if ($C['type'] !== 'webauthn.create') {
            $this->fail('7.1.7', 'C.type');
        }

        // 7.1.8
        $b64u = Codecs\Base64Url::encode($challenge->getChallenge());
        if (!hash_equals($b64u, $C['challenge'])) {
            $this->fail('7.1.8', 'C.challenge');
        }

        // 7.1.9
        if (!hash_equals($rp->getOrigin(), $C['origin'])) {
            $this->fail('7.1.9', 'C.origin');
        }

        // 7.1.10
        // TODO: tokenBinding (may not exist on localhost??)

        // 7.1.11
        $hash = new BinaryString(hash('sha256', $this->clientDataJson->unwrap(), true));

        // 7.1.12
        // Happened in response parser
        $authData = $this->ao->data;

        // 7.1.13
        $knownRpIdHash = hash('sha256', $rp->getId(), true);
        if (!hash_equals($knownRpIdHash, $authData->getRpIdHash()->unwrap())) {
            $this->fail('7.1.13', 'authData.rpIdHash');
        }

        // 7.1.14
        if (!$authData->isUserPresent()) {
            $this->fail('7.1.14', 'authData.isUserPresent');
        }

        // 7.1.15
        $isUserVerificationRequired = true; // TODO: where to source this?
        $isUserVerificationRequired = false;
        if ($isUserVerificationRequired && !$authData->isUserVerified()) {
            $this->fail('7.1.15', 'authData.isUserVerified');
        }

        // 7.1.16
        // js options ~ publicKey.pubKeyCredParams[].alg
        // match $authData->ACD->alg (== ECDSA-SHA-256 = -7)

        // 7.1.17
        // TODO: clientExtensionResults / options.extensions

        // 7.1.18
        // Already parsed in AttestationParser::parse upstraem

        // 7.1.19
        // Verification is format-specific.
        // TODO: call attStmt->verify() here?
        $this->ao->verify($hash);

        // 7.1.20
        // get trust anchors for format

        // 7.1.21
        // assess verification result

        // 7.1.22
        // check that credentialId is not registered to another user
        // (done in client code?)

        // 7.1.23
        // associate credential with new user
        // done in client code
        $credential = $authData->getCredential();
        assert($credential !== null);
        // var_dump($this, $challenge, $C, __LINE__);

        return $credential;

        // 7.1.24
        // fail registration if attestation is "verified but is not
        // trustworthy"
    }

    private function fail(string $section, string $desc): never
    {
        throw new UnexpectedValueException(sprintf('%s %s incorrect', $section, $desc));
    }
}
