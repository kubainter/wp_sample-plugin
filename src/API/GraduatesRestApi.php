<?php

declare(strict_types=1);

namespace Graduates\API;

use WP_REST_Request;
use WP_REST_Response;
use Graduates\PostType\GraduatePostType;
use Graduates\Admin\ApiSettings;

/**
 * REST API endpoint for graduates
 */
class GraduatesRestApi
{
    private GraduatePostType $graduatePostType;
    private ApiSettings $apiSettings;

    public function __construct(GraduatePostType $graduatePostType, ApiSettings $apiSettings)
    {
        $this->graduatePostType = $graduatePostType;
        $this->apiSettings = $apiSettings;
    }

    /**
     * Initialize the REST API
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes'], 99);
    }

    public function registerRoutes(): void
    {
        register_rest_route('graduates/v1', '/graduates', [
            'methods' => 'GET',
            'callback' => [$this, 'getGraduates'],
            'permission_callback' => [$this, 'getGraduatesPermissionsCheck'],
            'args' => [
                'page' => [
                    'description' => __('Current page of the collection.', 'graduates'),
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'description' => __('Maximum number of items to be returned in result set.', 'graduates'),
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                    'sanitize_callback' => 'absint',
                ],
                'search' => [
                    'description' => __('Limit results to those matching a string.', 'graduates'),
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'orderby' => [
                    'description' => __('Sort collection by object attribute.', 'graduates'),
                    'type' => 'string',
                    'default' => 'title',
                    'enum' => ['title', 'date', 'id'],
                    'sanitize_callback' => 'sanitize_key',
                ],
                'order' => [
                    'description' => __('Order sort attribute ascending or descending.', 'graduates'),
                    'type' => 'string',
                    'default' => 'asc',
                    'enum' => ['asc', 'desc'],
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);
    }

    /**
     * @param WP_REST_Request $request Full data about the request.
     * @return bool|\WP_Error Whether the request has access to read graduates or error object.
     */
    public function getGraduatesPermissionsCheck(WP_REST_Request $request)
    {
        if (!$this->apiSettings->isApiEnabled()) {
            return true;
        }

        $api_key_header = $request->get_header('X-Graduates-API-Key');
        if (empty($api_key_header)) {
            return new \WP_Error(
                'graduates_rest_missing_api_key',
                __('Missing API key. Please provide X-Graduates-API-Key header.', 'graduates'),
                ['status' => 401]
            );
        }

        if ($api_key_header !== $this->apiSettings->getApiKey()) {
            return new \WP_Error(
                'graduates_rest_invalid_api_key',
                __('Invalid API key.', 'graduates'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_REST_Response
     */
    public function getGraduates(WP_REST_Request $request): WP_REST_Response
    {
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $search = $request->get_param('search');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');

        $args = [
            'post_type' => GraduatePostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $query = new \WP_Query($args);
        $graduates = [];

        foreach ($query->posts as $post) {
            $graduates[] = $this->prepareGraduateForResponse($post);
        }

        $response = new WP_REST_Response($graduates, 200);
        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', $query->max_num_pages);

        if ($page > 1) {
            $response->link_header('prev', rest_url('graduates/v1/graduates?page=' . ($page - 1)));
        }
        if ($page < $query->max_num_pages) {
            $response->link_header('next', rest_url('graduates/v1/graduates?page=' . ($page + 1)));
        }

        return $response;
    }

    /**
     * @param \WP_Post $post Graduate post to prepare for response
     * @return array Formatted graduate data
     */
    private function prepareGraduateForResponse(\WP_Post $post): array
    {
        $first_name = get_post_meta($post->ID, '_graduate_first_name', true);
        $last_name = get_post_meta($post->ID, '_graduate_last_name', true);

        return [
            'id' => $post->ID,
            'title' => [
                'rendered' => $post->post_title,
            ],
            'first_name' => $first_name,
            'last_name' => $last_name,
            'content' => [
                'rendered' => apply_filters('the_content', $post->post_content),
            ],
            'excerpt' => [
                'rendered' => get_the_excerpt($post),
            ],
            'date' => $post->post_date,
            'date_gmt' => $post->post_date_gmt,
            'modified' => $post->post_modified,
            'modified_gmt' => $post->post_modified_gmt,
            'status' => $post->post_status,
            'featured_media' => get_post_thumbnail_id($post->ID),
            'link' => get_permalink($post->ID),
            '_links' => [
                'self' => [
                    [
                        'href' => rest_url('wp/v2/' . GraduatePostType::POST_TYPE . '/' . $post->ID),
                    ],
                ],
                'collection' => [
                    [
                        'href' => rest_url('graduates/v1/graduates'),
                    ],
                ],
            ],
        ];
    }
}
