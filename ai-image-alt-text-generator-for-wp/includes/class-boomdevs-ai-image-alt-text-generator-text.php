<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';

class Boomdevs_Ai_Image_Alt_Text_Generator_Text {

    protected static $instance;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        // Hooks into WordPress actions to perform tasks
        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

        if(isset($settings['bdaiatg_alt_text_image_generator']['bdaiatg_alt_text_image_generator_enable'][0]) && $settings['bdaiatg_alt_text_image_generator']['bdaiatg_alt_text_image_generator_enable'][0] === 'enable') {
            add_action('add_attachment', array($this, 'boomdevs_update_alt_text_on_upload'));
        }

        add_action("wp_ajax_bdaiatg_save_alt_text", [$this, 'bdaiatg_save_alt_text']);
        add_action("wp_ajax_nopriv_bdaiatg_save_alt_text", [$this, 'bdaiatg_save_alt_text']);

        add_action("wp_ajax_bulk_alt_image_generator_gutenburg_post", [$this, 'bulk_alt_image_generator_gutenburg_post']);
        add_action("wp_ajax_nopriv_bulk_alt_image_generator_gutenburg_post", [$this, 'bulk_alt_image_generator_gutenburg_post']);

        add_action("wp_ajax_bulk_alt_image_generator_gutenburg_block", [$this, 'bulk_alt_image_generator_gutenburg_block']);
        add_action("wp_ajax_nopriv_bulk_alt_image_generator_gutenburg_block", [$this, 'bulk_alt_image_generator_gutenburg_block']);
    }

    public function bdaiatg_save_alt_text() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'import_csv' ) ) {
            die( 'Permission denied!' );
        }

        $attachment_id = $_REQUEST['attachment_id'];
        $alt_text = $_REQUEST['alt_text'];
        $image_title = $_REQUEST['image_title'];
        $image_caption = $_REQUEST['image_caption'];
        $image_description = $_REQUEST['image_description'];

        if(isset($image_title) && $image_title === 'update_title') {
            $post = get_post($attachment_id);
            $post->post_title = $alt_text;
            wp_update_post($post);
        }

        if(isset($image_caption) && $image_caption === 'update_caption') {
            $post = get_post($attachment_id);
            $post->post_excerpt = $alt_text;
            wp_update_post($post);
        }

        if(isset($image_description) && $image_description === 'update_description') {
            $post = get_post($attachment_id);
            $post->post_content = $alt_text;
            wp_update_post($post);
        }

        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
    }

    public function bulk_alt_image_generator_gutenburg_post() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'import_csv' ) ) {
            die( 'Permission denied!' );
        }

        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';

        $post_id = $_REQUEST['post_id'];
        $keywords = $_REQUEST['keywords'];
        $override_images_status = $_REQUEST['overrite_existing_images'];

        $attachment_urls = [];

        if (!$api_key) {
            wp_send_json_error(array(
                'status' => 'error',
                'redirect' => true,
                'redirect_url' => admin_url('/admin.php?page=boomdevs-ai-image-alt-text-generator-settings')
            ));
            exit;
        }

	    // Check if post exists
	    $post = get_post($post_id);
	    if ($post === null) {
		    wp_send_json_error(array(
			    'status' => 'error',
                'redirect' => false,
			    'message' => __('Post not found.', 'ai-image-alt-text-generator-for-wp')
		    ));
		    return false;
	    }

	    $content = $post->post_content;

	    // Check if content is empty
	    if (empty($content)) {
		    wp_send_json_error(array(
			    'status' => 'error',
                'redirect' => false,
			    'message' => __('Post content not found.', 'ai-image-alt-text-generator-for-wp')
		    ));
		    return true;
	    }

	    // Check if there are any images
	    if (!str_contains($content, '<img')) {
		    wp_send_json_error(array(
			    'status' => 'error',
                'redirect' => false,
			    'message' => __('Image not found inside post content.', 'ai-image-alt-text-generator-for-wp')
		    ));
		    return true;
	    }

        // Replace $post_id with the ID of your post/page
        $post_content = $content;
        $blocks = parse_blocks($post_content);

        // Loop through each block to find image IDs
        foreach ($blocks as $block) {
            if ($block['blockName'] === 'core/image') {
                // Check if the block has an image
                if (isset($block['attrs']['id'])) {
                    $attachments_url = wp_get_attachment_image_url($block['attrs']['id'], 'thumbnail');
                    $has_alt_text = get_post_meta($block['attrs']['id'], '_wp_attachment_image_alt', true);

                    if($override_images_status !== 'true') {
                        if ($has_alt_text) {
                            continue;
                        }
                    }

                    $attachment_urls[] = array(
                        'url' => $attachments_url,
                        'id' => $block['attrs']['id'],
                    );
                }
            }
        }

		if(count($attachment_urls) === 0) {
			wp_send_json_error(array(
				'status' => 'error',
                'redirect' => false,
				'message' => 'All image has alt text if you want to override please select Overwrite existing alt text.',
			));

			die();
		}

		$attachemtns_urls_update_content = $attachment_urls;

        foreach ($attachment_urls as $key => $single_attachment) {

            $data_send = [
                'website_url' => site_url(),
                'file_url' => $single_attachment['url'],
                'language'  => $language,
                'keywords'  => $keywords ? $keywords : [],
                'image_suffix'  => $image_suffix,
                'image_prefix'  => $image_prefix
            ];

            $headers = array(
                'token' => $api_key,
            );

            $url = 'https://aialttext.boomdevs.com/wp-json/alt-text-generator/v1/get-alt-text';
            $arguments = [
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode($data_send),
            ];

            $response = wp_remote_post( $url, $arguments );
            $body = wp_remote_retrieve_body( $response );
            $make_obj = json_decode($body);

            if(isset($image_title[0]) && $image_title[0] === 'update_title') {
                $post = get_post($single_attachment['id']);
	            $post->post_title = $make_obj->data->generated_text;
                wp_update_post($post);
            }

            if(isset($image_caption[0]) && $image_caption[0] === 'update_caption') {
                $post = get_post($single_attachment['id']);
	            $post->post_excerpt =  $make_obj->data->generated_text;
                wp_update_post($post);
            }

            if(isset($image_description[0]) && $image_description[0] === 'update_description') {
                $post = get_post($single_attachment['id']);
	            $post->post_content =  $make_obj->data->generated_text;
                wp_update_post($post);
            }

            update_post_meta( $single_attachment['id'], '_wp_attachment_image_alt', $make_obj->data->generated_text );
        }

	    if (version_compare(get_bloginfo('version'), '6.2') >= 0) {
		    $tags = new WP_HTML_Tag_Processor($post_content);

		    foreach ($attachemtns_urls_update_content as $key => $single_attachment) {
				if ($tags->next_tag('img')) {
				    if ($single_attachment['id']) {
					    // Get the attachment ID for the current image
					    $attachment_id = $single_attachment['id'];

					    // Get the alt text for the current image
					    $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

					    // Set the alt text only if it's not empty
					    if (!empty($alt_text)) {
						    $tags->set_attribute('alt', $alt_text);

						    $updated_content = $tags->get_updated_html();

						    if (!empty($updated_content)) {
							    $post->post_content = $updated_content;
							    wp_update_post($post);
						    }
					    }
				    }
				}
		    }
	    }

	    wp_send_json_success(array(
            'message' => count($attachemtns_urls_update_content).' images alt text successfully generated',
        ));
    }

    public function bulk_alt_image_generator_gutenburg_block() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'import_csv' ) ) {
            die( 'Permission denied!' );
        }

        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();
        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';

        $attachment = $_REQUEST['attachment'];
        $post_id = $_REQUEST['post_id'];
        $attachment_id = $_REQUEST['attachment_id'];
        $keywords = $_REQUEST['keywords'];
        $overrite_image = $_REQUEST['overrite_existing_image'];

        $generated_alt = true;

	    if(isset($settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types']) && $settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types'] !== '') {
		    $image_types = array_map('trim', explode(',', $settings['bdaiatg_alt_text_image_types_wrapper']['bdaiatg_alt_text_image_types']));

		    $path_info = pathinfo($attachment);
		    $extension = $path_info['extension'];

		    if(!in_array($extension, $image_types)) {
			    $generated_alt = false;
		    }
	    }

		if(!$generated_alt){
			wp_send_json_error(array(
				'status' => 'error',
				'message' => 'Your image extension is not match to your given types.'
			));
			exit;
		}

        if (!$api_key) {
            wp_send_json_error(array(
                'status' => 'error',
                'redirect_url' => admin_url('/admin.php?page=boomdevs-ai-image-alt-text-generator-settings')
            ));
            exit;
        }

        $has_alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if($overrite_image !== 'true') {
            if ($has_alt_text) {
                return true;
            }
        }

        $data_send = [
            'website_url' => site_url(),
            'file_url' => $attachment,
            'language'  => $language,
            'keywords'  => $keywords ? $keywords : [],
            'image_suffix'  => $image_suffix,
            'image_prefix'  => $image_prefix
        ];

        $headers = array(
            'token' => $api_key,
        );

        $url = 'https://aialttext.boomdevs.com/wp-json/alt-text-generator/v1/get-alt-text';
        $arguments = [
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data_send),
        ];

        $response = wp_remote_post( $url, $arguments );
        $body = wp_remote_retrieve_body( $response );
        $make_obj = json_decode($body);

        if(isset($image_title[0]) && $image_title[0] === 'update_title') {
            $post = get_post($attachment_id);
	        $post->post_title = $make_obj->data->generated_text;
//	        $post->post_title = $make_obj['data']['generated_text'];
            wp_update_post($post);
        }

        if(isset($image_caption[0]) && $image_caption[0] === 'update_caption') {
            $post = get_post($attachment_id);
	        $post->post_excerpt = $make_obj->data->generated_text;
//	        $post->post_excerpt = $make_obj['data']['generated_text'];
            wp_update_post($post);
        }

        if(isset($image_description[0]) && $image_description[0] === 'update_description') {
            $post = get_post($attachment_id);
            $post->post_content = $make_obj->data->generated_text;
//            $post->post_content = $make_obj['data']['generated_text'];
            wp_update_post($post);
        }

        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $make_obj->data->generated_text );

        wp_send_json_success(array(
            'message' => 'Successfully generated alt text for this image',
            'text' => $make_obj->data->generated_text
        ));
    }

    /**
     * Update alt text when an image is uploaded.
     *
     * @param int $attachment_id Attachment ID.
     */
    public function boomdevs_update_alt_text_on_upload($attachment_id) {
        $attachment_type = get_post_mime_type($attachment_id);

        if(strpos($attachment_type, 'image/') === 0) {
            $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

            $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
            $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
            $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
            $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
            $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
            $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
            $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';

            $attachment_url = wp_get_attachment_url($attachment_id);

            $data_send = [
                'website_url' => site_url(),
                'file_url' => $attachment_url,
                'language'  => $language,
                'keywords'  => [],
                'image_suffix'  => $image_suffix,
                'image_prefix'  => $image_prefix
            ];

            $headers = array(
    //            'Content-Type' => 'application/json',
                'token' => $api_key,
            );

            $url = 'https://aialttext.boomdevs.com/wp-json/alt-text-generator/v1/get-alt-text';
            $arguments = [
                'method' => 'POST',
                'headers' => $headers,
                'body' => json_encode($data_send),
            ];

            $response = wp_remote_post( $url, $arguments );
            $body = wp_remote_retrieve_body( $response );
            $make_obj = json_decode($body);
//            $make_obj->data->generated_text

//            $make_obj = array (
//                'success' => true,
//                'data'  => array (
//                    'generated_text' => 'Overwrite Alt Text 13'
//                ),
//            );
//            $make_obj['data']['generated_text']

            if(isset($image_title[0]) && $image_title[0] === 'update_title') {
                $post = get_post($attachment_id);
                $post->post_title = $make_obj->data->generated_text;
                wp_update_post($post);
            }

            if(isset($image_caption[0]) && $image_caption[0] === 'update_caption') {
                $post = get_post($attachment_id);
                $post->post_excerpt = $make_obj->data->generated_text;
                wp_update_post($post);
            }

            if(isset($image_description[0]) && $image_description[0] === 'update_description') {
                $post = get_post($attachment_id);
                $post->post_content = $make_obj->data->generated_text;
                wp_update_post($post);
            }

            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $make_obj->data->generated_text );
        }
    }
}

// Initialize the class instance
Boomdevs_Ai_Image_Alt_Text_Generator_Text::get_instance();