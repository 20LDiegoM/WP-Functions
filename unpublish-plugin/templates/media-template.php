<?php

use xxUnpu\Table\MediaTable;

// Instantiate the MediaTable class and prepare the items for display
$media_table = new MediaTable();
$media_table->prepare_items();

// Retrieve status and scheduling information for the 5-year cronjob
$cronjob_five_years_status = get_option('unpublish_media_auto_status', '0');
$next_scheduled_date_five = get_option('next_run_update_auto_media_posts_event', 'Not scheduled');
$last_run_date = get_option('last_run_update_auto_media_posts_event', 'Never');

// Retrieve status and scheduling information for the 2-year cronjob
$cronjob_two_years_status = get_option('unpublish_media_auto_status_two', '0');
$next_scheduled_timestamp_two = wp_next_scheduled('update_auto_media_posts_event_two');
$next_scheduled_date_two = $next_scheduled_timestamp_two ? date('Y-m-d H:i:s', $next_scheduled_timestamp_two) : 'Not scheduled';
$last_run_date_two = get_option('last_run_update_auto_media_posts_event_two', 'Never');

// Determine if date input fields should be disabled based on cronjob status
$input_date_two = $cronjob_two_years_status ? 'disabled=""' : '';
$input_date_five = $cronjob_five_years_status ? 'disabled=""' : '';

?>
<div class="wrap">
    <h2>Media Posts</h2>

    <h3>Automation Process</h3>

    <div class="container">
        <!-- Form for the 5-year cronjob automation -->
        <div class="col">
            <form class="enable-cronjob-form" action="<?= rest_url('unpu/media-auto-update-cronjob-rest') ?>">
                <p>
                    <label for="cronjob-checkbox"> <strong>Non-gated content:</strong> </label>
                    <input type="checkbox" name="cronjob-checkbox" class="js-cronjob-five-years" value="1" <?php checked(get_option('unpublish_media_auto_status'), '1'); ?>>
                    <label for="cronjob-date">Select Start Date:</label>
                    <input type="date" name="cronjob-date" value="<?= esc_attr(get_option('unpublish_media_auto_date', date('Y-m-d'))); ?>" <?= $input_date_five; ?>>
                    <input type="hidden" name="cron-type" value="five-years">
                </p>

                <!-- Display the activation status of the 5-year cronjob -->
                <p>Status:
                    <?php if ($cronjob_five_years_status === '1') : ?>
                        <span style="color: green;">Activated</span>
                    <?php else : ?>
                        <span style="color: red;">Deactivated</span>
                    <?php endif; ?>
                </p>

                <p>If this option is activated all old posts older than 5 years since <?= get_option('unpublish_media_auto_date'); ?> will be automatically unpublished, excluding the posts in the field below.</p>
                <p>This cronjob is executed every year</p>
                <p>Last Run: <strong class="cronjob-five-years-last-run"><?= esc_html($last_run_date); ?></strong></p>
                <p>Next Scheduled Run: <strong class="cronjob-five-years-next-run"><?= esc_html($next_scheduled_date_five); ?></strong></p>

                <!-- Log section for the 5-year cronjob -->
                <div class="cronjob-log">
                    <h4>Cronjob Log:</h4>
                    <button id="clear-log-five-years" class="button button-secondary clear-log-btn" type="button" data-type="five-years">Clear Log</button>
                    <textarea id="cronjob-log-five-years" rows="10" style="width:100%;" readonly><?= esc_textarea(get_option('cronjob_log_five_years', 'No logs yet.')); ?></textarea>
                </div>

                <button class="button button-primary" type="submit">Save</button>
            </form>
        </div>

        <!-- Form for the 2-year cronjob automation -->
        <div class="col">
            <form class="enable-cronjob-form" action="<?= rest_url('unpu/media-auto-update-cronjob-rest') ?>">
                <p>
                    <label for="cronjob-checkbox"> <strong>Gated content:</strong> </label>
                    <input type="checkbox" name="cronjob-checkbox" class="js-cronjob-two-years" value="1" <?php checked(get_option('unpublish_media_auto_status_two'), '1'); ?>>
                    <label for="cronjob-date-two">Select Start Date:</label>
                    <input type="date" name="cronjob-date" value="<?= esc_attr(get_option('unpublish_media_auto_date_two', date('Y-m-d'))); ?>" <?= $input_date_two; ?>>
                    <input type="hidden" name="cron-type" value="two-years">
                </p>

                <!-- Display the activation status of the 2-year cronjob -->
                <p>Status:
                    <?php if ($cronjob_two_years_status === '1') : ?>
                        <span style="color: green;">Activated</span>
                    <?php else : ?>
                        <span style="color: red;">Deactivated</span>
                    <?php endif; ?>
                </p>

                <p>If this option is activated all old posts older than 2 years since Jan 1, 2025 and with gated content activated will be automatically unpublished, excluding the posts in the field below.</p>
                <p>This cronjob is executed every day</p>
                <p>Last Run: <strong class="cronjob-two-years-last-run"><?= esc_html($last_run_date_two); ?></strong></p>
                <p>Next Scheduled Run: <strong class="cronjob-two-years-next-run"><?= esc_html($next_scheduled_date_two); ?></strong></p>

                <!-- Log section for the 2-year cronjob -->
                <div class="cronjob-log">
                    <h4>Cronjob Log:</h4>
                    <button id="clear-log-two-years" class="button button-secondary clear-log-btn" type="button" data-type="two-years">Clear Log</button>
                    <textarea id="cronjob-log-two-years" rows="10" style="width:100%;" readonly><?= esc_textarea(get_option('cronjob_log_two_years', 'No logs yet.')); ?></textarea>
                </div>

                <button class="button button-primary" type="submit">Save</button>
            </form>
        </div>
    </div>

    <!-- Form for excluding specific media posts -->
    <form class="exclude-media-posts" action="<?= rest_url('unpu/exclude-media-auto-exclude') ?>">
        <?php
        acf_form(array(
            'post_id'       => 'options',
            'field_groups'  => array('group_media_auto_relationship'),
            'submit_value'  => 'Save',
        ));
        ?>
    </form>

    <!-- Search box and display of media table -->
    <form method="post">
        <?php $media_table->search_box('search', 'unpu_xx_search_id'); ?>
        <?php $media_table->display(); ?>
    </form>
</div>
