<?php

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

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GRADUATES_VERSION', '1.0.0');
define('GRADUATES_FILE', __FILE__);
define('GRADUATES_DIR', plugin_dir_path(__FILE__));
define('GRADUATES_URL', plugin_dir_url(__FILE__));

// Include the Loader class manually - needed for autoloader setup
require_once GRADUATES_DIR . 'src/Loader.php';

// Register autoloader
Graduates\Loader::registerAutoloader();

// Register lifecycle hooks
Graduates\Loader::registerHooks();

// Initialize the plugin
$loader = new Graduates\Loader();
$loader->init();
