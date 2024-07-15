<?php
/**
 * Generate alt, W and H attr for an image based on its URL or metadata.
 *
 * This function attempts to generate alt text for an image using the following methods:
 * 1: Construct a new URL with the home domain
 * 2. Get all information to the image from the metadata in WP
 * 3. Validate what type of alt will be set
 *
 * @since 2.0.0
 *
 * @param string $url The URL of the image.
 * @return string Alt text for the image.
 */
function generate_img_info($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        // Step 1: Construct a new URL with the home domain
        $parsed_url = parse_url($url);

        if (!empty($parsed_url)) {
            $new_domain = parse_url(home_url());
            $new_image_url = $new_domain['scheme'] . "://" . $new_domain['host'] . $parsed_url['path'];
            // Now $new_image_url contains the URL updated with the new domain

            // Step 2: Get all information to the image from the metadata
            $img_id = attachment_url_to_postid($new_image_url); // Tries to convert an attachment URL into a post ID
            $img_meta_data = get_post_meta($img_id)['_wp_attachment_metadata']; // Get the image information stored in the database
            $img_meta_data_uns = unserialize($img_meta_data[0]);
            $img_title = $img_meta_data_uns['image_meta']['title'];
            $img_w = $img_meta_data_uns['width'];
            $img_h = $img_meta_data_uns['height'];
            $alt_wp = get_post_meta($img_id, '_wp_attachment_image_alt', true); // Retrieve alt text from the WordPress attachment if available.
            $alt = '';

            // Step 3: Validate what type of alt will be set
            if (!empty($alt_wp)) {
                $alt = $alt_wp;
            } elseif (!empty($img_title)) {
                $alt = $img_title;
            } else {
                $img_file = $img_meta_data_uns['file'];
                $img_name = pathinfo($img_file, PATHINFO_FILENAME);
                $cleaned_img_name = preg_replace('/[\d-]+/', ' ', $img_name);
                $final_result = ucwords(trim($cleaned_img_name));

                // Set alt text based to the name of the filename
                $alt = $final_result;
            }

            return "alt='{$alt}' width='{$img_w}' height='{$img_h}'";
        } else {
            return false;
        }
    }
}
