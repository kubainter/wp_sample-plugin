<?php

declare(strict_types=1);

namespace Graduates;

use Graduates\PostType\GraduatePostType;
use Graduates\Admin\GraduateColumnsManager;

class Plugin
{
    public function __construct(
        private readonly GraduatePostType $graduate_post_type,
        private readonly GraduateColumnsManager $columns_manager
    ) {}

    public function initialize(): void
    {
        $this->registerHooks();
        
        add_action('init', function() {
            $this->graduate_post_type->register();
            $this->columns_manager->register();
        }, 5);
    }

    private function registerHooks(): void
    {
        add_action('init', [$this, 'loadTextdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'graduates',
            false,
            dirname(plugin_basename(GRADUATES_FILE)) . '/languages'
        );
    }

    public function enqueueAssets(): void
    {
        if (!is_singular('graduate')) {
            return;
        }

        wp_enqueue_style(
            'graduates-frontend',
            GRADUATES_URL . 'assets/css/frontend.css',
            [],
            GRADUATES_VERSION
        );
    }

    public function enqueueAdminAssets(): void
    {
        wp_enqueue_style(
            'graduates-admin',
            GRADUATES_URL . 'assets/css/admin.css',
            [],
            GRADUATES_VERSION
        );

        wp_enqueue_script(
            'graduates-admin',
            GRADUATES_URL . 'assets/js/admin.js',
            ['jquery'],
            GRADUATES_VERSION,
            true
        );
    }
}
