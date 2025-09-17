<?php

if (!defined('ABSPATH')) {
    exit;
}

class Boomdevs_Ai_Image_Alt_Text_Generator_Image_Update_History
{
    function __construct()
    {
        add_action("wp_ajax_update_attachment_meta", [$this, 'update_attachment_meta']);
        add_action("wp_ajax_nopriv_update_attachment_meta", [$this, 'update_attachment_meta']);
    }

    public static function run()
    {
        return new self();
    }

    public static function store($args = array())
    {
        global $wpdb;

        $current_user = wp_get_current_user();

        $defaults = array(
            'attachment_id' => '',
            'total_count' => 1,
            'gen_time' => current_time('mysql'),
            'gen_by' => $current_user->ID,
        );

        $table_name = $wpdb->prefix . 'ai_alt_text_generator_history';
        $data = wp_parse_args($args, $defaults);

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, total_count FROM $table_name WHERE attachment_id = %d",
            $data['attachment_id']
        ));

        if ($existing) {
            $wpdb->update(
                $table_name,
                array(
                    'total_count' => $existing->total_count + 1,
                    'gen_time' => current_time('mysql'),
                ),
                array('id' => $existing->id),
                array('%d', '%s'),
                array('%d')
            );

            return $existing->id;
        } else {
            $wpdb->insert(
                $table_name,
                $data,
                array('%d', '%d', '%s', '%s')
            );

            return $wpdb->insert_id;
        }
    }

    public function update_attachment_meta()
    {
        if (!isset($_POST['media_id']) || !isset($_POST['alt_text']) || !wp_verify_nonce($_POST['nonce'], 'import_csv')) {
            wp_send_json_error(['message' => 'Invalid request']);
            wp_die();
        }

        $media_id = intval($_POST['media_id']);
        $alt_text = sanitize_text_field($_POST['alt_text']);

        if (!get_post($media_id) || get_post_type($media_id) !== 'attachment') {
            wp_send_json_error(['message' => 'Update failed: media not found.']);
            wp_die();
        }

        update_post_meta($media_id, '_wp_attachment_image_alt', $alt_text);

        wp_send_json_success(['message' => 'Updated successfully']);
        wp_die();
    }

}
