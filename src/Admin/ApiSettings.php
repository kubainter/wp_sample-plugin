<?php

declare(strict_types=1);

namespace Graduates\Admin;

/**
 * API Settings Manager
 *
 * Facade integrating model, service and controller for API settings
 * Maintains backward compatibility with existing code
 */
class ApiSettings
{
    private ApiSettingsModel $model;
    private ApiSecurityService $security;
    private ApiSettingsController $controller;

    public function __construct()
    {
        $this->model = new ApiSettingsModel();
        $this->security = new ApiSecurityService($this->model);
        $this->controller = new ApiSettingsController($this->model, $this->security);
    }

    /**
     * Registers all hooks related to API settings
     */
    public function register(): void
    {
        $this->controller->register();
    }

    /**
     * Checks if API security is enabled
     */
    public function isApiEnabled(): bool
    {
        return $this->model->isApiEnabled();
    }

    /**
     * Gets API key (decrypted)
     */
    public function getApiKey(): string
    {
        return $this->security->getApiKey();
    }

    /**
     * Saves API key (with encryption)
     */
    public function saveApiKey(string $api_key): bool
    {
        return $this->security->saveApiKey($api_key);
    }
}
