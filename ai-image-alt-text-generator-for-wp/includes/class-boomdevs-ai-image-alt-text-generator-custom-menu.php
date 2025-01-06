<?php

	if (!defined('ABSPATH')) {
		exit; // Exit if accessed directly
	}

	// Get settings
	require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';
	function boomdevs_alt_text_gen_custom_menu()
	{
		$plugin_logo = BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL . 'admin/img/plugin-logo.svg';

		add_menu_page(
			esc_html('Ai Alt Text Generator', 'ai-image-alt-text-generator-for-wp'),
			'Ai Alt Text Generator',
			'manage_options',
			'ai-alt-text-generator',
			'boomdevs_alt_text_menu_content',
			$plugin_logo,
			59
		);

		add_submenu_page(
			'ai-alt-text-generator', // Parent slug
			esc_html('Settings', 'ai-image-alt-text-generator-for-wp'),
			__('Settings', 'ai-image-alt-text-generator-for-wp'),
			'manage_options',
			'/admin.php?page=boomdevs-ai-image-alt-text-generator-settings',
			''
		);

        add_submenu_page(
			'ai-alt-text-generator', // Parent slug
			esc_html('History', 'ai-image-alt-text-generator-for-wp'),
			__('History', 'ai-image-alt-text-generator-for-wp'),
			'manage_options',
			'/admin.php?page=boomdevs-ai-image-alt-text-generator-history',
			'render_generated_image_alt'
		);
	}

	add_action('admin_menu', 'boomdevs_alt_text_gen_custom_menu');


	/**
	 * Display a custom menu page
	 */
	function boomdevs_alt_text_menu_content()
	{
		$settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();?>
        <div class="boomdevs_ai_img_alt_text_generator_dashboard">
            <div class="baiatgd_cards">
                <div class="baiatgd_single_card">
                    <div class="baiatgd_card_img_wrapper">
                        <img src="<?php echo BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL . 'admin/img/gallery.png' ?>"
                             class="baiatgd_card_img" alt="bulk-generate">
                    </div>
                    <div class="baiatgd_card_content">
                    <span class="content_text">
                        Images in your library
                    </span>
                        <span class="content_number">
                        <?php echo BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$all_images; ?>
                    </span>
                    </div>
                </div>
                <div class="baiatgd_single_card">
                    <div class="baiatgd_card_img_wrapper">
                        <img src="<?php echo BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL . 'admin/img/gallery-remove.png' ?>"
                             class="baiatgd_card_img" alt="bulk-generate">
                    </div>
                    <div class="baiatgd_card_content">
                    <span class="content_text">
                        Images Missing Alt Text
                    </span>
                        <span class="content_number">
                        <?php echo BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$missing_alt_text_count; ?>
                    </span>
                    </div>
                </div>
                <div class="baiatgd_single_card card-3">
                    <div class="baiatgd_single_card_top">
                        <div class="baiatgd_card_img_wrapper">
                            <img src="<?php echo BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL . 'admin/img/database.png' ?>"
                                 class="baiatgd_card_img" alt="bulk-generate">
                        </div>
                        <div class="baiatgd_card_content">
                            <span class="content_text">
                                Plan Credit Usage
                            </span>
                            <span class="content_number">
                                <span id="bdaiatg_available_token_num">0</span>
                                    /
                                    <span id="bdaiatg_token_token_num">0</span>
                                <span class="content_percent">(<span id="bdaiatg_spent_token">0</span>%)</span>
                            </span>
                        </div>
                    </div>
                    <div class="baiatgd_progress_bar_wrapper">
                        <div class="baiatgd_percentage_wrapper">
                        <span id="bdiatgd_percent_start">
                            <span>0%</span>
                        </span>
                            <span>100%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" id="progress" data-color="#4834D4"></div>
                        </div>
                    </div>
                    <?php
                     
                     $api_key = '';

                     if(isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) && $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] !== ''){
                        $api_key = $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'];
                     }

                     $url = 'https://aialttextgenerator.com/wp-json/alt-text-generator/v1/available-token';
             
                     $args = array(
                         'headers' => array(
                            'token' => $api_key,
                         )
                     );
             
                     $response = wp_remote_post($url, $args);
             
                     $response_body = wp_remote_retrieve_body($response);
                     $decoded_response = json_decode($response_body);

                    if((isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) && $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] === '') || !$decoded_response->data->available_token): ?>
                        <div class="overlay_for_plan">
                            You don't have any plan please<a style="margin-left: 5px; display: inline-block; margin-top: 10px" href="https://aialttextgenerator.com/register/" target="_blank"><b>Get Started for Free</b></a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="baiatgd_plan_notice">
            <span class="notice_text">
		        <?php if((isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) && $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] === '') || !$decoded_response->data->available_token): ?>
                    You don't have any plan please<a style="margin-left: 5px; display: inline-block" href="https://aialttextgenerator.com/register/" target="_blank">Get Started for Free</a>.
		        <?php else: ?>
                    You are on the <span id="subscription_plan">Free plan</span> with <span id="remaining_credit">0</span> credits remaining.
                    <a href="https://aialttextgenerator.com/pricing/" target="_blank">Purchase more credits</a>
                    to keep going!
                <?php endif; ?>
            </span>
            </div>
        </div>
        <div class="boomdevs_ai_img_alt_text_generator_bulk">
            <h2 class="baiatgd_bulk_title">Bulk Image Alt Text Generator</h2>
            <div class="baiatgd_bulk_smush_wrapper">
                <div class="baiatgd_bulk_smush_content">
                <span class="baiatgd_bulk_smush_quantity">
                    <?php echo BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$missing_alt_text_count; ?>
                </span>
                    <span class="baiatgd_bulk_smush_text">
                    <?php bdaiatg_user(); ?>, you have <?php echo BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$all_images; ?> images total in your website, and <?php echo BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::$missing_alt_text_count; ?> of them have no alt text attached to them.
                </span>
                </div>
                <div class="baiatgd_bulk_smush_btn_wrapper">
                    <div class="generate_button_wrap">
                        <div class="generate_alt_text_btn_loader"></div>
                        <button type="button" id="generate_alt_text" class="baiatgd_generate_btn">
                            Generate Alt Text
                        </button>
                    </div>
                    <div>
                        <label for="bdaiatg_bulk_generate_all" class="generate_checkbox_wrapper">
                            <input type="checkbox" id="bdaiatg_bulk_generate_all">
                            <span>Include images that already have alt text (overwrite existing alt text).</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="baiatgd_bulk_progress_card">
                <div class="baiatgd_bulk_progress_bar_wrapper">
                    <div class="progress-bar">
                        <div class="progress" id="bulk-progress" data-color="#4834D4"></div>
                    </div>
                    <div class="baiatgd_percentage_wrapper">
                        <span id="bulk_alt_text_progress">0%</span>
                        <div class="baiatgd_percentage_wrapper_cancel">
                            <div class="spinner-icon">
                                <img src="<?php echo BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL . 'admin/img/spinner.gif' ?>"
                                     alt="spinner">
                            </div>
                            <span class="baiatgd_bulk_cancal" id="cancel_bulk_alt_image_generator">Cancel</span>
                        </div>
                    </div>
                </div>
                <div class="baiatgd_bulk_progress_optimized"><span id="attachment_generated_count">0</span>/<span
                            id="total_attachment_count">0</span> images optimized
                </div>
            </div>
        </div>
		<?php

	}

    function render_generated_image_alt() {
        require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-custom-table.php';
        // Create an instance of our package class
        $Books_List_Table = new Books_List_Table();
        // Fetch, prepare, sort, and filter our data
        $Books_List_Table->prepare_items();
    ?>

    <div class="wrap">
        <h2>My Custom List Table</h2>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $Books_List_Table->display() ?>
        </form>
    </div>
    <?php
    }

	function bdaiatg_user()
	{
		$current_user = wp_get_current_user();

		if ($current_user->exists()) {
			$username = $current_user->user_login;
			$capitalized_username = ucfirst($username);
			echo "$capitalized_username";
		} else {
			echo "Guest";
		}
	}