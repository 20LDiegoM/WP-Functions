<?php

namespace xxUnpu;

use WP_REST_Request;
use WP_REST_Response;

class Main
{
    /**
     * Constructor to initialize actions and filters.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('rest_api_init', [$this, 'register_api_routes']);
    }

    /**
     * Adds the main admin menu and submenus for the plugin.
     */
    public function add_admin_menu()
    {
        $unpu_page = add_menu_page(
            'Unpublish Manager',
            'Unpublish Manager',
            'manage_options',
            xx_UNPU_ADMIN_PAGE,
            [$this, 'render_people_table']
        );

        add_submenu_page(
            xx_UNPU_ADMIN_PAGE,
            'Unpublish Media Posts',
            'Unpublish Media Posts',
            'manage_options',
            'xx-unpu-media',
            [$this, 'render_media_table']
        );
    }

    /**
     * Renders the media table template.
     */
    public function render_media_table()
    {
        include xx_UNPU_ROOT . '/templates/media-table.php';
    }

    /**
     * Enqueues admin styles and scripts for the plugin.
     */
    public function enqueue_admin_scripts()
    {
        wp_enqueue_style(
            'xx-unpu',
            xx_UNPU_PLUGIN_URI . 'assets/style.css',
            null,
            filemtime(plugin_dir_path(__DIR__) . 'assets/style.css'),
            false
        );

        wp_enqueue_script(
            'xx-unpu',
            xx_UNPU_PLUGIN_URI . 'assets/script.js',
            [],
            filemtime(plugin_dir_path(__DIR__) . 'assets/script.js')
        );
    }

    /**
     * Handles the REST API endpoint for updating the media cronjob settings.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response The response object containing the status message.
     */
    public function media_auto_update_cronjob_rest(WP_REST_Request $request)
    {
        $payload = $request->get_params();
        $cron_type = isset($payload['cron-type']) ? $payload['cron-type'] : null;

        if (!$cron_type || !in_array($cron_type, ['five-years', 'two-years'])) {
            return new WP_REST_Response([
                'message' => 'Invalid or missing cron-type parameter.'
            ], 400);
        }

        // Configuration for cron jobs
        $cron_jobs = [
            'five-years' => [
                'enable_option' => 'unpublish_media_auto_status',
                'date_option' => 'unpublish_media_auto_date',
                'event_name' => 'update_auto_media_posts_event',
                'schedule' => 'yearly',
                'log_prefix' => 'five_years'
            ],
            'two-years' => [
                'enable_option' => 'unpublish_media_auto_status_two',
                'date_option' => 'unpublish_media_auto_date_two',
                'event_name' => 'update_auto_media_posts_event_two',
                'schedule' => 'daily',
                'log_prefix' => 'two_years'
            ]
        ];

        $settings = $cron_jobs[$cron_type];
        $enableCronjob = isset($payload["cronjob-checkbox"]) ? '1' : '0';
        $selected_date = isset($payload["cronjob-date"]) ? sanitize_text_field($payload["cronjob-date"]) : date('Y-m-d');

        update_option($settings['date_option'], $selected_date);
        update_option($settings['enable_option'], $enableCronjob);

        $background_process_five_years = new \MediaBackgroundProcess('media_auto_update_five_years');
        $background_process_two_years = new \MediaBackgroundProcess('media_auto_update_two_years');

        if ($enableCronjob === '1') {
            $timestamp = strtotime($selected_date . ' 00:00:00');

            if (!wp_next_scheduled($settings['event_name'])) {
                wp_schedule_event($timestamp, $settings['schedule'], $settings['event_name']);
                $next_run_date = wp_next_scheduled($settings['event_name']);
                update_option("next_run_{$settings['event_name']}", date('Y-m-d H:i:s', $next_run_date));
                error_log("Cronjob scheduled for {$settings['schedule']} execution.");
            }
        } else {
            $timestamp = wp_next_scheduled($settings['event_name']);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $settings['event_name']);
                delete_option("next_run_{$settings['event_name']}");
                error_log("Cronjob unscheduled.");

                if ($cron_type == 'five-years') {
                    $background_process_five_years->delete_all();
                } else {
                    $background_process_two_years->delete_all();
                }
            }
        }

        return new WP_REST_Response([
            'message' => "Cronjob for {$cron_type} updated."
        ], 200);
    }

    /**
     * Retrieves the status of the 5-year cronjob.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response The response object with cronjob status information.
     */
    public function get_cronjob_status_five_years(WP_REST_Request $request)
    {
        $last_run_date_five = get_option('last_run_update_auto_media_posts_event', 'Never');
        $cronjob_status_five = get_option('unpublish_media_auto_status');
        $logs_five = get_option('cronjob_log_five_years', 'No logs yet.');

        return new WP_REST_Response([
            'last_run' => $last_run_date_five,
            'status' => $cronjob_status_five ? 'Enabled' : 'Disabled',
            'logs' => $logs_five,
        ], 200);
    }

    /**
     * Retrieves the status of the 2-year cronjob.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response The response object with cronjob status information.
     */
    public function get_cronjob_status_two_years(WP_REST_Request $request)
    {
        $last_run_date_two = get_option('last_run_update_auto_media_posts_event_two', 'Never');
        $cronjob_status_two = get_option('unpublish_media_auto_status_two');
        $logs_two = get_option('cronjob_log_two_years', 'No logs yet.');

        return new WP_REST_Response([
            'last_run' => $last_run_date_two,
            'status' => $cronjob_status_two ? 'Enabled' : 'Disabled',
            'logs' => $logs_two,
        ], 200);
    }

    /**
     * Clears the cronjob logs for the specified type.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response The response indicating if the log was cleared successfully.
     */
    public function clear_cronjob_log(WP_REST_Request $request)
    {
        $type = $request->get_param('type');
        error_log(print_r("clear cron: " . $type, true));

        if ($type === 'five-years') {
            update_option('cronjob_log_five_years', 'No logs yet.');
            wp_cache_delete('cronjob_log_five_years', 'options');
            error_log('deleted five');
        } elseif ($type === 'two-years') {
            update_option('cronjob_log_two_years', 'No logs yet.');
            wp_cache_delete('cronjob_log_two_years', 'options');
        }

        return new WP_REST_Response(['message' => 'Log cleared successfully'], 200);
    }

    /**
     * Excludes media from automatic processes via REST API.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response The response indicating if the field was updated successfully.
     */
    public function exclude_media_auto_exclude(WP_REST_Request $request)
    {
        $payload = $request->get_params();
        $exclude_media_ids = $payload;

        $updated = update_field('related_media_posts', $exclude_media_ids, 'options');

        if (is_wp_error($updated)) {
            return new WP_REST_Response([
                'error' => 'There was an error updating the field.',
            ], 500);
        }

        return new WP_REST_Response([
            'field-updated' => 'Field successfully updated',
        ], 200);
    }

    /**
     * Checks if the current user has the required permissions for REST API requests.
     *
     * @return bool True if the user has the required role, false otherwise.
     */
    public function api_permissions()
    {
        $auth_cookie = wp_parse_auth_cookie('', 'logged_in');
        $user = get_user_by('login', $auth_cookie['username']);

        if ($user !== false && $user->exists()) {
            return in_array('xx_user', (array) $user->roles) ||
                   in_array('xx_admin_user', (array) $user->roles) ||
                   in_array('administrator', (array) $user->roles);
        }

        return false;
    }

    /**
     * Registers custom REST API routes for the plugin.
     */
    public function register_api_routes()
    {
        register_rest_route('unpu', '/exclude-media-auto-exclude', array(
            'methods' => array('GET', 'POST'),
            'callback' => [$this, 'exclude_media_auto_exclude'],
            'permission_callback' => [$this, 'api_permissions'],
        ));

        register_rest_route('unpu', '/media-auto-update-cronjob-rest', array(
            'methods' => 'POST',
            'callback' => [$this, 'media_auto_update_cronjob_rest'],
            'permission_callback' => [$this, 'api_permissions'],
        ));

        register_rest_route('unpu', '/get-cronjob-status-five-years', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_cronjob_status_five_years'],
            'permission_callback' => [$this, 'api_permissions'],
        ));

        register_rest_route('unpu', '/get-cronjob-status-two-years', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_cronjob_status_two_years'],
            'permission_callback' => [$this, 'api_permissions'],
        ));

        register_rest_route('unpu', '/clear-cronjob-log', array(
            'methods' => 'POST',
            'callback' => [$this, 'clear_cronjob_log'],
            'permission_callback' => [$this, 'api_permissions'],
        ));
    }
}
