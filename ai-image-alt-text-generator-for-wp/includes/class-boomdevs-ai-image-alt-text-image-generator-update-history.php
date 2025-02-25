<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AltUpdateHistory {
    public static function store($generated_alt, $image_url) {
        // Generate a normalized base name by removing variation parameters
        $base_name = self::normalizeImageUrl($image_url);

        $existing_post = self::check($base_name, false);

        if (!$existing_post) {
            wp_insert_post(array(
                'post_type' => 'alt-history',
                'post_title' => basename($base_name),
                'post_status' => 'publish',
                'meta_input' => array(
                    'image_url' => $image_url,
                    'base_name' => $base_name,
                    'alt_text' => $generated_alt,
                    'url_name' => $image_url,
                    'generated_time' => current_time('mysql'),
                    'generated_count' => 1
                )
            ));
        } else {
            $generated_count = get_post_meta($existing_post->ID, 'generated_count', true);
            $generated_count = $generated_count ? intval($generated_count) + 1 : 1;
            update_post_meta($existing_post->ID, 'generated_count', $generated_count);
            update_post_meta($existing_post->ID, 'alt_text', $generated_alt);
            update_post_meta($existing_post->ID, 'generated_time', current_time('mysql'));
            update_post_meta($existing_post->ID, 'image_url', $image_url);
//            update_post_meta($existing_post->ID, 'url_name', $image_url);
        }
    }

    public static function check($base_name, $increment = false) {
        $args = array(
            'post_type' => 'alt-history',
            'meta_key' => 'base_name',  // Query by normalized base name
            'meta_value' => $base_name,
            'posts_per_page' => 1,
        );

        $existing_posts = get_posts($args);

        if ($existing_posts) {
            $existing_post = $existing_posts[0];

            if ($increment) {
                $generated_count = get_post_meta($existing_post->ID, 'generated_count', true);
                $generated_count = $generated_count ? intval($generated_count) + 1 : 1;
                update_post_meta($existing_post->ID, 'generated_count', $generated_count);
            }

            return $existing_post;
        }

        return null;
    }

    private static function normalizeImageUrl($url) {
        // Remove any query parameters or size variations
        $base_url = preg_replace('/\?.*/', '', $url);
        $base_url = preg_replace('/-\d+x\d+(?=\.[a-zA-Z]+$)/', '', $base_url);
        return $base_url;
    }
}