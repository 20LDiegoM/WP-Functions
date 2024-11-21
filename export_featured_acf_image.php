<?php
/**
 * Generate and download a CSV file with information about "people" posts in WordPress.
 * This includes both the featured image (_thumbnail_id) and a custom ACF field ('people_picture').
 * The CSV contains details like image IDs, URLs, and the original dimensions (width x height).
 * 
 * Usage: Add ?download_csv=1 to any page URL to trigger the CSV download.
 */

if (isset($_GET['download_csv']) && $_GET['download_csv'] === '1') {
    global $wpdb;

    // Query to fetch post data, featured image, and ACF custom field
    $results = $wpdb->get_results("
        SELECT
            p.ID AS post_id,
            p.post_title,
            p.post_date,
            pm_thumbnail.meta_value AS thumbnail_id,
            p2.guid AS thumbnail_url,
            pm_thumbnail_meta.meta_value AS thumbnail_metadata,
            pm_acf.meta_value AS acf_picture_id,
            p3.guid AS acf_picture_url,
            pm_acf_meta.meta_value AS acf_picture_metadata
        FROM
            {$wpdb->prefix}posts p
        LEFT JOIN
            {$wpdb->prefix}postmeta pm_thumbnail ON p.ID = pm_thumbnail.post_id AND pm_thumbnail.meta_key = '_thumbnail_id'
        LEFT JOIN
            {$wpdb->prefix}posts p2 ON pm_thumbnail.meta_value = p2.ID
        LEFT JOIN
            {$wpdb->prefix}postmeta pm_thumbnail_meta ON pm_thumbnail.meta_value = pm_thumbnail_meta.post_id AND pm_thumbnail_meta.meta_key = '_wp_attachment_metadata'
        LEFT JOIN
            {$wpdb->prefix}postmeta pm_acf ON p.ID = pm_acf.post_id AND pm_acf.meta_key = 'people_picture'
        LEFT JOIN
            {$wpdb->prefix}posts p3 ON pm_acf.meta_value = p3.ID
        LEFT JOIN
            {$wpdb->prefix}postmeta pm_acf_meta ON pm_acf.meta_value = pm_acf_meta.post_id AND pm_acf_meta.meta_key = '_wp_attachment_metadata'
        WHERE
            p.post_type = 'people' AND
            p.post_status = 'publish'
    ", ARRAY_A);

    // Create CSV file
    $csv_file = fopen('php://output', 'w');

    // Set headers to trigger download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="People_with_Thumbnails_and_ACF_Pictures.csv"');

    // Write CSV headers
    fputcsv($csv_file, [
        'Post ID',
        'Title',
        'Date',
        'Thumbnail ID',
        'Thumbnail URL',
        'Thumbnail Size',
        'ACF Picture ID',
        'ACF Picture URL',
        'ACF Picture Size'
    ]);

    // Process each result and write to the CSV
    foreach ($results as $row) {
        // Process featured image metadata
        $thumbnail_metadata = maybe_unserialize($row['thumbnail_metadata']);
        $thumbnail_size = '';
        if (!empty($thumbnail_metadata) && isset($thumbnail_metadata['width'], $thumbnail_metadata['height'])) {
            $thumbnail_size = "{$thumbnail_metadata['width']}x{$thumbnail_metadata['height']}";
        }

        // Process ACF picture metadata
        $acf_picture_metadata = maybe_unserialize($row['acf_picture_metadata']);
        $acf_picture_size = '';
        if (!empty($acf_picture_metadata) && isset($acf_picture_metadata['width'], $acf_picture_metadata['height'])) {
            $acf_picture_size = "{$acf_picture_metadata['width']}x{$acf_picture_metadata['height']}";
        }

        // Write data to CSV
        fputcsv($csv_file, [
            $row['post_id'],
            $row['post_title'],
            $row['post_date'],
            $row['thumbnail_id'],
            $row['thumbnail_url'],
            $thumbnail_size,
            $row['acf_picture_id'],
            $row['acf_picture_url'],
            $acf_picture_size
        ]);
    }

    // Close the CSV file and end the script
    fclose($csv_file);
    exit;
}
?>
