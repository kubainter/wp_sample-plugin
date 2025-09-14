<?php

declare(strict_types=1);

namespace Graduates\API;

use Graduates\Admin\ApiSettings;

/**
 * Class responsible for API security mechanisms
 */
class ApiSecurity
{
    private ApiSettings $apiSettings;

    public function __construct()
    {
        $this->apiSettings = new ApiSettings();
    }

    /**
     * Initialize security features
     */
    public function init(): void
    {
        add_filter('graduates_rest_api_permissions', [$this, 'validateApiKey'], 10, 2);
    }

    /**
     * Validate the API key from request
     *
     * @param bool|\WP_Error $permission Current permission status
     * @param \WP_REST_Request $request Request object
     * @return bool|\WP_Error Updated permission status or error
     */
    public function validateApiKey($permission, $request)
    {
        if (!$this->apiSettings->isApiEnabled()) {
            return true;
        }

        $api_key_header = $request->get_header('X-Graduates-API-Key');
        if (empty($api_key_header)) {
            return new \WP_Error(
                'graduates_rest_missing_api_key',
                __('Missing API key. Please provide X-Graduates-API-Key header.', 'graduates'),
                ['status' => 401]
            );
        }

        if ($api_key_header !== $this->apiSettings->getApiKey()) {
            return new \WP_Error(
                'graduates_rest_invalid_api_key',
                __('Invalid API key.', 'graduates'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Get the API settings instance
     *
     * @return ApiSettings
     */
    public function getApiSettings(): ApiSettings
    {
        return $this->apiSettings;
    }
}
