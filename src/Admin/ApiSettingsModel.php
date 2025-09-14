<?php

declare(strict_types=1);

namespace Graduates\Admin;

/**
 * Model managing API configuration data
 */
class ApiSettingsModel
{
    public const OPTION_API_ENABLED = 'graduates_api_enabled';
    public const OPTION_API_KEY = 'graduates_api_key';
    public const OPTION_ENCRYPTION_KEY = 'graduates_encryption_key';

    /**
     * Checks if API security is enabled
     */
    public function isApiEnabled(): bool
    {
        return (bool) get_option(self::OPTION_API_ENABLED, false);
    }

    /**
     * Gets encrypted API key
     */
    public function getEncryptedApiKey(): string
    {
        return (string) get_option(self::OPTION_API_KEY, '');
    }

    /**
     * Saves encrypted API key
     */
    public function saveEncryptedApiKey(string $encrypted_key): bool
    {
        if (empty($encrypted_key)) {
            return delete_option(self::OPTION_API_KEY);
        }

        return update_option(self::OPTION_API_KEY, $encrypted_key);
    }

    /**
     * Gets or creates encryption key
     */
    public function getOrCreateEncryptionKey(): string
    {
        $encryption_key = get_option(self::OPTION_ENCRYPTION_KEY, '');

        if (empty($encryption_key)) {
            $encryption_key = wp_hash(uniqid('graduates', true) . wp_salt('auth'));
            update_option(self::OPTION_ENCRYPTION_KEY, $encryption_key);
        }

        return $encryption_key;
    }

    /**
     * Enables or disables API security
     */
    public function setApiEnabled(bool $enabled): bool
    {
        return update_option(self::OPTION_API_ENABLED, $enabled);
    }
}
