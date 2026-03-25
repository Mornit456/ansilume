<?php

declare(strict_types=1);

namespace app\models;

use app\services\TotpService;
use yii\base\Model;

/**
 * Form model for disabling TOTP 2FA.
 * Requires a valid TOTP code or recovery code to confirm.
 */
class TotpDisableForm extends Model
{
    public string $code = '';

    private User $_user;

    public function __construct(User $user, array $config = [])
    {
        $this->_user = $user;
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['code'], 'required'],
            [['code'], 'string', 'max' => 12],
            [['code'], 'validateCode'],
        ];
    }

    public function attributeLabels(): array
    {
        return ['code' => 'Current Code'];
    }

    public function validateCode(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        /** @var TotpService $totp */
        $totp = \Yii::$app->get('totpService');

        $secret = $totp->getUserSecret($this->_user);
        if ($secret === null) {
            $this->addError($attribute, 'Two-factor authentication is not configured.');
            return;
        }

        $code = trim($this->code);

        // Try TOTP code (6 digits)
        if (preg_match('/^\d{6}$/', $code) && $totp->verifyCode($secret, $code)) {
            return;
        }

        // Try recovery code
        if ($totp->verifyRecoveryCode($code, $totp->getStoredRecoveryCodes($this->_user)) >= 0) {
            return;
        }

        $this->addError($attribute, 'Invalid code. Enter a current authenticator code or a valid recovery code.');
    }

    /**
     * Disable TOTP for the user after validation passes.
     */
    public function disable(): void
    {
        /** @var TotpService $totp */
        $totp = \Yii::$app->get('totpService');
        $totp->disable($this->_user);
    }

    public function getUser(): User
    {
        return $this->_user;
    }
}
