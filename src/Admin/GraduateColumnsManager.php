<?php

declare(strict_types=1);

namespace Graduates\Admin;

class GraduateColumnsManager
{
    private string $post_type;

    public function __construct(string $post_type)
    {
        $this->post_type = $post_type;
    }

    public function register(): void
    {
        add_filter('manage_' . $this->post_type . '_posts_columns', [$this, 'defineColumns']);
        add_action('manage_' . $this->post_type . '_posts_custom_column', [$this, 'renderColumnContent'], 10, 2);
    }

    public function defineColumns(array $columns): array
    {
        return [
            'cb' => $columns['cb'], // Checkbox
            'thumbnail' => __('Photo', 'graduates'),
            'title' => __('Full Name', 'graduates'),
            'excerpt' => __('Description', 'graduates'),
            'date' => $columns['date'], // Date
        ];
    }

    public function renderColumnContent(string $column_name, int $post_id): void
    {
        switch ($column_name) {
            case 'thumbnail':
                echo get_the_post_thumbnail($post_id, [50, 50]) ?: '—';
                break;
            case 'excerpt':
                $content = get_post_field('post_content', $post_id);
                $excerpt = mb_substr(strip_tags($content), 0, 50);
                echo esc_html($excerpt) ?: '—';
                break;
        }
    }
}
