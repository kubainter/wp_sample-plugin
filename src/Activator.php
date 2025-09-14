<?php

declare(strict_types=1);

namespace Graduates;

use Graduates\PostType\GraduatePostType;

class Activator
{
    public static function activate(): void
    {
        self::addCapabilities();
        self::registerPostType();
        self::initializeEncryptionKey();
        flush_rewrite_rules(false);
    }

    private static function addCapabilities(): void
    {
        $admin_role = get_role('administrator');
        $editor_role = get_role('editor');

        $capabilities = [
            'edit_' . GraduatePostType::CAPABILITY_TYPE => true,
            'read_' . GraduatePostType::CAPABILITY_TYPE => true,
            'delete_' . GraduatePostType::CAPABILITY_TYPE => true,
            'edit_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'edit_others_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'publish_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'read_private_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'delete_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'delete_private_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'delete_published_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'delete_others_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'edit_private_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'edit_published_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'create_' . GraduatePostType::CAPABILITY_TYPE . 's' => true,
            'manage_' . GraduatePostType::CAPABILITY_TYPE . '_terms' => true,
            'edit_' . GraduatePostType::CAPABILITY_TYPE . '_terms' => true,
            'delete_' . GraduatePostType::CAPABILITY_TYPE . '_terms' => true,
            'assign_' . GraduatePostType::CAPABILITY_TYPE . '_terms' => true,
        ];

        if ($admin_role) {
            foreach ($capabilities as $cap => $grant) {
                $admin_role->add_cap($cap, $grant);
            }
        }
        if ($editor_role) {
            foreach ($capabilities as $cap => $grant) {
                $editor_role->add_cap($cap, $grant);
            }
        }
    }

    private static function registerPostType(): void
    {
        (new GraduatePostType())->registerPostType();
    }

    private static function initializeEncryptionKey(): void
    {
        $encryption_key = get_option('graduates_encryption_key', '');

        if (empty($encryption_key)) {
            $encryption_key = wp_hash(uniqid('graduates', true) . wp_salt());
            update_option('graduates_encryption_key', $encryption_key);
        }
    }
}
