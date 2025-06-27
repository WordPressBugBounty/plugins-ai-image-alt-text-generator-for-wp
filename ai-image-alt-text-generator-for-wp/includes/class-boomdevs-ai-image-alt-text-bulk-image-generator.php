<?php
// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Include required dependencies
require_once plugin_dir_path(dirname(__FILE__)) . '/vendor/autoload.php';
// require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-request.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-image-generator-history.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-image-generator-update-history.php';

class Boomdevs_Ai_Image_Alt_Text_Bulk_Image_Generator
{
    // Singleton instance
    protected static $instance;
    // Background process handler
    protected $process_generate_bulk_post;

    // Get singleton instance
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Constructor to initialize actions and background process
    public function __construct()
    {
        // for bulk image generator
        add_action("wp_ajax_send_bulk_images", [$this, 'send_bulk_images']);
        add_action("wp_ajax_nopriv_send_bulk_images", [$this, 'send_bulk_images']);

        // AJAX actions for checking credit status
        add_action("wp_ajax_check_no_credit", [$this, 'check_no_credit']);
        add_action("wp_ajax_nopriv_check_no_credit", [$this, 'check_no_credit']);
    }

    // Check if user has sufficient credits
    public function check_no_credit()
    {
        $no_credit = get_option('error_during_background_task_no_credit');
        if ($no_credit) {
            wp_send_json_error(array(
                'message' => 'You have not enough credit to generate image alt text please buy credit from here <a href="https://aialttextgenerator.com/pricing">Buy now</a>',
            ));
        }
    }

    // Verify nonce for AJAX requests
    public function verify_authorization()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Permission denied!',
            ));
            return false;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'import_csv')) {
            wp_send_json_error(array(
                'message' => 'Permission denied!',
            ));
            return false;
        }
    }

    // Check available API credits
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
        );
        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => "Something went wrong try again later.",
            ));
            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body);
        }
    }

    // Send bulk images	
    public function send_bulk_images()
    {
        // Verify user authorization
        $this->verify_authorization();

        // Retrieve plugin settings
        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();
        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';

        // Check if API key is set (except in development mode)
        if (!$api_key && !BDAIATG_DEVELOPMENT) {
            wp_send_json_error(array(
                'message' => "Set api key in ai alt text generator settings",
            ));
            return false;
        }

        // Verify available API credits
        $check_available_credit = $this->check_available_token($api_key);
        if (!$check_available_credit->data->status && !BDAIATG_DEVELOPMENT) {
            wp_send_json_error(array(
                'message' => "You don't have sufficient credits buy more and try again.",
            ));
            return false;
        }

        // Get allowed file extensions and overwrite settings
        $file_extensions = isset($settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types']) ? $settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types'] : '';
        $overrite_existing_images = isset($_REQUEST['overrite_existing_images']) ? $_REQUEST['overrite_existing_images'] : false;

        //Attachment_settings
        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

        // Get API key and other settings
        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
        $alt_text_length = isset($settings['bdaiatg_alt_text_length']) ? $settings['bdaiatg_alt_text_length'] : '';
        $alt_text_description = isset($settings['bdaiatg_alt_description']) ? $settings['bdaiatg_alt_description'] : false;

        // Query all image attachments
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => 'image',
        );
        $attachments = get_posts($args);
        $alt_text_attachments = array();
        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email;
        $body_data = [
            'website_url' => site_url(),
            'email' => $user_email,
            'settings' => array(
                'language' => $language,
                'keywords' => array(),
                'focus_keyword' => '',
                'image_suffix' => $image_suffix,
                'image_prefix' => $image_prefix,
                'bdaiatg_alt_text_length' => $alt_text_length,
                'bdaiatg_alt_description' => $alt_text_description,
            ),
            'attachments' => array()
        ];

        // Loop through each attachment
        foreach ($attachments as $attachment) {
            $path = wp_parse_url($attachment->guid, PHP_URL_PATH);
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            // Process attachments based on file extensions
            if ($file_extensions) {
                $file_extensions_array = explode(",", $file_extensions);
                $allowed_extensions = array_map('trim', $file_extensions_array);

                if (in_array($extension, $allowed_extensions)) {
                    if ($overrite_existing_images === 'false') {
                        $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                        if (empty($alt_text)) {
                            $alt_text_attachments[] = array(
                                'id' => $attachment->ID,
                                'url' => $attachment->guid
                            );
                        }
                    } else {
                        $alt_text_attachments[] = array(
                            'id' => $attachment->ID,
                            'url' => $attachment->guid,
                        );
                    }
                }
            } else {
                if ($overrite_existing_images === 'false') {
                    $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                    if (empty($alt_text)) {
                        $alt_text_attachments[] = array(
                            'id' => $attachment->ID,
                            'url' => $attachment->guid,
                        );
                    }
                } else {
                    $alt_text_attachments[] = array(
                        'id' => $attachment->ID,
                        'url' => $attachment->guid,
                    );
                }
            }
        }

        // Return error if no attachments need processing
        if (count($alt_text_attachments) === 0) {
            wp_send_json_error(array(
                'message' => "You don't have left any missing alt text attachments!",
            ));
        } else {
            // Filter out unnecessary keys before saving to option
            $filtered_attachments = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'url' => $item['url'],
                ];
            }, $alt_text_attachments);

            // Set API request headers

            $body_data['attachments'] = $filtered_attachments;
        }

        $headers = array(
            'token' => $api_key,
        );
        $response = wp_remote_post(BDAIATG_API_URL . '/wp-json/alt-text-generator/v1/process-alt-text-backend', array(
            'headers' => $headers,
            'body' => json_encode($body_data),
        ));

        $data = json_decode($response['body'], true);
        $status = $data['data']['status'];
        $message = $data['data']['message'];

        if (!$status) {
            wp_send_json_error(array(
                'message' => $message,
            ), 400);
        }
        update_option('bdaiatg_bulk_generating', true);
        // Return success response
        wp_send_json_success(array(
            'status' => $status,
            'message' => $message,
        ), 200);
    }
}

// Initialize the class instance
Boomdevs_Ai_Image_Alt_Text_Bulk_Image_Generator::get_instance();
