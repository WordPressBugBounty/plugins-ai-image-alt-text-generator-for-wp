<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/vendor/autoload.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-request.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';

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

	public function check_no_credit() {
		$no_credit = get_option('error_during_background_task_no_credit');
		if($no_credit) {
			wp_send_json_error(array(
				'message' => 'You have not enough credit to generate image alt text please buy credit from here <a href="https://aialttextgenerator.com/pricing">Buy now</a>',
			));
		}
	}

    public function verify_authorization() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce(  $_POST['nonce'], 'import_csv' ) ) {
            wp_send_json_error( array(
                'message' => 'Permission denied!',
            ) );
            return false;
        }
    }

	public function get_total_jobs_lists() {
		$this->verify_authorization();

		$all_jobs = get_option('altgen_attachments_jobs');
        wp_send_json_success( array(
            'data' => is_array($all_jobs) ? count($all_jobs) : 0,
        ), 200 );
	}

    public function cancel_bulk_alt_image_generator() {
        $this->verify_authorization();

        $this->process_generate_bulk_post->cancel();
        delete_option('altgen_attachments_jobs');
    }

	public function check_available_token($api_key) {
		$url = 'https://aialttextgenerator.com/wp-json/alt-text-generator/v1/available-token';
		$body_data = array(
			'token' => $api_key,
		);

		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode($body_data),
		);

		$response = wp_remote_post($url, $args);
		if (is_wp_error($response)) {
			wp_send_json_error(array(
				'message' => "Something went wrong try again letter.",
			));
			return false;
		} else {
			$response_code = wp_remote_retrieve_response_code($response);
			$response_body = wp_remote_retrieve_body($response);
			return json_decode($response_body);
		}
	}

    public function bulk_alt_image_generator()
    {
        $this->verify_authorization();

        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();
        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';

        if(!$api_key) {
            wp_send_json_error(array(
                'message' => "Set api key in ai alt text generator settings",
            ));
	        return false;
        }

		$check_available_credit = $this->check_available_token($api_key);
		if(!$check_available_credit->data->status) {
			wp_send_json_error(array(
				'message' => "You don't have sufficient credits buy more and try again.",
			));
			return false;
		}

        $file_extensions = isset($settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types']) ? $settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types'] : '';
        $overrite_existing_images = isset($_REQUEST['overrite_existing_images']) ? $_REQUEST['overrite_existing_images']: false;

        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'post_mime_type' => 'image',
        );

        $attachments = get_posts($args);
        $alt_text_attachments = array();

        foreach ($attachments as $attachment) {
            $path = wp_parse_url($attachment->guid, PHP_URL_PATH);
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if($file_extensions) {
                $file_extensions_array = explode(",", $file_extensions);
                $allowed_extensions = array_map('trim', $file_extensions_array);

                if (in_array($extension, $allowed_extensions)) {
                    if ($overrite_existing_images === 'false') {
                        $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                        if (empty($alt_text)) {
                            $alt_text_attachments[] = array(
                                'status' => false,
                                'id' => $attachment->ID,
                                'url' => $attachment->guid,
                            );
                        }
                    } else {
                        $alt_text_attachments[] = array(
                            'status' => false,
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
                            'status' => false,
                            'id' => $attachment->ID,
                            'url' => $attachment->guid,
                        );
                    }
                } else {
                    $alt_text_attachments[] = array(
                        'status' => false,
                        'id' => $attachment->ID,
                        'url' => $attachment->guid,
                    );
                }
            }
        }

		$job_added = '';

        if(count($alt_text_attachments) === 0) {
            wp_send_json_error(array(
                'message' => "You don't have left any missing alt text attachments!",
            ));
        } else {
	        $job_added = update_option('altgen_attachments_jobs', $alt_text_attachments);
            $this->background_process($alt_text_attachments);
        }

	    wp_send_json_success( array(
		    'data' => $job_added,
	    ), 200 );
    }

    public function background_process($data)
    {
        foreach ($data as $single_data) {
            $this->process_generate_bulk_post->push_to_queue($single_data);
        }
        $this->process_generate_bulk_post->save()->dispatch();
    }

    public static function bulk_image_generator($item)
    {
        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';

        $data_send = [
            'website_url' => site_url(),
            'file_url' => $item['url'],
            'language'  => $language,
            'keywords'  => [],
            'image_suffix'  => $image_suffix,
            'image_prefix'  => $image_prefix
        ];

        $headers = array(
//            'Content-Type' => 'application/json',
            'token' => $api_key,
        );

        $url = 'https://aialttextgenerator.com/wp-json/alt-text-generator/v1/get-alt-text';
        $arguments = [
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode($data_send),
        ];

        $response = wp_remote_post( $url, $arguments );
        $body = wp_remote_retrieve_body( $response );
        $make_obj = json_decode($body);

//        $make_obj->data->generated_text

//        $make_obj = array (
//            'success' => true,
//            'data'  => array (
//                'generated_text' => 'Overwrite Alt Text 45'
//            ),
//        );

//        $make_obj['data']['generated_text']

		if($make_obj->success === false) {
			$cancel_process = new self();
			$cancel_process->process_generate_bulk_post->cancel();
			delete_option('altgen_attachments_jobs');
			update_option('error_during_background_task_no_credit', true);
		} else {
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

			if(isset($image_description[0]) && $image_description[0] === 'update_description') {
				$post = get_post($item['id']);
				$post->post_content = $make_obj->data->generated_text;
				wp_update_post($post);
			}

			update_post_meta( $item['id'], '_wp_attachment_image_alt', $make_obj->data->generated_text );

			$get_altgen_jobs = get_option('altgen_attachments_jobs', true);

			if ($get_altgen_jobs) {
				$id_to_update = $item['id'];
				$new_status = true;

				self::updateItemById($get_altgen_jobs, $id_to_update, $new_status);
				update_option('altgen_attachments_jobs', $get_altgen_jobs);
			}
		}
    }

    public static function updateItemById(&$array, $id, $new_status)
    {
        foreach ($array as &$item) {
            if (($item['id'] === $id) && ($item['status'] !== true)) {
                $item['status'] = $new_status;
            }
        }
    }
}

// Initialize the class instance
Boomdevs_Ai_Image_Alt_Text_Bulk_Image_Generator::get_instance();