<?php
/**
 * Copyright (C) 2023 Pixel Développement
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PixelOpen\CloudflareTurnstile\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public const TURNSTILE_CONFIG_PATH_SECRET_KEY = 'pixel_open_cloudflare_turnstile/settings/secret_key';
    public const TURNSTILE_CONFIG_PATH_SITEKEY = 'pixel_open_cloudflare_turnstile/settings/sitekey';

    public const TURNSTILE_CONFIG_PATH_FRONTEND_ENABLED = 'pixel_open_cloudflare_turnstile/frontend/enabled';
    public const TURNSTILE_CONFIG_PATH_FRONTEND_THEME = 'pixel_open_cloudflare_turnstile/frontend/theme';
    public const TURNSTILE_CONFIG_PATH_FRONTEND_SIZE = 'pixel_open_cloudflare_turnstile/frontend/size';
    public const TURNSTILE_CONFIG_PATH_FRONTEND_FORMS = 'pixel_open_cloudflare_turnstile/frontend/forms';

    public const TURNSTILE_CONFIG_PATH_ADMINHTML_ENABLED = 'pixel_open_cloudflare_turnstile/adminhtml/enabled';
    public const TURNSTILE_CONFIG_PATH_ADMINHTML_THEME = 'pixel_open_cloudflare_turnstile/adminhtml/theme';
    public const TURNSTILE_CONFIG_PATH_ADMINHTML_SIZE = 'pixel_open_cloudflare_turnstile/adminhtml/size';
    public const TURNSTILE_CONFIG_PATH_ADMINHTML_FORMS = 'pixel_open_cloudflare_turnstile/adminhtml/forms';

    /**
     * Magento ciphertext is "<keyVersion>:<cipherVersion>:<base64>". Used to
     * tell an encrypted value from one still stored in clear, because BOTH
     * exist: the field only gained its Encrypted backend model in this change,
     * so a store holds plaintext until an admin re-saves it or a deploy
     * re-applies it.
     */
    private const CIPHERTEXT = '/^\d+:\d+:/';

    public function __construct(
        Context $context,
        private readonly EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
    }

    /**
     * Is Turnstile enabled on front
     *
     * @return bool
     */
    public function isEnabledOnFront(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::TURNSTILE_CONFIG_PATH_FRONTEND_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Is Turnstile enabled on admin
     *
     * @return bool
     */
    public function isEnabledOnAdmin(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::TURNSTILE_CONFIG_PATH_ADMINHTML_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve Secret Key
     *
     * @return string
     */
    public function getSecretKey(): string
    {
        $value = (string)$this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_SECRET_KEY,
            ScopeInterface::SCOPE_STORE
        );

        if ($value === '') {
            return '';
        }

        // Decrypt ONLY what is actually encrypted. Encryptor::decrypt() on a
        // plaintext string does not fail - it takes its legacy 1-part branch
        // and returns binary garbage - so decrypting unconditionally would
        // send rubbish to Cloudflare and fail every challenge with nothing in
        // a log to say why. Not hypothetical: that is exactly what happened to
        // Nimasystems_Speedy's API password, traced 2026-08-03.
        return preg_match(self::CIPHERTEXT, $value) === 1
            ? (string)$this->encryptor->decrypt($value)
            : $value;
    }

    /**
     * Retrieve Sitekey
     *
     * @return string
     */
    public function getSiteKey(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_SITEKEY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve frontend theme
     *
     * @return string
     */
    public function getFrontendTheme(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_FRONTEND_THEME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve admin theme
     *
     * @return string
     */
    public function getAdminTheme(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_ADMINHTML_THEME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve frontend size
     *
     * @return string
     */
    public function getFrontendSize(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_FRONTEND_SIZE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve admin size
     *
     * @return string
     */
    public function getAdminSize(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_ADMINHTML_SIZE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve enabled frontend forms
     *
     * @return string[]
     */
    public function getFrontendForms(): array
    {
        $forms = $this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_FRONTEND_FORMS,
            ScopeInterface::SCOPE_STORE
        );

        return $forms ? array_filter(explode(',', (string) $forms)) : [];
    }

    /**
     * Retrieve enabled admin forms
     *
     * @return string[]
     */
    public function getAdminForms(): array
    {
        $forms = $this->scopeConfig->getValue(
            self::TURNSTILE_CONFIG_PATH_ADMINHTML_FORMS,
            ScopeInterface::SCOPE_STORE
        );

        return $forms ? array_filter(explode(',', (string) $forms)) : [];
    }

    /**
     * Map a Magento locale to a Cloudflare Turnstile widget language.
     *
     * Turnstile accepts an ISO 639-1 code (optionally with a country suffix)
     * and falls back to browser detection when given "auto" — passing the
     * store language keeps the widget consistent with the storefront locale
     * instead of the visitor's browser.
     *
     * @param string $locale
     * @return string
     */
    public function getWidgetLanguage(string $locale): string
    {
        $language = strtolower(strtok($locale, '_') ?: '');

        return preg_match('/^[a-z]{2,3}$/', $language) ? $language : 'auto';
    }

    /**
     * Retrieve API URL
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }

    /**
     * Retrieve default action
     *
     * @return string
     */
    public function getAction(): string
    {
        return 'default';
    }
}
