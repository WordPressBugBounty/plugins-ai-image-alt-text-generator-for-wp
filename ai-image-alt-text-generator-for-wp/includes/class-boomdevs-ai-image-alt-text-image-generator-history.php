<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Boomdevs_Ai_Image_Alt_Text_Generator_History extends WP_List_Table
{
    private $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_alt_text_generator_history';

        parent::__construct([
            'singular' => 'AI Alt Text History',
            'plural' => 'AI Alt Text Histories',
            'ajax' => false
        ]);
    }

    /**
     * Prepare the items for the table
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $this->record_count();

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page
        ]);

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $this->get_history_data($per_page, $current_page);
    }

    /**
     * Get the total number of records
     */
    public function record_count()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    /**
     * Retrieve history data from the database
     */
    public function get_history_data($per_page, $page_number)
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'Permission denied!',
            ]);
            return false;
        }

        global $wpdb;

        $offset = ($page_number - 1) * $per_page;

        // Handle sorting
        $orderby = (!empty($_REQUEST['orderby'])) ? esc_sql($_REQUEST['orderby']) : 'gen_time';
        $order   = (!empty($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), ['ASC', 'DESC'])) 
            ? strtoupper($_REQUEST['order']) 
            : 'DESC';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT h.*, p.guid as image_url, p.post_excerpt as post_excerpt, u.display_name 
                 FROM {$this->table_name} h
                 LEFT JOIN {$wpdb->posts} p ON h.attachment_id = p.ID
                 LEFT JOIN {$wpdb->users} u ON h.gen_by = u.ID
                 ORDER BY $orderby $order
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        foreach ($results as &$result) {
            $alt_text = get_post_meta($result['attachment_id'], '_wp_attachment_image_alt', true);
            $result['gen_text'] = $alt_text;
        }

        return $results;
    }

    /**
     * Define which columns are hidden
     */
    public function get_hidden_columns()
    {
        return ['id'];
    }

    /**
     * Define the columns that are going to be used in the table
     */
    public function get_columns()
    {
        return [
            'baiatgd_history_media_id'    => 'Media ID',
            'baiatgd_history_image'       => 'Image',
            'baiatgd_history_gen_text'    => 'Alt Text',
            'baiatgd_history_gen_time'    => 'Processed On',
            'baiatgd_history_total_count' => 'Total Count',
        ];
    }

    /**
     * Define which columns are sortable
     */
    public function get_sortable_columns()
    {
        return [
            'baiatgd_history_media_id'    => ['attachment_id', false],
            'baiatgd_history_gen_time'    => ['gen_time', false],
            'baiatgd_history_total_count' => ['total_count', false],
        ];
    }

    /**
     * Column default method
     */
    public function column_default($item, $column_name)
    {
        $edit_url = admin_url('upload.php?item=' . intval($item['attachment_id']));
        $image_url = wp_get_attachment_image_url($item['attachment_id'], 'thumbnail');
        switch ($column_name) {
            case 'baiatgd_history_media_id':
                return sprintf(
                    '<a href="%s" target="_blank"><h3 class="baiatgd_history_media_id">%s</h3></a>',
                    esc_url($edit_url),
                    esc_html($item['attachment_id'])
                );

            case 'baiatgd_history_image':
                return $item['image_url']
                    ? sprintf(
                        '<a href="%s" target="_blank"><img src="%s" class="image-preview"></a>',
                        esc_url($edit_url),
                        esc_url($image_url)
                    )
                    : 'Media not found';

            case 'baiatgd_history_gen_text':
                return sprintf('<div><textarea class="alt-text-input alt-text">%s</textarea>
                    <div class="update-message-action">
                        <button class="update-alt" data-media-id="' . esc_attr($item['attachment_id']) . '">Update</button>
                        <span class="update-message">Alt text updated successfully.</span>
                    </div>
                </div>', esc_textarea($item['gen_text']));

            case 'baiatgd_history_gen_time':
                return sprintf('<p class="baiatgd_history_gen_time">' . esc_html($item['gen_time']) . '</p>');

            case 'baiatgd_history_total_count':
                return sprintf('<h3 class="baiatgd_history_total_count">' . esc_html($item['total_count']) . ' ' . ($item['total_count'] > 1 ? 'Times' : 'Time') . '</h3>');

            default:
                return print_r($item, true);
        }
    }

    /**
     * Render the table
     */
    public function display()
    {
        parent::display();
    }
}
