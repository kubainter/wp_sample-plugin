<?php

declare(strict_types=1);

namespace Graduates;

use Graduates\PostType\GraduatePostType;
use Graduates\Admin\GraduateColumnsManager;
use Graduates\Admin\ApiSettings;
use Graduates\API\GraduatesRestApi;
use Graduates\Shortcode\GraduatesListShortcode;

/**
 * Plugin loader responsible for initializing plugin components.
 */
class Loader
{
    /**
     * Initialize the plugin components.
     */
    public function init(): void
    {
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        add_action('plugins_loaded', [$this, 'initializePlugin']);
    }

    /**
     * Load the plugin text domain.
     */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'graduates',
            false,
            dirname(plugin_basename(GRADUATES_FILE)) . '/languages'
        );
    }

    /**
     * Initialize plugin components.
     */
    public function initializePlugin(): void
    {
        $graduate_post_type = new GraduatePostType();
        $columns_manager = new GraduateColumnsManager($graduate_post_type::POST_TYPE);

        $api_settings = new ApiSettings();

        $this->initializeApiComponents($graduate_post_type, $api_settings);

        $plugin = new Plugin($graduate_post_type, $columns_manager, $api_settings);
        $plugin->initialize();

        $this->initializeShortcodes($graduate_post_type);
    }

    /**
     * Initialize API components if they exist.
     */
    private function initializeApiComponents(GraduatePostType $graduate_post_type, ApiSettings $api_settings): void
    {
        if (!class_exists(GraduatesRestApi::class)) {
            return;
        }

        $rest_api = new GraduatesRestApi($graduate_post_type, $api_settings);
        $rest_api->init();
    }

    /**
     * Initialize shortcodes.
     */
    private function initializeShortcodes(GraduatePostType $graduate_post_type): void
    {
        new GraduatesListShortcode($graduate_post_type);
    }

    /**
     * Register plugin lifecycle hooks.
     */
    public static function registerHooks(): void
    {
        register_activation_hook(GRADUATES_FILE, [Activator::class, 'activate']);
        register_deactivation_hook(GRADUATES_FILE, [Deactivator::class, 'deactivate']);
    }

    /**
     * Register autoloader for plugin classes.
     * Falls back to a PSR-4 compliant autoloader if Composer is not available.
     */
    public static function registerAutoloader(): void
    {
        // Prefer Composer autoload if available
        $composerAutoload = GRADUATES_DIR . 'vendor/autoload.php';
        if (file_exists($composerAutoload)) {
            require_once $composerAutoload;
            return;
        }

        // Fallback to a simple PSR-4 autoloader
        spl_autoload_register(static function (string $class_name): void {
            $namespace = 'Graduates\\';
            $namespace_length = strlen($namespace);
            if (strncmp($namespace, $class_name, $namespace_length) !== 0) {
                return;
            }
            $relative_class = substr($class_name, $namespace_length);
            $file = GRADUATES_DIR . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
