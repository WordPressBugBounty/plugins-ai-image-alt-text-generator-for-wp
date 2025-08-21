<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/vendor/autoload.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-request.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-image-generator-history.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-image-generator-update-history.php';

class Boomdevs_Ai_Image_Alt_Text_Bulk_Image_Generator
{
    protected static $instance;
    protected $process_generate_bulk_post;

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->process_generate_bulk_post = new BDAIATG_Ai_Image_Alt_Text_Generator_Request();
        add_action("wp_ajax_bulk_alt_image_generator", [$this, 'bulk_alt_image_generator']);
        add_action("wp_ajax_nopriv_bulk_alt_image_generator", [$this, 'bulk_alt_image_generator']);

        add_action("wp_ajax_cancel_bulk_alt_image_generator", [$this, 'cancel_bulk_alt_image_generator']);
        add_action("wp_ajax_nopriv_cancel_bulk_alt_image_generator", [$this, 'cancel_bulk_alt_image_generator']);

        add_action("wp_ajax_check_no_credit", [$this, 'check_no_credit']);
        add_action("wp_ajax_nopriv_check_no_credit", [$this, 'check_no_credit']);

        add_action("wp_ajax_get_all_added_jobs", [$this, "get_total_jobs_lists"]);
        add_action("wp_ajax_nopriv_get_total_jobs_lists", [$this, 'get_total_jobs_lists']);
    }

    public function check_no_credit()
    {
        $no_credit = get_option('error_during_background_task_no_credit');
        if ($no_credit) {
            wp_send_json_error(array(
                'message' => 'You have not enough credit to generate image alt text please buy credit from here <a href="https://aialttextgenerator.com/pricing">Buy now</a>',
            ));
        }
    }

    public function verify_authorization()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'import_csv')) {
            wp_send_json_error(array(
                'message' => 'Permission denied!',
            ));
            return false;
        }
    }

    public function get_total_jobs_lists()
    {
        $this->verify_authorization();

        $all_jobs = get_option('altgen_attachments_jobs');
        wp_send_json_success(array(
            'data' => is_array($all_jobs) ? count($all_jobs) : 0,
        ), 200);
    }

    public function cancel_bulk_alt_image_generator()
    {
        $this->verify_authorization();

        $this->process_generate_bulk_post->cancel();
        delete_option('altgen_attachments_jobs');
        delete_option('error_during_background_task_no_credit'); // Clear error flag
        
        wp_send_json_success(array(
            'message' => 'Bulk generation cancelled successfully.',
        ), 200);
    }

    public function check_available_token($api_key)
    {
        $url = BDAIATG_API_URL . '/wp-json/alt-text-generator/v1/available-token';
        $body_data = array(
            'token' => $api_key,
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body_data),
            'sslverify' => false,
            'timeout' => 30, // Add timeout
        );

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            error_log('API Token Check Error: ' . $response->get_error_message());
            wp_send_json_error(array(
                'message' => "Something went wrong try again later.",
            ));
            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($response_code !== 200) {
                error_log('API Token Check HTTP Error: ' . $response_code);
                return false;
            }
            
            return json_decode($response_body);
        }
    }

    public function bulk_alt_image_generator()
    {
        $this->verify_authorization();
        
        // Clear any previous error flags
        delete_option('error_during_background_task_no_credit');
        
        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();
        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';

        if (!$api_key && !BDAIATG_DEVELOPMENT) {
            wp_send_json_error(array(
                'message' => "Set api key in ai alt text generator settings",
            ));
            return false;
        }

        $check_available_credit = $this->check_available_token($api_key);
        if (!$check_available_credit || !$check_available_credit->data->status && !BDAIATG_DEVELOPMENT) {
            wp_send_json_error(array(
                'message' => "You don't have sufficient credits buy more and try again.",
            ));
            return false;
        }

        $file_extensions = isset($settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types']) ? $settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types'] : '';
        $overrite_existing_images = isset($_REQUEST['overrite_existing_images']) ? sanitize_text_field($_REQUEST['overrite_existing_images']) : 'false';

        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => 'image',
        );

        $attachments = get_posts($args);
        $alt_text_attachments = array();
        $total_image_send = 0;
        $max_image_send = 1000;

        foreach ($attachments as $attachment) {
            if ($total_image_send >= $max_image_send) {
                break;
            }

            $attachment_url = wp_get_attachment_url($attachment->ID);
            if (!$attachment_url) {
                continue; // Skip if URL is not available
            }

            $path = wp_parse_url($attachment_url, PHP_URL_PATH);
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            $should_include = false;

            if ($file_extensions) {
                $file_extensions_array = explode(",", $file_extensions);
                $allowed_extensions = array_map('trim', $file_extensions_array);
                if (in_array(strtolower($extension), array_map('strtolower', $allowed_extensions))) {
                    $should_include = true;
                }
            } else {
                $should_include = true; // Include all image types if no filter
            }

            if ($should_include) {
                if ($overrite_existing_images === 'false') {
                    $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                    if (empty($alt_text)) {
                        $total_image_send++;
                        $alt_text_attachments[] = array(
                            'id' => $attachment->ID,
                            'url' => $attachment_url, // Fixed: was using 'file_url', now 'url'
                            'status' => false,
                        );
                    }
                } else {
                    $total_image_send++;
                    $alt_text_attachments[] = array(
                        'id' => $attachment->ID,
                        'url' => $attachment_url, // Fixed: was using 'file_url', now 'url'
                        'status' => false,
                    );
                }
            }
        }

        if (count($alt_text_attachments) === 0) {
            wp_send_json_error(array(
                'message' => "You don't have any missing alt text attachments to process!",
            ));
            return;
        }

        // Save all attachments to altgen_attachments_jobs
        update_option('altgen_attachments_jobs', $alt_text_attachments);

        // Push only the first attachment to background process
        if (!empty($alt_text_attachments)) {
            $this->process_generate_bulk_post->push_to_queue($alt_text_attachments[0]);
            $this->process_generate_bulk_post->save()->dispatch();
        }

        wp_send_json_success(array(
            'status' => true,
            'message' => 'Your bulk request for ' . count($alt_text_attachments) . ' images has been successfully queued for processing.',
            'total_jobs_count' => count($alt_text_attachments),
        ), 200);
    }

    public static function bulk_image_generator($item)
    {
        // Add error handling for malformed items
        if (!isset($item['id']) || !isset($item['url'])) {
            error_log('Malformed queue item: ' . print_r($item, true));
            return false;
        }

        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';
        $alt_text_length = isset($settings['bdaiatg_alt_text_length']) ? $settings['bdaiatg_alt_text_length'] : '';
        $alt_text_description = $settings['bdaiatg_alt_description'] ?? false;

        $data_send = [
            'website_url' => site_url(),
            'file_url' => $item['url'],
            'language' => $language,
            'keywords' => [],
            'focus_keyword' => '',
            'image_suffix' => $image_suffix,
            'image_prefix' => $image_prefix,
            'bdaiatg_alt_text_length' => $alt_text_length,
            'bdaiatg_alt_description' => $alt_text_description,
        ];

        $headers = array(   
            'Content-Type' => 'application/json', // Added content type
            'token' => $api_key,
        );

        $url = BDAIATG_API_URL . '/wp-json/alt-text-generator/v1/get-alt-text';

        $arguments = [
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode($data_send),
            'timeout' => 60, // Increased timeout for bulk processing
            'sslverify' => false,
        ];

        $response = wp_remote_post($url, $arguments);
        
        if (is_wp_error($response)) {
            error_log('API Error for attachment ' . $item['id'] . ': ' . $response->get_error_message());
            // Don't cancel the entire process for individual failures
            self::mark_item_as_failed($item['id']);
            self::process_next_item();
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('API HTTP Error for attachment ' . $item['id'] . ': ' . $response_code);
            self::mark_item_as_failed($item['id']);
            self::process_next_item();
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $make_obj = json_decode($body);

        if (!$make_obj || !isset($make_obj->success)) {
            error_log('Invalid API response for attachment ' . $item['id'] . ': ' . $body);
            self::mark_item_as_failed($item['id']);
            self::process_next_item();
            return false;
        }

        if ($make_obj->success === false) {
            error_log('API returned error for attachment ' . $item['id'] . ': ' . ($make_obj->message ?? 'Unknown error'));
            
            // Check if it's a credit issue
            if (isset($make_obj->message) && strpos(strtolower($make_obj->message), 'credit') !== false) {
                $instance = new self();
                $instance->process_generate_bulk_post->cancel();
                delete_option('altgen_attachments_jobs');
                update_option('error_during_background_task_no_credit', true);
                return false;
            }
            
            self::mark_item_as_failed($item['id']);
            self::process_next_item();
            return false;
        }

        // Verify we have the required data
        if (!isset($make_obj->data->generated_text)) {
            // error_log('Missing generated_text in API response for attachment ' . $item['id']);
            self::mark_item_as_failed($item['id']);
            self::process_next_item();
            return false;
        }

        if(isset($image_title[0]) && $image_title[0] === 'update_title') {
				$post = get_post($item['id']);
				$post->post_title = $make_obj->data->generated_text;
				wp_update_post($post);
			}

			if(isset($image_caption[0]) && $image_caption[0] === 'update_caption') {
				$post = get_post($item['id']);
				$post->post_excerpt = $make_obj->data->generated_text;
				wp_update_post($post);
			}
            
            if(($alt_text_description == '' || !$alt_text_description) && ($image_description[0] === 'update_description')) {
                $post = get_post($item['id']);
                $post->post_content = $make_obj->adata->generated_text;
                wp_update_post($post);
            }

            if($alt_text_description !== '') {
                $post = get_post($item['id']);
                $post->post_content = $make_obj->data->generated_description_text;
                wp_update_post($post);
            }

        // Update alt text
        update_post_meta($item['id'], '_wp_attachment_image_alt', sanitize_text_field($make_obj->data->generated_text));

        // Update job status
        $get_altgen_jobs = get_option('altgen_attachments_jobs', []);

        if ($get_altgen_jobs) {
            $id_to_update = $item['id'];
            $new_status = true;

            self::updateItemById($get_altgen_jobs, $id_to_update, $new_status);
            update_option('altgen_attachments_jobs', $get_altgen_jobs);

            // Track History - Fixed variable name
            $args = array(
                'attachment_id' => $item['id'], // Fixed: was using undefined $attachment_id
            );

            Boomdevs_Ai_Image_Alt_Text_Generator_Image_Update_History::store($args);
            
            // Process next item
            self::process_next_item();
        }

        return true;
    }

    /**
     * Mark an item as failed but don't stop the entire process
     */
    private static function mark_item_as_failed($attachment_id)
    {
        $get_altgen_jobs = get_option('altgen_attachments_jobs', []);
        
        if ($get_altgen_jobs) {
            self::updateItemById($get_altgen_jobs, $attachment_id, 'failed');
            update_option('altgen_attachments_jobs', $get_altgen_jobs);
        }
    }

    /**
     * Process the next item in the queue
     */
    private static function process_next_item()
    {
        $get_altgen_jobs = get_option('altgen_attachments_jobs', []);
        
        if ($get_altgen_jobs) {
            // Find the next unprocessed item
            $next_attachment = null;
            foreach ($get_altgen_jobs as $job) {
                if ($job['status'] === false) {
                    $next_attachment = $job;
                    break;
                }
            }

            if ($next_attachment) {
                $background_process = new BDAIATG_Ai_Image_Alt_Text_Generator_Request();
                $background_process->push_to_queue($next_attachment);
                $background_process->save()->dispatch();
            } else {
                // No more items to process, clean up
                self::delete_bulk_generating_status();
            }
        }
    }

    public static function delete_bulk_generating_status()
    {
        $get_altgen_jobs = get_option('altgen_attachments_jobs', []);

        if (!empty($get_altgen_jobs)) {
            $all_processed = true;
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($get_altgen_jobs as $item) {
                if ($item['status'] === false) {
                    $all_processed = false;
                    break;
                } elseif ($item['status'] === true) {
                    $success_count++;
                } elseif ($item['status'] === 'failed') {
                    $failed_count++;
                }
            }
            
            if ($all_processed) {
                delete_option('altgen_attachments_jobs');
                
                // Log completion stats
                error_log("Bulk alt text generation completed. Success: {$success_count}, Failed: {$failed_count}");
                
                // Optionally store completion stats
                update_option('altgen_last_bulk_stats', [
                    'completed_at' => current_time('mysql'),
                    'success_count' => $success_count,
                    'failed_count' => $failed_count,
                    'total_count' => $success_count + $failed_count
                ]);
            }
        }
    }

    public static function updateItemById(&$array, $id, $new_status)
    {
        foreach ($array as &$item) {
            if (($item['id'] === $id) && ($item['status'] !== true) && ($item['status'] !== 'failed')) {
                $item['status'] = $new_status;
                break; // Exit after first match
            }
        }
    }

    /**
     * Get bulk processing progress
     */
    public function get_bulk_progress()
    {
        $jobs = get_option('altgen_attachments_jobs', []);
        
        if (empty($jobs)) {
            return [
                'total' => 0,
                'completed' => 0,
                'failed' => 0,
                'pending' => 0,
                'percentage' => 0
            ];
        }

        $total = count($jobs);
        $completed = 0;
        $failed = 0;
        $pending = 0;

        foreach ($jobs as $job) {
            if ($job['status'] === true) {
                $completed++;
            } elseif ($job['status'] === 'failed') {
                $failed++;
            } else {
                $pending++;
            }
        }

        $percentage = $total > 0 ? round((($completed + $failed) / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'percentage' => $percentage
        ];
    }
}

// Initialize the class instance
Boomdevs_Ai_Image_Alt_Text_Bulk_Image_Generator::get_instance();