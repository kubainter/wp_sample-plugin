<?php

declare(strict_types=1);

namespace Graduates\Admin;

/**
 * Controller for handling API settings admin interface
 */
class ApiSettingsController
{
    private ApiSettingsModel $settings_model;
    private ApiSecurityService $security_service;

    public function __construct(
        ApiSettingsModel $settings_model,
        ApiSecurityService $security_service
    ) {
        $this->settings_model = $settings_model;
        $this->security_service = $security_service;
    }

    /**
     * Registers all hooks related to API settings
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_post_graduates_generate_api_key', [$this, 'handleGenerateApiKey']);
        add_action('admin_notices', [$this, 'displayAdminNotices']);
    }

    /**
     * Adds settings page to admin menu
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
     * Registers settings, sections, and fields
     */
    public function registerSettings(): void
    {
        register_setting('graduates_api_settings', ApiSettingsModel::OPTION_API_ENABLED, [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);

        register_setting('graduates_api_settings', ApiSettingsModel::OPTION_API_KEY, [
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
     * Renders the settings page
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
            $this->captureOutput(function () {
                settings_fields('graduates_api_settings');
                do_settings_sections('graduates_api_settings');
                submit_button(__('Save Settings', 'graduates'));
            }),
            $this->settings_model->isApiEnabled() ? $this->renderGenerateKeySection() : ''
        );
    }

    /**
     * Renders the API key generation section
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
            $this->captureOutput(function () {
                submit_button(__('Generate New Key', 'graduates'), 'secondary');
            })
        );
    }

    /**
     * Renders the settings section description
     */
    public function renderSettingsSection(): void
    {
        echo sprintf(
            '<p>%s</p>',
            esc_html__('Configure security settings for the Graduates REST API. When enabled, all API requests must include the API key in the X-Graduates-API-Key header.', 'graduates')
        );
    }

    /**
     * Renders the API enabled field
     */
    public function renderApiEnabledField(): void
    {
        $enabled = $this->settings_model->isApiEnabled();
        echo sprintf(
            '<label for="graduates_api_enabled">
                <input type="checkbox" name="%s" id="graduates_api_enabled" value="1" %s>
                %s
            </label>',
            esc_attr(ApiSettingsModel::OPTION_API_ENABLED),
            checked($enabled, true, false),
            __('Require API key for all requests', 'graduates')
        );
    }

    /**
     * Renders the API key field
     */
    public function renderApiKeyField(): void
    {
        $api_key = $this->security_service->getApiKey();
        $enabled = $this->settings_model->isApiEnabled();

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
     * Handles the API key generation action
     */
    public function handleGenerateApiKey(): void
    {
        if (
            !current_user_can('manage_options') ||
            !isset($_POST['graduates_api_nonce']) ||
            !wp_verify_nonce($_POST['graduates_api_nonce'], 'graduates_generate_api_key')
        ) {
            wp_die(__('Security check failed.', 'graduates'));
        }

        $new_key = $this->security_service->createUniqueApiKey();
        $this->security_service->saveApiKey($new_key);

        wp_redirect(add_query_arg(
            ['page' => 'graduates-api-settings', 'key-generated' => '1'],
            admin_url('edit.php?post_type=graduate')
        ));
        exit;
    }

    /**
     * Displays admin notices
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
     * Captures the output of a callback function
     */
    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();
        return ob_get_clean();
    }
}
