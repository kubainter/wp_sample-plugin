<?php

declare(strict_types=1);

namespace Graduates\Admin;

/**
 * API Settings Manager
 *
 * Handles API settings, encryption and decryption of API keys
 */
class ApiSettings
{
    private const OPTION_API_ENABLED = 'graduates_api_enabled';
    private const OPTION_API_KEY = 'graduates_api_key';
    private const OPTION_ENCRYPTION_KEY = 'graduates_encryption_key';

    /**
     * Register all hooks related to API settings
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_post_graduates_generate_api_key', [$this, 'handleGenerateApiKey']);
        add_action('admin_notices', [$this, 'displayAdminNotices']);
    }

    /**
     * Add settings page to admin menu
     */
    public function addSettingsPage(): void
    {
        add_submenu_page(
            'edit.php?post_type=graduate',
            __('API Settings', 'graduates'),
            __('API Settings', 'graduates'),
            'manage_options',
            'graduates-api-settings',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Register settings, sections and fields
     */
    public function registerSettings(): void
    {
        register_setting('graduates_api_settings', self::OPTION_API_ENABLED, [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);

        register_setting('graduates_api_settings', self::OPTION_API_KEY, [
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        add_settings_section(
            'graduates_api_section',
            __('API Security Settings', 'graduates'),
            [$this, 'renderSettingsSection'],
            'graduates_api_settings'
        );

        add_settings_field(
            'graduates_api_enabled',
            __('Enable API Security', 'graduates'),
            [$this, 'renderApiEnabledField'],
            'graduates_api_settings',
            'graduates_api_section'
        );

        add_settings_field(
            'graduates_api_key',
            __('API Key', 'graduates'),
            [$this, 'renderApiKeyField'],
            'graduates_api_settings',
            'graduates_api_section'
        );
    }

    /**
     * Render the settings page
     */
    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'graduates'));
        }

        echo sprintf(
            '<div class="wrap">
                <h1>%s</h1>
                <form action="options.php" method="post">
                    %s
                </form>
                %s
            </div>',
            esc_html(get_admin_page_title()),
            $this->captureOutput(function() {
                settings_fields('graduates_api_settings');
                do_settings_sections('graduates_api_settings');
                submit_button(__('Save Settings', 'graduates'));
            }),
            $this->isApiEnabled() ? $this->renderGenerateKeySection() : ''
        );
    }

    /**
     * Render generate key section
     */
    private function renderGenerateKeySection(): string
    {
        return sprintf(
            '<div class="graduates-generate-key card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h2>%s</h2>
                <p>%s</p>
                <form action="%s" method="post">
                    %s
                    <input type="hidden" name="action" value="graduates_generate_api_key">
                    %s
                </form>
            </div>',
            __('Generate New API Key', 'graduates'),
            __('If you need to regenerate your API key for security reasons, click the button below. Note: This will invalidate your previous key.', 'graduates'),
            esc_url(admin_url('admin-post.php')),
            wp_nonce_field('graduates_generate_api_key', 'graduates_api_nonce', true, false),
            $this->captureOutput(function() {
                submit_button(__('Generate New Key', 'graduates'), 'secondary');
            })
        );
    }

    /**
     * Render settings section description
     */
    public function renderSettingsSection(): void
    {
        echo sprintf(
            '<p>%s</p>',
            esc_html__('Configure security settings for the Graduates REST API. When enabled, all API requests must include the API key in the X-Graduates-API-Key header.', 'graduates')
        );
    }

    /**
     * Render API enabled field
     */
    public function renderApiEnabledField(): void
    {
        $enabled = $this->isApiEnabled();
        echo sprintf(
            '<label for="graduates_api_enabled">
                <input type="checkbox" name="%s" id="graduates_api_enabled" value="1" %s>
                %s
            </label>',
            esc_attr(self::OPTION_API_ENABLED),
            checked($enabled, true, false),
            __('Require API key for all requests', 'graduates')
        );
    }

    /**
     * Render API key field
     */
    public function renderApiKeyField(): void
    {
        $api_key = $this->getApiKey();
        $enabled = $this->isApiEnabled();

        if (!$enabled) {
            echo sprintf(
                '<p>%s</p>',
                esc_html__('Enable API security to view or generate an API key.', 'graduates')
            );
            return;
        }

        if (empty($api_key)) {
            echo sprintf(
                '<p>%s</p>',
                esc_html__('No API key generated yet. Use the "Generate New Key" button below to create one.', 'graduates')
            );
        } else {
            echo sprintf(
                '<input type="text" class="regular-text code" id="graduates_api_key" value="%s" readonly>
                <p class="description">%s</p>
                <div class="graduates-api-example" style="background: #f0f0f1; padding: 10px; margin-top: 10px; border-radius: 4px;">
                    <p><strong>%s</strong></p>
                    <pre style="overflow-x: auto; white-space: pre-wrap;">curl -H "X-Graduates-API-Key: %s" %s</pre>
                </div>',
                esc_attr($api_key),
                __('Use this key in your API requests by adding the X-Graduates-API-Key header.', 'graduates'),
                __('Example cURL request:', 'graduates'),
                esc_attr($api_key),
                esc_url(rest_url('graduates/v1/graduates'))
            );
        }
    }

    /**
     * Handle generate API key action
     */
    public function handleGenerateApiKey(): void
    {
        if (!current_user_can('manage_options') ||
            !isset($_POST['graduates_api_nonce']) ||
            !wp_verify_nonce($_POST['graduates_api_nonce'], 'graduates_generate_api_key')) {
            wp_die(__('Security check failed.', 'graduates'));
        }

        $new_key = $this->createUniqueApiKey();
        $this->saveApiKey($new_key);

        wp_redirect(add_query_arg(
            ['page' => 'graduates-api-settings', 'key-generated' => '1'],
            admin_url('edit.php?post_type=graduate')
        ));
        exit;
    }

    /**
     * Display admin notices
     */
    public function displayAdminNotices(): void
    {
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'graduate_page_graduates-api-settings') {
            return;
        }

        if (isset($_GET['key-generated']) && $_GET['key-generated'] === '1') {
            echo sprintf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                __('API key generated successfully.', 'graduates')
            );
        }

        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo sprintf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                __('API settings updated successfully.', 'graduates')
            );
        }
    }

    /**
     * Create a unique API key
     */
    private function createUniqueApiKey(): string
    {
        $prefix = 'grad';
        $random_string = bin2hex(random_bytes(16));
        return $prefix . '_' . $random_string;
    }

    /**
     * Check if API security is enabled
     */
    public function isApiEnabled(): bool
    {
        return (bool) get_option(self::OPTION_API_ENABLED, false);
    }

    /**
     * Get the current API key (decrypted)
     */
    public function getApiKey(): string
    {
        $encrypted_key = get_option(self::OPTION_API_KEY, '');
        if (empty($encrypted_key)) {
            return '';
        }

        return $this->decryptApiKey($encrypted_key);
    }

    /**
     * Save API key (encrypted)
     */
    public function saveApiKey(string $api_key): bool
    {
        if (empty($api_key)) {
            return delete_option(self::OPTION_API_KEY);
        }

        $encrypted_key = $this->encryptApiKey($api_key);
        return update_option(self::OPTION_API_KEY, $encrypted_key);
    }

    /**
     * Get or create encryption key
     */
    private function getEncryptionKey(): string
    {
        $encryption_key = get_option(self::OPTION_ENCRYPTION_KEY, '');

        if (empty($encryption_key)) {
            $encryption_key = wp_hash(uniqid('graduates', true) . wp_salt('auth'));
            update_option(self::OPTION_ENCRYPTION_KEY, $encryption_key);
        }

        return $encryption_key;
    }

    /**
     * Encrypt API key
     */
    private function encryptApiKey(string $api_key): string
    {
        if (empty($api_key)) {
            return '';
        }

        $encryption_key = $this->getEncryptionKey();
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
     * Decrypt API key
     */
    private function decryptApiKey(string $encrypted_key): string
    {
        if (empty($encrypted_key)) {
            return '';
        }

        $encryption_key = $this->getEncryptionKey();
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

    /**
     * Capture output of a callback function
     */
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }
}
