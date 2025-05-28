<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-image-generator-update-history.php';

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
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( sanitize_text_field($_POST['nonce']) ), 'import_csv' ) ) {
			die( 'Permission denied!' );
		}

		// Sanitize inputs
		$attachment_id      = isset( $_REQUEST['attachment_id'] ) ? absint( $_REQUEST['attachment_id'] ) : 0;
		$alt_text           = isset( $_REQUEST['alt_text'] ) ? sanitize_text_field( $_REQUEST['alt_text'] ) : '';
		$alt_description_text           = isset( $_REQUEST['generated_description_text'] ) ? sanitize_text_field( $_REQUEST['generated_description_text'] ) : '';
		$image_title        = isset( $_REQUEST['image_title'] ) ? sanitize_text_field( $_REQUEST['image_title'] ) : '';
		$image_caption      = isset( $_REQUEST['image_caption'] ) ? sanitize_text_field( $_REQUEST['image_caption'] ) : '';
		$image_description  = isset( $_REQUEST['image_description'] ) ? sanitize_textarea_field( $_REQUEST['image_description'] ) : '';
		$image_description_enable  = isset( $_REQUEST['bdaiatg_alt_description'] ) ? sanitize_textarea_field( $_REQUEST['bdaiatg_alt_description'] ) : '';
		$focus_keyword = isset( $_REQUEST['focus_keyword'] ) ? sanitize_text_field( $_REQUEST['focus_keyword'] ) : '';

		// Update post title
		if ( 'update_title' === $image_title && ! empty( $alt_text ) ) {
			$post = get_post( $attachment_id );
			if ( $post ) {
				$post->post_title = $alt_text;
				wp_update_post( $post );
			}
		}

		// Update post caption
		if ( 'update_caption' === $image_caption && ! empty( $alt_text ) ) {
			$post = get_post( $attachment_id );
			if ( $post ) {
				$post->post_excerpt = $alt_text;
				wp_update_post( $post );
			}
		}

		if(($image_description_enable == '' || !$image_description_enable) && ($image_description === 'update_description')) {
			$post = get_post( $attachment_id );
			if ( $post ) {
				$post->post_content = $alt_text;
				wp_update_post( $post );
			}
		}

		if($image_description_enable !== '') {
			$post = get_post( $attachment_id );
			if ( $post ) {
				$post->post_content = $alt_description_text;
				wp_update_post( $post );
			}
		}


		$args = array(
			'attachment_id' => $attachment_id,
		);

		AltUpdateHistory::store($args);

		// Update alt text
		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}
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
		$image_description_enable  = isset( $settings['bdaiatg_alt_description'] ) ? sanitize_textarea_field( $settings['bdaiatg_alt_description'] ) : '';
		$alt_text_length = isset($settings['bdaiatg_alt_text_length']) ? $settings['bdaiatg_alt_text_length'] : '';
		$focus_keyword = isset($_REQUEST['focus_keyword']) ? $_REQUEST['focus_keyword'] : '';
		$alt_text_description = isset($settings['bdaiatg_alt_description']) ? $settings['bdaiatg_alt_description'] : '';

		$post_id = $_REQUEST['post_id'];
		$keywords = $_REQUEST['keywords'];
		$override_images_status = $_REQUEST['overrite_existing_images'];

		$attachment_urls = [];

		if (!$api_key && !BDAIATG_DEVELOPMENT) {
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

		$post_content = $content;
		$blocks = parse_blocks($post_content);

		// Loop through each block to find image IDs
		foreach ($blocks as $block) {
			if ($block['blockName'] === 'core/image') {
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
		}

		$attachemtns_urls_update_content = $attachment_urls;
		$updated_blocks = $blocks;

		foreach ($attachment_urls as $key => $single_attachment) {
			$data_send = [
				'website_url' => site_url(),
				'file_url' => $single_attachment['url'],
				'language'  => $language,
				'keywords'  => $keywords ? $keywords : [],
				'focus_keyword'  => $focus_keyword,
				'image_suffix'  => $image_suffix,
				'image_prefix'  => $image_prefix,
				'bdaiatg_alt_description' => $image_description_enable,
				'bdaiatg_alt_text_length' => $alt_text_length
			];

			$headers = array(
				'token' => $api_key,
			);

			$url = BDAIATG_API_URL . '/wp-json/alt-text-generator/v1/get-alt-text';
			$arguments = [
				'method' => 'POST',
				'headers' => $headers,
				'body' => wp_json_encode($data_send),
				'sslverify' => false,
			];

			$response = wp_remote_post( $url, $arguments );
			$body = wp_remote_retrieve_body( $response );
			$make_obj = json_decode($body);

			// Update image title if enabled
			if(isset($image_title[0]) && $image_title[0] === 'update_title') {
				$post = get_post($single_attachment['id']);
				$post->post_title = $make_obj->data->generated_text;
				wp_update_post($post);
			}

			// Update image caption if enabled
			if(isset($image_caption[0]) && $image_caption[0] === 'update_caption') {
				$post = get_post($single_attachment['id']);
				$post->post_excerpt =  $make_obj->data->generated_text;
				wp_update_post($post);
			}

			// Update post description if alt_text_description is empty and enabled
			if (($alt_text_description == '' || !$alt_text_description) && ($image_description[0] === 'update_description')) {
				$post = get_post($single_attachment['id']);
				$post->post_content = $make_obj->data->generated_text;
				wp_update_post($post);
			}

			// Update post description with generated_description_text if alt_text_description is set
			if ($alt_text_description !== '') {
				$post = get_post($single_attachment['id']);
				$post->post_content = $make_obj->data->generated_description_text;
				wp_update_post($post);
			}

			// Update attachment meta with new alt text
			update_post_meta( $single_attachment['id'], '_wp_attachment_image_alt', $make_obj->data->generated_text );
			$args = array(
				'attachment_id' => $single_attachment['id'],
			);
	
			AltUpdateHistory::store($args);

			// Update alt text in block content
			foreach ($updated_blocks as &$block) {
				if ($block['blockName'] === 'core/image' && isset($block['attrs']['id']) && $block['attrs']['id'] === $single_attachment['id']) {
					$block['attrs']['alt'] = $make_obj->data->generated_text;

					// Update innerContent to reflect new alt text
					$img_tag = preg_replace(
						'/alt="[^"]*"/',
						'alt="' . esc_attr($make_obj->data->generated_text) . '"',
						$block['innerContent'][0]
					);
					$block['innerContent'][0] = $img_tag;
				}
			}
		}

		// Serialize blocks back to content
		$updated_content = serialize_blocks($updated_blocks);

		// Update post content
		wp_update_post(array(
			'ID' => $post_id,
			'post_content' => $updated_content
		));

		wp_send_json_success(array(
			'message' => count($attachemtns_urls_update_content).' images alt text successfully generated and updated in post content',
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
		$alt_text_length = isset($settings['bdaiatg_alt_text_length']) ? $settings['bdaiatg_alt_text_length'] : '';
		$image_description_enable  = isset( $settings['bdaiatg_alt_description'] ) ? sanitize_textarea_field( $settings['bdaiatg_alt_description'] ) : '';
		$focus_keyword = isset($_REQUEST['focus_keyword']) ? $_REQUEST['focus_keyword'] : '';
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
			'focus_keyword'  => $focus_keyword,
			'image_prefix'  => $image_prefix,
			'bdaiatg_alt_description' => $image_description_enable,
			'bdaiatg_alt_text_length' => $alt_text_length
		];

		$headers = array(
			'token' => $api_key,
		);

		$url = BDAIATG_API_URL . '/wp-json/alt-text-generator/v1/get-alt-text';
		$arguments = [
			'method' => 'POST',
			'headers' => $headers,
			'body' => wp_json_encode($data_send),
			'sslverify' => false,
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
			wp_update_post($post);
		}

		if(($image_description_enable == '' || !$image_description_enable) && (isset($image_description[0]) && $image_description[0] === 'update_description')) {
			$post = get_post($attachment_id);
			$post->post_content = $make_obj->data->generated_text;
			wp_update_post($post);
		}

		if($image_description_enable !== '' && $image_description_enable !== '0') {
			$post = get_post($attachment_id);
			$post->post_content = $make_obj->data->generated_description_text;
			wp_update_post($post);
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $make_obj->data->generated_text );

		// Add history tracking
//        $history_alt_image_url = $attachment;
//        $history_alt_text = $make_obj->data->generated_text;
		$args = array(
			'attachment_id' => $attachment_id,
//            'image_url' => $history_alt_image_url,
//            'gen_text' => $history_alt_text,
		);

		AltUpdateHistory::store($args);


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
			$alt_text_length = isset($settings['bdaiatg_alt_text_length']) ? $settings['bdaiatg_alt_text_length'] : '';
			$image_description_enable  = isset( $settings['bdaiatg_alt_description'] ) ? sanitize_textarea_field( $settings['bdaiatg_alt_description'] ) : '';

			$attachment_url = wp_get_attachment_url($attachment_id);

			$data_send = [
				'website_url' => site_url(),
				'file_url' => $attachment_url,
				'language'  => $language,
				'keywords'  => [],
				'focus_keyword' => '',
				'image_suffix'  => $image_suffix,
				'image_prefix'  => $image_prefix,
				'bdaiatg_alt_text_length' => $alt_text_length
			];

			$headers = array(
				//            'Content-Type' => 'application/json',
				'token' => $api_key,
			);

			$url = BDAIATG_API_URL . '/wp-json/alt-text-generator/v1/get-alt-text';
			$arguments = [
				'method' => 'POST',
				'headers' => $headers,
				'body' => wp_json_encode($data_send),
				'sslverify'   => false,
			];

			$response = wp_remote_post( $url, $arguments );
			$body = wp_remote_retrieve_body( $response );
			$make_obj = json_decode($body);

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

			// if($image_description_enable !== '' && $image_description_enable !== '0') {
			// 	$post = get_post($attachment_id);
			// 	$post->post_content = $make_obj->data->generated_description_text;
			// 	wp_update_post($post);
			// }

			$args = array(
				'attachment_id' => $attachment_id,
			);

			AltUpdateHistory::store($args);

			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $make_obj->data->generated_text );
		}
	}
}

// Initialize the class instance
Boomdevs_Ai_Image_Alt_Text_Generator_Text::get_instance();