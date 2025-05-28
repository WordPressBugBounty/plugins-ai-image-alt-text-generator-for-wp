<?php

if (!defined('ABSPATH')) {
    exit;
}

class AltUpdateHistory {
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
                    'gen_by' => $data['gen_by'],
                ),
                array('id' => $existing->id),
                array('%d', '%s', '%s'),
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

}