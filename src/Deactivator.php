<?php

declare(strict_types=1);

namespace Graduates;

use Graduates\PostType\GraduatePostType;

class Deactivator
{
    public static function deactivate(): void
    {
        self::removeCapabilities();
        self::removeApiSettings();
        flush_rewrite_rules(false);
    }

    private static function removeApiSettings(): void
    {
        $api_options = [
            'graduates_api_enabled',
            'graduates_api_key',
            'graduates_encryption_key',
        ];

        foreach ($api_options as $option_name) {
            delete_option($option_name);
        }
    }

    private static function removeCapabilities(): void
    {
        if (!class_exists('WP_Roles')) {
            return;
        }

        $capabilities = [
            'edit_' . GraduatePostType::CAPABILITY_TYPE,
            'read_' . GraduatePostType::CAPABILITY_TYPE,
            'delete_' . GraduatePostType::CAPABILITY_TYPE,
            'edit_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'edit_others_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'publish_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'read_private_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'delete_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'delete_private_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'delete_published_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'delete_others_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'edit_private_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'edit_published_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'create_' . GraduatePostType::CAPABILITY_TYPE . 's',
            'manage_' . GraduatePostType::CAPABILITY_TYPE . '_terms',
            'edit_' . GraduatePostType::CAPABILITY_TYPE . '_terms',
            'delete_' . GraduatePostType::CAPABILITY_TYPE . '_terms',
            'assign_' . GraduatePostType::CAPABILITY_TYPE . '_terms',
        ];

        $roles = ['administrator', 'editor', 'author', 'contributor'];
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}
