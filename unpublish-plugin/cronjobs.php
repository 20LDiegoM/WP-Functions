<?php
// Add WordPress actions to schedule and trigger the cronjob functions
add_action('update_auto_media_posts_event', function () {
    media_auto_update_cronjob('five_years');
}, 10, 0);

add_action('update_auto_media_posts_event_two', function () {
    media_auto_update_cronjob('two_years');
}, 10, 0);

// Include the background process class
require_once xx_UNPU_ROOT . '/xxUnpu/Process/MediaBackgroundProcess.php';

// Initialize background process instances
$background_process_five_years = new MediaBackgroundProcess('media_auto_update_five_years');
$background_process_two_years = new MediaBackgroundProcess('media_auto_update_two_years');

/**
 * Logs messages for cronjob activities.
 *
 * @param string $message The message to log.
 * @param string $type    The type of log, either 'five_years' or 'two_years'.
 */
function add_to_cron_log($message, $type = 'five_years')
{
    $log_option = $type === 'five_years' ? 'cronjob_log_five_years' : 'cronjob_log_two_years';
    $existing_logs = get_option($log_option, '');
    $timestamp = current_time('mysql');
    $new_log_entry = "[$timestamp] $message\n";

    // Remove the default message if present
    if (trim($existing_logs) === 'No logs yet.') {
        $existing_logs = '';
    }

    // Update the option with the new log entry
    update_option($log_option, $existing_logs . $new_log_entry);
}

/**
 * Processes media posts based on the specified time period.
 *
 * @param string $type The type of cronjob, either 'five_years' or 'two_years'.
 */
function media_auto_update_cronjob($type = 'five_years')
{
    global $background_process_five_years;
    global $background_process_two_years;

    $log_type = $type === 'two_years' ? 'two_years' : 'five_years';
    $background_process = $log_type === 'two_years' ? $background_process_two_years : $background_process_five_years;

    // Log the start of the cronjob
    add_to_cron_log('Running...', $log_type);
    error_log("Cronjob Running for $log_type...");

    // Determine the date since which media should be processed
    $since_option = $type === 'two_years' ? 'unpublish_media_auto_date_two' : 'unpublish_media_auto_date';
    $since = get_option($since_option, '2025-01-01');
    $since_timestamp = strtotime($since);
    $time_period = $type === 'two_years' ? '-2 years' : '-5 years';
    $cutoff_date = strtotime($time_period, $since_timestamp);
    $exclude_media_ids = get_field('related_media_posts', 'options') ?: [];

    // Log the cutoff date and excluded IDs
    $formatted_date = date('m/d/Y', $cutoff_date);
    add_to_cron_log('Since: ' . $formatted_date, $log_type);
    add_to_cron_log('Exclude IDs: ' . (is_array($exclude_media_ids) ? implode(', ', $exclude_media_ids) : $exclude_media_ids), $log_type);

    // Set up the query arguments to fetch media posts
    $args = array(
        'post_type'      => 'media',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'post__not_in'   => $exclude_media_ids,
        'date_query'     => array(
            array(
                'before'    => date('Y-m-d', $cutoff_date),
                'inclusive' => true,
            )
        ),
        'fields'         => 'ids',
    );

    // Add specific meta_query for 'two_years' type
    if ($type === 'two_years') {
        $args['meta_query'] = array(
            array(
                'key'     => 'gated_content_disclaimer',
                'value'   => '1',
                'compare' => '='
            )
        );
    }

    // Fetch posts based on the query
    $posts_to_process = get_posts($args);
    $total_posts_found = count($posts_to_process);

    // If no posts are found, log and end the process
    if ($total_posts_found === 0) {
        $total_option = $type === 'two_years' ? 'cronjob_process_total_posts_found_two' : 'cronjob_process_total_posts_found_five';
        delete_option($total_option);
        $last_run_option = $type === 'two_years' ? 'last_run_update_auto_media_posts_event_two' : 'last_run_update_auto_media_posts_event';
        update_option($last_run_option, current_time('mysql'));
        add_to_cron_log("No posts found to process.", $log_type);
        return;
    }

    // Log the total number of posts found
    add_to_cron_log('Total Posts Found: ' . $total_posts_found, $log_type);

    // Process posts in chunks
    $batch_size = 5000;
    $chunks = array_chunk($posts_to_process, $batch_size);

    foreach ($chunks as $chunk) {
        foreach ($chunk as $post_id) {
            $background_process->push_to_queue(array('post_id' => $post_id, 'type' => $type));
        }
        $background_process->save()->dispatch();
    }

    // Update the last run time of the cronjob
    $last_run_option = $type === 'two_years' ? 'last_run_update_auto_media_posts_event_two' : 'last_run_update_auto_media_posts_event';
    update_option($last_run_option, current_time('mysql'));
}
