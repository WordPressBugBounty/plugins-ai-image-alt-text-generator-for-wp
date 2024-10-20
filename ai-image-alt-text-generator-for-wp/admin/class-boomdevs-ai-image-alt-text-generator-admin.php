<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://boomdevs.com
 * @since      1.0.0
 *
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/admin
 */

require_once(__DIR__ . '/../includes/class-boomdevs-ai-image-alt-text-generator-settings.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/admin
 * @author     BoomDevs <contact@boomdevs.com>
 */
class Boomdevs_Ai_Image_Alt_Text_Generator_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Boomdevs_Ai_Image_Alt_Text_Generator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Boomdevs_Ai_Image_Alt_Text_Generator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name . 'toast-css', plugin_dir_url( __FILE__ ) . 'css/jquery.toast.min.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/boomdevs-ai-image-alt-text-generator-admin.css', array(), time(), 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Boomdevs_Ai_Image_Alt_Text_Generator_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Boomdevs_Ai_Image_Alt_Text_Generator_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        $settings = BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings::get_settings();

        $bulk_alt_text_options = get_option('bulk_alt_text_processing');
        if(isset($bulk_alt_text_options)) {
            $bulk_alt_text_processing = $bulk_alt_text_options;
        }

        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';

        $nonce = wp_create_nonce('import_csv');
        $has_jobs_list = get_option('altgen_attachments_jobs');

        if (!$has_jobs_list) {
            $has_jobs_list = 0;
        }

		wp_enqueue_script( $this->plugin_name . '-toast-notify', plugin_dir_url( __FILE__ ) . 'js/jquery.toast.min.js', array( 'jquery' ), time(), true );
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/boomdevs-ai-image-alt-text-generator-admin.js', array( 'jquery' ), time(), true );
        wp_enqueue_script( $this->plugin_name . 'edit-media', plugin_dir_url( __FILE__ ) . 'js/boomdevs-ai-image-alt-text-generator-edit-media.js', array( 'jquery' ), time(), true );

        wp_localize_script(
            $this->plugin_name . 'edit-media',
            'import_csv',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'  => $nonce,
                'icon_button_generate' => plugin_dir_url( __FILE__ ) . '/img/flash.svg',
                'site_url'  => site_url(),
                'settings'  => $settings,
                'api_key'   => $api_key,
                'language'   => $language,
                'image_title'   => $image_title,
                'image_caption'   => $image_caption,
                'image_description'   => $image_description,
                'image_suffix'   => $image_suffix,
                'image_prefix'   => $image_prefix,
                'bulk_alt_text_processing' => $bulk_alt_text_processing,
                'has_jobs_list' => $has_jobs_list,
            )
        );
	}
}


/**
 * Enqueue specific modifications for the block editor.
 *
 * @return void
 */
function wpdev_enqueue_editor_modifications() {
    $asset_file = include plugin_dir_path( __FILE__ ) . '../bdalt-text-gen-block/build/index.asset.php';
    wp_enqueue_script( 'bdaitgen-override-core-img', plugin_dir_url( __FILE__ ) . '../bdalt-text-gen-block/build/index.js', $asset_file['dependencies'], $asset_file['version'], true );
}
add_action( 'enqueue_block_editor_assets', 'wpdev_enqueue_editor_modifications' );
