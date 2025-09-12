<?php

declare(strict_types=1);

namespace Graduates\Shortcode;

use Graduates\PostType\GraduatePostType;

class GraduatesListShortcode
{
    private GraduatePostType $postType;

    public function __construct(GraduatePostType $postType)
    {
        $this->postType = $postType;
        add_shortcode('graduates_list', [$this, 'render']);
    }

    public function render(): string
    {
        $query = new \WP_Query([
            'post_type'      => GraduatePostType::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);

        if (!$query->have_posts()) {
            return '<div class="graduates-empty">' . esc_html__('No graduates found.', 'graduates') . '</div>';
        }

        $output = '<ul class="graduates-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $first_name = sanitize_text_field(get_post_meta($post_id, '_graduate_first_name', true));
            $last_name = sanitize_text_field(get_post_meta($post_id, '_graduate_last_name', true));
            $title = esc_html(get_the_title($post_id));

            $output .= sprintf(
                '<li class="graduate"><strong>%s</strong> — %s %s</li>',
                $title,
                esc_html($first_name),
                esc_html($last_name)
            );
        }
        wp_reset_postdata();
        $output .= '</ul>';

        return $output;
    }
}
