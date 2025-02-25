<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

class Boomdevs_Ai_Image_Alt_Text_Image_Generator_Post
{
    public function __construct()
    {
        add_action('init', array($this, 'Alt_History_Post_Type'));
        add_filter('manage_alt-history_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_alt-history_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('post_row_actions', array($this, 'hide_post_action'), 10, 2);
        add_filter('get_edit_post_link', array($this, 'disable_edit_link'), 10, 3);
    }

    public function Alt_History_Post_Type() {
        $labels = array(
            'name' => __('History', 'ai-image-alt-text-generator-for-wp'),
            'not_found' => __('No history found.', 'ai-image-alt-text-generator-for-wp'),
        );

        $args = array(
            'public' => true,
            'labels' => $labels,
            'supports' => array('custom-fields'),
            'show_ui' => true,
            'show_in_menu' => false,
            'has_archive' => false,
            'rewrite' => array('slug' => 'alt-history'),
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap' => true,
        );

        register_post_type('alt-history', $args);
    }
    public function add_custom_columns($columns)
    {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
//        $new_columns['url_name'] = __('URL', 'ai-image-alt-text-generator-for-wp');
        $new_columns['image'] = __('Image', 'ai-image-alt-text-generator-for-wp');
        $new_columns['alt_text'] = __('Alt Text', 'ai-image-alt-text-generator-for-wp');
        $new_columns['generated_time'] = __('Generated Time', 'ai-image-alt-text-generator-for-wp');
        $new_columns['total'] = __('Total Generated', 'ai-image-alt-text-generator-for-wp');

        return $new_columns;
    }

    public function custom_column_content($column, $post_id)
    {
        switch ($column) {
//            case 'url_name': // New case for URL name
//                $url_name = get_post_meta($post_id, 'url_name', true);
//                echo $url_name ? esc_html($url_name) : esc_html__('N/A', 'ai-image-alt-text-generator-for-wp');
//                break;
            case 'image':
                $image_url = get_post_meta($post_id, 'image_url', true);
                if ($image_url) {
                    echo '<img style="border-radius: 8px;" src="' . esc_url($image_url) . '" width="50" height="50" />';
                } else {
                    echo esc_html__('No Image', 'ai-image-alt-text-generator-for-wp');
                }
                break;
            case 'alt_text':
                $alt_text = get_post_meta($post_id, 'alt_text', true);
                echo $alt_text ? esc_html($alt_text) : '';
                break;
            case 'generated_time':
                $generated_time = get_post_meta($post_id, 'generated_time', true);
                echo $generated_time ? esc_html($generated_time) : esc_html__('N/A', 'ai-image-alt-text-generator-for-wp');
                break;
            case 'total':
                $generated_count = get_post_meta($post_id, 'generated_count', true);
                echo $generated_count ? esc_html($generated_count) : '0';
                break;
        }
    }

    public function hide_post_action($actions, $post)
    {
        if ($post->post_type === 'alt-history') {
            unset($actions['edit']);
            unset($actions['inline hide-if-no-js']);
            unset($actions['trash']);
            unset($actions['view']);
        }

        return $actions;
    }

    public function disable_edit_link($link, $post_id)
    {
        if (get_post_type($post_id) === 'alt-history') {
            return null;
        }
        return $link;
    }
}

new Boomdevs_Ai_Image_Alt_Text_Image_Generator_Post();