<?php
declare(strict_types=1);
/**
 * Plugin Name: Graduates
 * Description: Advanced plugin for managing graduates using modern design patterns.
 * Version: 1.0.0
 * Author: Jakub Grzesiak
 * Text Domain: graduates
 * Domain Path: /languages
 * Requires PHP: 8.1
 *
 * @package Graduates
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('GRADUATES_PLUGIN_DIR')) {
    define('GRADUATES_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

const GRADUATES_VERSION = '1.0.0';
define('GRADUATES_FILE', __FILE__);
define('GRADUATES_DIR', plugin_dir_path(__FILE__));
define('GRADUATES_URL', plugin_dir_url(__FILE__));
// Prefer Composer autoload if available; otherwise, fallback to a simple PSR-4 autoloader
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
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

// Lifecycle hooks
register_activation_hook(__FILE__, ['Graduates\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Graduates\\Deactivator', 'deactivate']);

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain(
        'graduates',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
    $graduate_post_type = new Graduates\PostType\GraduatePostType();
    $columns_manager = new Graduates\Admin\GraduateColumnsManager($graduate_post_type::POST_TYPE);
    $plugin = new Graduates\Plugin($graduate_post_type, $columns_manager);
    $plugin->initialize();

    new Graduates\Shortcode\GraduatesListShortcode($graduate_post_type);

    $graduates_rest_api = new Graduates\API\GraduatesRestApi($graduate_post_type);
    $graduates_rest_api->init();
});