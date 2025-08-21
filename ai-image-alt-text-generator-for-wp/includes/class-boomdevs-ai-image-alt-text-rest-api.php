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
    }

    public function fetch_data()
    {
        $bulk_alt_text_options = get_option('bulk_alt_text_processing');

        if (isset($bulk_alt_text_options)) {
            $bulk_alt_text_processing = $bulk_alt_text_options;
            wp_send_json_success(array(
                'bulk_alt_processing' => $bulk_alt_text_processing,
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'No bulk processing data found.',
            ));
        }
    }
    public function fetch_jobs()
    {
        $bulk_alt_text_jobs_array = get_option('altgen_attachments_jobs', []);

        if ($bulk_alt_text_jobs_array) {
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

            if ($total_jobs_count === 0) {
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
                'total_jobs_count' => 0,
                'count_increase' => 0,
                'all_status' => true
            ));
        }
    }
}
