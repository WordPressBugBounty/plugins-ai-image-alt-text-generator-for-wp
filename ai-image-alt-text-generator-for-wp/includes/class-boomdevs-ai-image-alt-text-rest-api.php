<?php
require_once plugin_dir_path(dirname(__FILE__)) . '/vendor/autoload.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-image-generator-update-history.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';

class BDAIATG_Ai_Image_Alt_Text_Generator_Rest_Api
{
    public function __construct()
    {
        add_action('rest_api_init', function () {
            register_rest_route(
                'alt-text-generator/v1',
                '/fetch-data',
                array(
                    'methods' => 'GET',
                    'callback' => [$this, 'fetch_data'],
                    'permission_callback' => function () {
                        return true;
                    }
                )
            );
        });

        add_action('rest_api_init', function () {
            register_rest_route(
                'alt-text-generator/v1',
                '/fetch-jobs',
                array(
                    'methods' => 'GET',
                    'callback' => [$this, 'fetch_jobs'],
                    'permission_callback' => function () {
                        return true;
                    }
                )
            );
        });

        add_action('rest_api_init', function () {
            register_rest_route(
                'alt-text-generator/v1',
                '/fetch-bulk-alt-text',
                array(
                    'methods' => 'POST',
                    'callback' => [$this, 'fetch_bulk_alt_text'],
                    'permission_callback' => '__return_true'
                )
            );
        });
        add_action('rest_api_init', function () {
            register_rest_route(
                'alt-text-generator/v1',
                '/delete-bulk-generating-status',
                array(
                    'methods' => 'POST',
                    'callback' => [$this, 'delete_bulk_generating_status'],
                    'permission_callback' => '__return_true',
                )
            );
        });
    }

    public function fetch_bulk_alt_text(WP_REST_Request $request)
    {
        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';
        $alt_text_description = isset($settings['bdaiatg_alt_description']) ? $settings['bdaiatg_alt_description'] : '';

        $body = $request->get_json_params();
        $attachment_id = intval($body['attachment_id']);
        $alt_text = sanitize_text_field($body['alt_text']);
        $alt_title = '';
        $alt_caption = '';
        $alt_description = '';
        $ai_alt_description = sanitize_text_field($body['alt_text_description']);

        if (isset($image_description[0]) && $image_description[0] === 'update_description') {
            $alt_description = $alt_text;
        }

        if (isset($image_caption[0]) && $image_caption[0] === 'update_caption') {
            $alt_caption = $alt_text;
        }

        if (isset($image_title[0]) && $image_title[0] === 'update_title') {
            $alt_title = $alt_text;
        }

        if (intval($alt_text_description) === 1 && !empty($ai_alt_description) && !$image_description) {
            $alt_description = $ai_alt_description;
        }

        $update_result = wp_update_post([
            'ID' => $attachment_id,
            'post_content' => $alt_description,
            'post_title' => $alt_title,
            'post_excerpt' => $alt_caption,
        ], true);

        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

        $args = array(
            'attachment_id' => $attachment_id,
        );

        AltUpdateHistory::store($args);

        if (is_wp_error($update_result)) {
            return new WP_Error(
                'update_failed',
                'Failed to update attachment description.',
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'status' => 'success',
            'message' => 'Attachment data updated successfully.',
            'data' => [
                'attachment_id' => $attachment_id,
                'alt_text' => $alt_text,
                'alt_text_description' => $alt_description,
            ],
        ]);
    }

    public function delete_bulk_generating_status(WP_REST_Request $request)
    {
        $result = get_option('bdaiatg_bulk_generating');
        // var_dump($result);
        // die();

        if ($result) {
            $delete_option = delete_option('bdaiatg_bulk_generating');
            if ($delete_option) {
                wp_send_json_success(array(
                    'status' => 'success',
                    'message' => 'Bulk generating status deleted successfully.',
                ), 200);
            } else {
                wp_send_json_error(array(
                    'status' => 'error',
                    'message' => 'Bulk generating status delete failed.',
                ), 400);
            }
        } else {
            wp_send_json_error(array(
                'status' => 'error',
                'message' => 'Option Not found.',
            ), 400);
        }
    }

    public function fetch_data() {
        if(!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Permission denied!',
            ));
            return false;
        }

        $bulk_alt_text_options = get_option('bulk_alt_text_processing');

        if(isset($bulk_alt_text_options)) {
            $bulk_alt_text_processing = $bulk_alt_text_options;

            wp_send_json_success(array(
                'bulk_alt_processing' => $bulk_alt_text_processing,
            ));
        }
    }

     public function fetch_jobs() {
        if(!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Permission denied!',
            ));
            return false;
        }

        $bulk_alt_text_jobs_array = get_option('altgen_attachments_jobs');

        if($bulk_alt_text_jobs_array) {
            $total_jobs_count = count($bulk_alt_text_jobs_array);

            $all_true = true;
            foreach ($bulk_alt_text_jobs_array as $item) {
                if ($item['status'] !== true) {
                    $all_true = false;
                    break;
                }
            }

            $count_true = 0;
            foreach ($bulk_alt_text_jobs_array as $item) {
                if ($item['status'] === true) {
                    $count_true++;
                }
            }

            if ($count_true === 0) {
                $progress_percentage = 0;
            } else {
                $progress_percentage = round(($count_true / $total_jobs_count) * 100);
            }

            wp_send_json_success(array(
                'progress_percentage' => $progress_percentage,
                'total_jobs_count' => $total_jobs_count,
                'count_increase' => $count_true,
                'all_status' => $all_true
            ));
        } else {
            wp_send_json_success(array(
                'progress_percentage' => 0,
                'total_jobs_count' => null,
                'count_increase' => 0,
                'all_status' => true
            ));
        }
    }
}