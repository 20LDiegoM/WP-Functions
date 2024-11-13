<?php

namespace xxUnpu\Table;

use WP_Query;
use WP_List_Table;
use WP_Post;

class MediaTable extends WP_List_Table
{
    /**
     * Constructor for the MediaTable class.
     */
    public function __construct()
    {
        // Set up the list table with singular and plural labels
        parent::__construct([
            'singular' => 'media',
            'plural' => 'media'
        ]);
    }

    /**
     * Check if the current user has permission to make AJAX requests.
     *
     * @return bool True if the user can edit posts, false otherwise.
     */
    public function ajax_user_can()
    {
        return current_user_can('editor');
    }

    /**
     * Display a message when there are no items found.
     */
    public function no_items()
    {
        echo 'No people found';
    }

    /**
     * Get a list of columns for the list table.
     *
     * @return array Array of column names.
     */
    function get_columns()
    {
        return array(
            'post_title' => 'Title',
            'post_id' => 'ID',
            'status_post' => 'Status Post',
            'status_acf' => 'Gated Content Status',
            'date' => 'Date',
            'lang' => 'Language'
        );
    }

    /**
     * Get a list of hidden columns for the list table.
     *
     * @return array Array of column names that are hidden.
     */
    public function get_hidden_columns()
    {
        return [];
    }

    /**
     * Get a list of sortable columns for the list table.
     *
     * @return array Array of sortable columns.
     */
    protected function get_sortable_columns()
    {
        return [];
    }

    /**
     * Prepare the list table items.
     */
    public function prepare_items()
    {
        // Set up column headers
        $this->_column_headers = array(
            $this->get_columns(),
            $this->get_hidden_columns(),
            $this->get_sortable_columns(),
        );

        // Handle search input
        $search = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';

        $unpu_per_page = 20;
        $paged = $this->get_pagenum();

        // Query arguments to fetch media posts
        $args = [
            'post_type' => 'media',
            'posts_per_page' => $unpu_per_page,
            'offset' => ($paged - 1) * $unpu_per_page,
        ];

        // Add search term if present
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Add orderby and order parameters if set
        if (isset($_REQUEST['orderby'])) {
            $args['orderby'] = $_REQUEST['orderby'];
        }

        if (isset($_REQUEST['order'])) {
            $args['order'] = $_REQUEST['order'];
        }

        // Execute the query
        $query = new WP_Query($args);
        $this->items = $query->posts;

        // Set up pagination arguments
        $this->set_pagination_args(
            array(
                'total_items' => $query->found_posts,
                'per_page'    => $unpu_per_page,
            )
        );
    }

    /**
     * Generate the list table rows.
     */
    public function display_rows()
    {
        foreach ($this->items as $media) {
            echo "\n\t" . $this->single_row($media);
        }
    }

    /**
     * Generate HTML for a single row.
     *
     * @param WP_Post $media The current media item.
     * @return string HTML output for a single row.
     */
    public function single_row($media)
    {
        $r = "<tr id='media-$media->ID'>";

        // Set up actions for the 'edit' and 'view' links
        $actions = [];
        $edit_page = 'post.php?' . http_build_query([
            'post' => $media->ID,
            'action' => 'edit'
        ]);
        $view_link = get_permalink($media->ID);
        $actions['edit'] = "<a target='_blank' href=\"$edit_page\">Edit</a>";
        $actions['view'] = "<a href=\"$view_link\">View</a>";

        // Get column information
        list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

        // Loop through each column and generate cell content
        foreach ($columns as $column_name => $column_display_name) {
            $css_classes = "$column_name column-$column_name";
            if ($primary === $column_name) {
                $css_classes .= ' has-row-actions column-primary';
            }

            if (in_array($column_name, $hidden, true)) {
                $css_classes .= ' hidden';
            }

            $data = 'data-colname="' . wp_strip_all_tags($column_display_name) . '"';
            $attributes = "class='$css_classes' $data";
            $r .= "<td $attributes>";

            // Render cell content based on the column name
            switch ($column_name) {
                case 'post_id':
                    $post_id = $media->ID;
                    $r .= ucfirst($post_id);
                    break;
                case 'post_title':
                    $name = $media->post_title ?: '(no name)';
                    $r .= "<strong><a href=\"{$view_link}\">{$name}</a></strong><br />";
                    break;
                case 'status_post':
                    $post_status = get_post_status($media->ID);
                    $r .= ucfirst($post_status);
                    break;
                case 'status_acf':
                    $status_acf = get_post_meta($media->ID, 'gated_content_disclaimer', true);
                    $r .= !empty($status_acf) ? 'Active' : 'Inactive';
                    break;
                case 'date':
                    $status = get_the_date('d M Y', $media->ID);
                    $r .= ucfirst($status);
                    break;
                case 'lang':
                    $translations = pll_the_languages(array('raw' => 1));
                    $lang = pll_get_post_language($media->ID);
                    $r .= '<img src="' . $translations[$lang]['flag'] . '" alt="' . $translations[$lang]['name'] . '" width="16" height="11" style="width: 16px; height: 11px;" />';
                    break;
            }

            // Add row actions if this is the primary column
            if ($primary === $column_name) {
                $r .= $this->row_actions($actions);
            }

            $r .= '</td>';
        }

        $r .= '</tr>';

        return $r;
    }
}
