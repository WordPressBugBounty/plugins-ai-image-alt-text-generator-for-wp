<?php

require __DIR__ . '/vendor/autoload.php';

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wpmessiah.com/
 * @since             1.0.0
 * @package           Boomdevs_Ai_Image_Alt_Text_Generator
 *
 * @wordpress-plugin
 * Plugin Name:       Ai Image Alt Text Generator for WP
 * Plugin URI:        https://aialttextgenerator.com/
 * Description:       Effortlessly generate descriptive alt text for images using AI within your WordPress website.
 * Version:           1.1.6
 * Author:            WP Messiah
 * Author URI:        https://wpmessiah.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ai-image-alt-text-generator-for-wp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */


define('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_VERSION', '1.1.6');
define('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_PATH', plugin_dir_path(__FILE__));
define('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_URL', plugin_dir_url(__FILE__));
define('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_NAME', 'ai-image-alt-text-generator-for-wp');
define('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_FULL_NAME', 'Ai Image Alt Text Generator for WP');
define('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_BASE_NAME', plugin_basename(__FILE__));
define('BDAIATG_DB_ASSET_TABLE', 'bdaiatg_assets');
define('BDAIATG_API_URL', 'https://aialttextgenerator.com');

define('BDAIATG_AI_IMAGE_ALTTEXT_BACKEND_URL', 'https://wpmessiah.com/wp-json/notification-api/v1/get');

// Development mode
define('BDAIATG_DEVELOPMENT', false);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-boomdevs-ai-image-alt-text-generator-activator.php
 */
function boomdevs_ai_image_alt_text_generator_activate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-boomdevs-ai-image-alt-text-generator-activator.php';
	Boomdevs_Ai_Image_Alt_Text_Generator_Activator::activate();
}

/**
 * The code that runs during plugin update.
 * This action is documented in includes/class-boomdevs-ai-image-alt-text-generator-activator.php
 */

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-boomdevs-ai-image-alt-text-generator-deactivator.php
 */
function boomdevs_ai_image_alt_text_generator_deactivate()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-boomdevs-ai-image-alt-text-generator-deactivator.php';
	Boomdevs_Ai_Image_Alt_Text_Generator_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'boomdevs_ai_image_alt_text_generator_activate');
register_deactivation_hook(__FILE__, 'boomdevs_ai_image_alt_text_generator_deactivate');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-boomdevs-ai-image-alt-text-generator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function boomdevs_ai_image_alt_text_generator_run()
{
	$plugin = new Boomdevs_Ai_Image_Alt_Text_Generator();
	$plugin->run();
}

// Use a higher priority to ensure all dependencies are loaded.
add_action('plugins_loaded', 'boomdevs_ai_image_alt_text_generator_run', 2);



/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function appsero_init_tracker_ai_image_alt_text_generator_for_wp()
{

	if (! class_exists('Appsero\Client')) {
		require_once __DIR__ . '/appsero/src/Client.php';
	}

	$client = new Appsero\Client('c4a40d12-68e8-4f57-984c-bc744c2e45d0', 'Ai Image Alt Text Generator for WP', __FILE__);

	// Active insights
	$client->insights()->init();
}

add_action('init', 'appsero_init_tracker_ai_image_alt_text_generator_for_wp');


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

if (! function_exists('validate_api_key')) {
	function validate_api_key($value)
	{
		if (!current_user_can('manage_options')) {
			return esc_html__('Permission denied!', 'ai-image-alt-text-generator-for-wp');
		}

		$api_key = $value;
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

		$response_body = wp_remote_retrieve_body($response);

		$decoded_response = json_decode($response_body);

		if (!$decoded_response->data->available_token) {
			return esc_html__('This api key is not valid!', 'ai-image-alt-text-generator-for-wp');
		}
	}
}
