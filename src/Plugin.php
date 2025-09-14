<?php

declare(strict_types=1);

namespace Graduates;

use Graduates\PostType\GraduatePostType;
use Graduates\Admin\GraduateColumnsManager;
use Graduates\API\GraduatesRestApi;

class Plugin
{
    public function __construct(
        private readonly GraduatePostType $graduate_post_type,
        private readonly GraduateColumnsManager $columns_manager,
        private readonly Admin\ApiSettings $api_settings
    ) {
    }

    public function initialize(): void
    {
        add_action('init', function () {
            $this->graduate_post_type->register();
            $this->columns_manager->register();
            $this->api_settings->register();

            $graduates_rest_api = new GraduatesRestApi($this->graduate_post_type, $this->api_settings);
            $graduates_rest_api->init();
        }, 5);
    }
}
