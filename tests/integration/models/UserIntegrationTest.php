<?php

declare(strict_types=1);

namespace app\tests\integration\models;

use app\models\User;
use app\tests\integration\DbTestCase;

class UserIntegrationTest extends DbTestCase
{
    public function testFindIdentityReturnsActiveUser(): void
    {
        $user = $this->createUser();

        $found = User::findIdentity($user->id);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function testFindIdentityReturnsNullForInactiveUser(): void
    {
        $user = $this->createUser();
        \Yii::$app->db->createCommand()
            ->update('{{%user}}', ['status' => User::STATUS_INACTIVE], ['id' => $user->id])
            ->execute();

        $this->assertNull(User::findIdentity($user->id));
    }

    public function testFindIdentityReturnsNullForUnknownId(): void
    {
        $this->assertNull(User::findIdentity(999999));
    }

    public function testFindByUsernameReturnsActiveUser(): void
    {
        $user = $this->createUser('lookup');

        $found = User::findByUsername($user->username);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function testFindByUsernameReturnsNullForUnknownUsername(): void
    {
        $this->assertNull(User::findByUsername('no_such_user_xyzabc'));
    }

    public function testValidatePasswordReturnsTrueForCorrectPassword(): void
    {
        $user = $this->createUser();
        // createUser() sets password hash for "test"

        $this->assertTrue($user->validatePassword('test'));
    }

    public function testValidatePasswordReturnsFalseForWrongPassword(): void
    {
        $user = $this->createUser();

        $this->assertFalse($user->validatePassword('wrongpassword'));
    }

    public function testSetPasswordChangesHash(): void
    {
        $user = $this->createUser();
        $oldHash = $user->password_hash;

        $user->setPassword('newpassword123');

        $this->assertNotSame($oldHash, $user->password_hash);
        $this->assertTrue($user->validatePassword('newpassword123'));
    }

    public function testGenerateAuthKeyProducesNonEmptyString(): void
    {
        $user = new User();
        $user->generateAuthKey();

        $this->assertNotEmpty($user->auth_key);
    }

    public function testValidateAuthKeyReturnsTrueForMatchingKey(): void
    {
        $user = $this->createUser();

        $this->assertTrue($user->validateAuthKey($user->auth_key));
    }

    public function testValidateAuthKeyReturnsFalseForWrongKey(): void
    {
        $user = $this->createUser();

        $this->assertFalse($user->validateAuthKey('wrong_key'));
    }

    public function testGetIdReturnsIntegerId(): void
    {
        $user = $this->createUser();

        $this->assertIsInt($user->getId());
        $this->assertSame($user->id, $user->getId());
    }

    public function testGetAuthKeyReturnsAuthKey(): void
    {
        $user = $this->createUser();

        $this->assertSame($user->auth_key, $user->getAuthKey());
    }

    public function testIsActiveReturnsTrueForActiveStatus(): void
    {
        $user = $this->createUser();
        // createUser sets status to 'active' (STATUS_ACTIVE = 10)

        $this->assertTrue($user->isActive());
    }

    public function testFindIdentityByAccessTokenReturnsNullForInvalidToken(): void
    {
        $found = User::findIdentityByAccessToken(str_repeat('x', 64));
        $this->assertNull($found);
    }
}
