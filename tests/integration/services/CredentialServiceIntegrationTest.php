<?php

declare(strict_types=1);

namespace app\tests\integration\services;

use app\models\Credential;
use app\services\CredentialService;
use app\tests\integration\DbTestCase;

/**
 * Integration tests for CredentialService encrypt/decrypt round-trip.
 * Requires APP_SECRET_KEY to be set in .env (available in Docker).
 */
class CredentialServiceIntegrationTest extends DbTestCase
{
    private CredentialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = \Yii::$app->get('credentialService');
    }

    public function testStoreAndRetrieveSecretsRoundTrip(): void
    {
        $user       = $this->createUser();
        $credential = $this->createCredential($user->id, Credential::TYPE_SSH_KEY);

        $secrets = ['private_key' => 'FAKEPRIVATEKEY', 'passphrase' => 'secret123'];
        $this->service->storeSecrets($credential, $secrets);

        $retrieved = $this->service->getSecrets($credential);

        $this->assertSame($secrets, $retrieved);
    }

    public function testGetSecretsReturnsEmptyArrayWhenNoSecretData(): void
    {
        $user       = $this->createUser();
        $credential = $this->createCredential($user->id);
        // No secret_data set

        $this->assertSame([], $this->service->getSecrets($credential));
    }

    public function testEncryptedDataIsNotPlaintext(): void
    {
        $user       = $this->createUser();
        $credential = $this->createCredential($user->id);

        $this->service->storeSecrets($credential, ['token' => 'supersecret']);

        $this->assertNotEmpty($credential->secret_data);
        $this->assertStringNotContainsString('supersecret', $credential->secret_data);
    }

    public function testDifferentEncryptionsProduceDifferentCiphertext(): void
    {
        $user = $this->createUser();
        $c1   = $this->createCredential($user->id);
        $c2   = $this->createCredential($user->id);

        $this->service->storeSecrets($c1, ['key' => 'same_value']);
        $this->service->storeSecrets($c2, ['key' => 'same_value']);

        // IV is random so ciphertexts must differ
        $this->assertNotSame($c1->secret_data, $c2->secret_data);
    }

    public function testStoreSecretsOverwritesPreviousSecrets(): void
    {
        $user       = $this->createUser();
        $credential = $this->createCredential($user->id);

        $this->service->storeSecrets($credential, ['key' => 'first']);
        $this->service->storeSecrets($credential, ['key' => 'second']);

        $this->assertSame(['key' => 'second'], $this->service->getSecrets($credential));
    }
}
