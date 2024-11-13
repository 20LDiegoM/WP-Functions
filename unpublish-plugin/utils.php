<?php
// Hook to enqueue ACF scripts specifically for a custom admin page
add_action('admin_enqueue_scripts', 'enqueue_acf_scripts_for_custom_page');

// Hook to register custom ACF fields
add_action('init', 'register_acf_fields_for_media');

// Filter to modify the display of ACF relationship fields
add_filter('acf/fields/relationship/result', 'render_posts_status_in_acf', 10, 3);

// Filter to add custom cron schedules
add_filter('cron_schedules', 'custom_cron_yearly_interval');

/**
 * Enqueue ACF scripts for a specific custom admin page.
 *
 * @param string $hook The current admin page hook.
 */
function enqueue_acf_scripts_for_custom_page($hook)
{
    if ($hook == 'unpublish-manager_page_xx-unpu-media') {
        // Enqueue ACF scripts if on the specified custom admin page
        acf_enqueue_scripts();
    }
}

/**
 * Register custom ACF fields for media post type.
 */
function register_acf_fields_for_media()
{
    if (function_exists('acf_add_local_field_group')) {
        // Register a new ACF field group with a relationship field for media posts
        acf_add_local_field_group(array(
            'key' => 'group_media_auto_relationship',
            'title' => 'Exclude Media - Auto Unpublish',
            'fields' => array(
                array(
                    'key' => 'field_related_media_auto_posts',
                    'label' => 'Exclude Media Posts',
                    'name' => 'related_media_posts',
                    'type' => 'relationship',
                    'instructions' => 'All posts in this field will be excluded',
                    'post_type' => 'media',
                    'allow_null' => 0,
                    'multiple' => 0,
                    'filters' => array('search'),
                    'return_format' => 'id',
                )
            ),
            'location' => array(), // Specify location rules if needed
        ));
    }
}

/**
 * Customize the display of post titles in ACF relationship fields.
 *
 * @param string $text  The original display text.
 * @param WP_Post $post The post object.
 * @param array $field  The field settings.
 * @return string Modified display text including post ID, title, status, and language flag.
 */
function render_posts_status_in_acf($text, $post, $field)
{
    if (!empty($field['key']) && $field['key'] == 'field_related_media_auto_posts') {
        $id = $post->ID;
        $post_status = get_post_status($post->ID);
        $post_title = $post->post_title ?? '(no title)';
        $translations = pll_the_languages(array('raw' => 1));
        $lang = pll_get_post_language($id);

        // Add a language flag to the display
        $flag = "<img src='" . esc_url($translations[$lang]['flag']) . "' alt='" . esc_attr($translations[$lang]['name']) . "' width='18' height='12' style='width: 18px; height: 12px;' />";

        // Format the text to include post details
        $text = sprintf('ID: %d - %s (<strong>%s</strong>) %s', $id, $post_title, ucfirst($post_status), ucfirst($flag));
    }

    return $text;
}

/**
 * Add a custom yearly cron schedule.
 *
 * @param array $schedules Existing cron schedules.
 * @return array Modified cron schedules with a yearly interval.
 */
function custom_cron_yearly_interval($schedules)
{
    $schedules['yearly'] = array(
        'interval' => 365 * 24 * 60 * 60, // Interval for one year in seconds
        'display'  => __('Once Yearly')
    );
    return $schedules;
}

/**
 * Add a custom cron schedule for running every 5 minutes.
 *
 * @param array $schedules Existing cron schedules.
 * @return array Modified cron schedules with a 5-minute interval.
 */
add_filter('cron_schedules', function($schedules) {
    $schedules['xx_unpu_media_auto_update_cron_interval'] = array(
        'interval' => 300, // Interval for 5 minutes in seconds
        'display'  => __('Every 5 Minutes')
    );
    return $schedules;
});
