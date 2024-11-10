<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://wpmessiah.com
 * @since      1.0.0
 *
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/includes
 * @author     BoomDevs <contact@boomdevs.com>
 */
class Boomdevs_Ai_Image_Alt_Text_Generator_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ai-image-alt-text-generator-for-wp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}