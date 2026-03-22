<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\Credential;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Credential pure-logic helpers — no DB required.
 */
class CredentialTest extends TestCase
{
    // ── typeLabel ─────────────────────────────────────────────────────────────

    public function testTypeLabelSshKey(): void
    {
        $this->assertSame('SSH Key', Credential::typeLabel(Credential::TYPE_SSH_KEY));
    }

    public function testTypeLabelUsernamePassword(): void
    {
        $this->assertSame('Username / Password', Credential::typeLabel(Credential::TYPE_USERNAME_PASSWORD));
    }

    public function testTypeLabelVault(): void
    {
        $this->assertSame('Vault Secret', Credential::typeLabel(Credential::TYPE_VAULT));
    }

    public function testTypeLabelToken(): void
    {
        $this->assertSame('Token', Credential::typeLabel(Credential::TYPE_TOKEN));
    }

    public function testTypeLabelUnknownReturnsRaw(): void
    {
        $this->assertSame('custom_type', Credential::typeLabel('custom_type'));
    }

    // ── sensitiveFields ───────────────────────────────────────────────────────

    public function testSensitiveFieldsIsNonEmptyArray(): void
    {
        $fields = Credential::sensitiveFields();
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
    }

    public function testSensitiveFieldsContainsSecretData(): void
    {
        $this->assertContains('secret_data', Credential::sensitiveFields());
    }

    public function testSensitiveFieldsContainsPrivateKey(): void
    {
        $this->assertContains('private_key', Credential::sensitiveFields());
    }

    public function testSensitiveFieldsContainsToken(): void
    {
        $this->assertContains('token', Credential::sensitiveFields());
    }

    // ── constants ─────────────────────────────────────────────────────────────

    public function testConstantsAreStrings(): void
    {
        $this->assertIsString(Credential::TYPE_SSH_KEY);
        $this->assertIsString(Credential::TYPE_USERNAME_PASSWORD);
        $this->assertIsString(Credential::TYPE_VAULT);
        $this->assertIsString(Credential::TYPE_TOKEN);
    }

    public function testConstantsAreUnique(): void
    {
        $types = [
            Credential::TYPE_SSH_KEY,
            Credential::TYPE_USERNAME_PASSWORD,
            Credential::TYPE_VAULT,
            Credential::TYPE_TOKEN,
        ];
        $this->assertSame(count($types), count(array_unique($types)));
    }
}
