<?php

declare(strict_types=1);

namespace Graduates\Admin;

/**
 * Service supporting cryptographic operations for API
 */
class ApiSecurityService
{
    private ApiSettingsModel $settings_model;

    public function __construct(ApiSettingsModel $settings_model)
    {
        $this->settings_model = $settings_model;
    }

    /**
     * Creates a unique API key
     */
    public function createUniqueApiKey(): string
    {
        $prefix = 'grad';
        $random_string = bin2hex(random_bytes(16));
        return $prefix . '_' . $random_string;
    }

    /**
     * Gets decrypted API key
     */
    public function getApiKey(): string
    {
        $encrypted_key = $this->settings_model->getEncryptedApiKey();
        if (empty($encrypted_key)) {
            return '';
        }

        return $this->decryptApiKey($encrypted_key);
    }

    /**
     * Saves API key with encryption
     */
    public function saveApiKey(string $api_key): bool
    {
        if (empty($api_key)) {
            return $this->settings_model->saveEncryptedApiKey('');
        }

        $encrypted_key = $this->encryptApiKey($api_key);
        return $this->settings_model->saveEncryptedApiKey($encrypted_key);
    }

    /**
     * Encrypts API key
     */
    private function encryptApiKey(string $api_key): string
    {
        if (empty($api_key)) {
            return '';
        }

        $encryption_key = $this->settings_model->getOrCreateEncryptionKey();
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt(
            $api_key,
            $method,
            $encryption_key,
            0,
            $iv
        );

        if ($encrypted === false) {
            error_log('Graduates Plugin: Failed to encrypt API key');
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypts API key
     */
    private function decryptApiKey(string $encrypted_key): string
    {
        if (empty($encrypted_key)) {
            return '';
        }

        $encryption_key = $this->settings_model->getOrCreateEncryptionKey();
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);

        $decoded = base64_decode($encrypted_key);
        if ($decoded === false) {
            return '';
        }

        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);

        $decrypted = openssl_decrypt(
            $encrypted,
            $method,
            $encryption_key,
            0,
            $iv
        );

        if ($decrypted === false) {
            error_log('Graduates Plugin: Failed to decrypt API key');
            return '';
        }

        return $decrypted;
    }
}
