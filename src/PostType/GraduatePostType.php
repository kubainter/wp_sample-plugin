<?php

declare(strict_types=1);

namespace Graduates\PostType;

class GraduatePostType
{
    public const POST_TYPE = 'graduate';
    public const CAPABILITY_TYPE = 'graduate';
    private const META_KEY_FIRST_NAME = '_graduate_first_name';
    private const META_KEY_LAST_NAME = '_graduate_last_name';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('add_meta_boxes', [$this, 'registerMetaBoxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'saveMetaBox']);
    }

    public function registerPostType(): void
    {
        $labels = [
            'name'                  => _x('Graduates', 'post type general name', 'graduates'),
            'singular_name'         => _x('Graduate', 'post type singular name', 'graduates'),
            'menu_name'             => _x('Graduates', 'admin menu', 'graduates'),
            'add_new'               => _x('Add New', 'graduate', 'graduates'),
            'add_new_item'          => __('Add New Graduate', 'graduates'),
            'edit_item'             => __('Edit Graduate', 'graduates'),
            'all_items'             => __('All Graduates', 'graduates'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'rewrite'             => ['slug' => 'graduates'],
            'capability_type'     => self::CAPABILITY_TYPE,
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-groups',
            'supports'            => ['editor', 'thumbnail'],
            'show_in_rest'        => true,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function registerMetaBoxes(): void
    {
        add_meta_box(
            'graduate_details',
            __('Graduate Details', 'graduates'),
            [$this, 'renderMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function getFieldLabels(): array
    {
        return [
            'first_name' => esc_html__('First Name', 'graduates'),
            'last_name' => esc_html__('Last Name', 'graduates'),
        ];
    }

    private function renderView(string $view_path, array $data = []): string
    {
        ob_start();
        include GRADUATES_DIR . 'src/View/' . $view_path;
        return ob_get_clean();
    }

    public function renderMetaBox(\WP_Post $post): void
    {
        $first_name = get_post_meta($post->ID, self::META_KEY_FIRST_NAME, true);
        $last_name = get_post_meta($post->ID, self::META_KEY_LAST_NAME, true);
        $labels = $this->getFieldLabels();

        $nonce_field = wp_nonce_field('graduate_details_save', 'graduate_details_nonce', true, false);

        echo $this->renderView('graduate-metabox.php', [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'labels' => $labels,
            'nonce_field' => $nonce_field
        ]);
    }

    public function saveMetaBox(int $post_id): void
    {
        if (!isset($_POST['graduate_details_nonce']) || !wp_verify_nonce($_POST['graduate_details_nonce'], 'graduate_details_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $first_name = isset($_POST['graduate_first_name']) ? sanitize_text_field($_POST['graduate_first_name']) : '';
        $last_name = isset($_POST['graduate_last_name']) ? sanitize_text_field($_POST['graduate_last_name']) : '';

        update_post_meta($post_id, self::META_KEY_FIRST_NAME, $first_name);
        update_post_meta($post_id, self::META_KEY_LAST_NAME, $last_name);

        $full_name = trim("$first_name $last_name");
        if (!empty($full_name) && get_the_title($post_id) !== $full_name) {
            remove_action('save_post_' . self::POST_TYPE, [$this, 'saveMetaBox']);

            wp_update_post(['ID' => $post_id, 'post_title' => $full_name]);

            add_action('save_post_' . self::POST_TYPE, [$this, 'saveMetaBox']);
        }
    }
}
