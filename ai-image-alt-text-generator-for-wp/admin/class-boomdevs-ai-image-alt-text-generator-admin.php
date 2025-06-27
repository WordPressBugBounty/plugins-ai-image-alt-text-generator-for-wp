<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpmessiah.com
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
class Boomdevs_Ai_Image_Alt_Text_Generator_Admin
{

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
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('wp_ajax_get_focus_keyword', array($this, 'ajax_get_focus_keyword'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

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

        wp_enqueue_style($this->plugin_name . 'toast-css', plugin_dir_url(__FILE__) . 'css/jquery.toast.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/boomdevs-ai-image-alt-text-generator-admin.css', array(), time(), 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */

    public function enqueue_scripts()
    {

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
        if (isset($bulk_alt_text_options)) {
            $bulk_alt_text_processing = $bulk_alt_text_options;
        }

        $api_key = isset($settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key']) ? $settings['bdaiatg_api_key_wrapper']['bdaiatg_api_key'] : '';
        $language = isset($settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language']) ? $settings['bdaiatg_alt_text_language_wrapper']['bdaiatg_alt_text_language'] : '';
        $image_title = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_title'] : '';
        $image_caption = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_caption'] : '';
        $image_description = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_image_description'] : '';
        $image_suffix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_suffix'] : '';
        $image_prefix = isset($settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix']) ? $settings['bdaiatg_alt_text_image_wrapper']['bdaiatg_alt_text_prefix'] : '';
        $alt_text_length = isset($settings['bdaiatg_alt_text_length']) ? $settings['bdaiatg_alt_text_length'] : '';
        $alt_text_description = isset($settings['bdaiatg_alt_description']) ? $settings['bdaiatg_alt_description'] : '';

        $nonce = wp_create_nonce('import_csv');
        $has_jobs_list = get_option('altgen_attachments_jobs');

        if (!$has_jobs_list) {
            $has_jobs_list = 0;
        }

        global $wpdb;
        $focus_keyword = '';

        // Check for item parameter (attachment ID) first
        $item_id = isset($_GET['item']) ? intval($_GET['item']) : 0;

        if ($item_id) {
            // Look for posts containing the image ID in post content
            $all_posts_query = new WP_Query(array(
                'post_type' => 'any',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
            ));

            $needle = 'wp-image-' . $item_id;

            foreach ($all_posts_query->posts as $related_post_id) {
                $post_content = get_post_field('post_content', $related_post_id, 'raw');

                if (strpos($post_content, $needle) !== false) {
                    // Check for Rank Math
                    if (defined('RANK_MATH_VERSION')) {
                        $focus_keyword = get_post_meta($related_post_id, 'rank_math_focus_keyword', true);
                    }
                    // Check for Yoast
                    elseif (defined('WPSEO_VERSION')) {
                        $focus_keyword = get_post_meta($related_post_id, '_yoast_wpseo_focuskw', true);
                    }
                    // Check for AIOSEO
                    elseif (defined('AIOSEO_VERSION')) {
                        $table_name = $wpdb->prefix . 'aioseo_posts';
                        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

                        if ($table_exists) {
                            $row = $wpdb->get_row($wpdb->prepare(
                                "SELECT keyphrases FROM $table_name WHERE post_id = %d",
                                $related_post_id
                            ));

                            if ($row && !empty($row->keyphrases)) {
                                $keyphrases = json_decode($row->keyphrases, true);
                                if (isset($keyphrases['focus']['keyphrase'])) {
                                    $focus_keyword = $keyphrases['focus']['keyphrase'];
                                }
                            }
                        }
                    }

                    if (!empty($focus_keyword)) {
                        break; // Use the first focus keyword found
                    }
                }
            }
        }
        // If no item or no focus keyword found, check for post parameter
        else {
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;

            if ($post_id) {
                // Check for Rank Math
                if (defined('RANK_MATH_VERSION')) {
                    $focus_keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
                }
                // Check for Yoast
                elseif (defined('WPSEO_VERSION')) {
                    $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
                }
                // Check for AIOSEO (custom table)
                elseif (defined('AIOSEO_VERSION')) {
                    // Try the custom table method first (newer versions)
                    $table_name = $wpdb->prefix . 'aioseo_posts';

                    // Check if table exists
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

                    if ($table_exists) {
                        $row = $wpdb->get_row($wpdb->prepare(
                            "SELECT keyphrases FROM $table_name WHERE post_id = %d",
                            $post_id
                        ));

                        if ($row && !empty($row->keyphrases)) {
                            $keyphrases = json_decode($row->keyphrases, true);
                            if (isset($keyphrases['focus']['keyphrase'])) {
                                $focus_keyword = $keyphrases['focus']['keyphrase'];
                            }
                        }
                    }
                }
            }
        }

        // Process the focus keyword if found
        if (!empty($focus_keyword)) {
            $keywords_array = array_map('trim', explode(',', $focus_keyword));
            $focus_keyword = $keywords_array[0]; // Get the first keyword
        } else {
            $focus_keyword = ''; // Ensure it's an empty string, not null
        }

        // Add debug output to check values
        if (defined('BDAIATG_DEVELOPMENT') && BDAIATG_DEVELOPMENT) {
            error_log('Item ID: ' . $item_id);
            error_log('Focus Keyword: ' . ($focus_keyword ?: 'EMPTY'));
        }

        wp_enqueue_script($this->plugin_name . '-toast-notify', plugin_dir_url(__FILE__) . 'js/jquery.toast.min.js', array('jquery'), time(), true);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/boomdevs-ai-image-alt-text-generator-admin.js', array('jquery'), time(), true);
        wp_enqueue_script($this->plugin_name . 'edit-media', plugin_dir_url(__FILE__) . 'js/boomdevs-ai-image-alt-text-generator-edit-media.js', array('jquery'), time(), true);

        // update_option('bdaiatg_bulk_generating', true);

        $bulk_generating = get_option('bdaiatg_bulk_generating');

        if ($bulk_generating) {
            $bulk_generating = true;
        } else {
            $bulk_generating = false;
        }
        
        wp_localize_script(
            $this->plugin_name . 'edit-media',
            'import_csv',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => $nonce,
                'icon_button_generate' => plugin_dir_url(__FILE__) . '/img/flash.svg',
                'site_url' => site_url(),
                'settings' => $settings,
                'api_key' => $api_key,
                'language' => $language,
                'image_title' => $image_title,
                'image_caption' => $image_caption,
                'image_description' => $image_description,
                'focus_keyword' => $focus_keyword,
                'image_suffix' => $image_suffix,
                'image_prefix' => $image_prefix,
                'alt_length' => $alt_text_length,
                'alt_description' => $alt_text_description,
                'bulk_alt_text_processing' => isset($bulk_alt_text_processing) ? $bulk_alt_text_processing : '',
                'has_jobs_list' => $has_jobs_list,
                'development' => defined('BDAIATG_DEVELOPMENT') ? BDAIATG_DEVELOPMENT : false,
                'api_url' => defined('BDAIATG_API_URL') ? BDAIATG_API_URL : '',
                'current_item_id' => $item_id, // Add the current item ID
                'bulk_generating' => $bulk_generating,
            )
        );

        // Add a direct script to verify the value in the console

    }

    /**
     * Get focus keyword for an item by AJAX
     */
    public function ajax_get_focus_keyword()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'import_csv')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Get item ID
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        if (!$item_id) {
            wp_send_json_error('Invalid item ID');
            return;
        }

        global $wpdb;
        $focus_keyword = '';

        // Look for posts containing the image ID in post content
        $all_posts_query = new WP_Query(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));

        $needle = 'wp-image-' . $item_id;

        foreach ($all_posts_query->posts as $related_post_id) {
            $post_content = get_post_field('post_content', $related_post_id, 'raw');

            if (strpos($post_content, $needle) !== false) {
                $post_focus_keyword = '';

                // Check for Rank Math
                if (defined('RANK_MATH_VERSION')) {
                    $post_focus_keyword = get_post_meta($related_post_id, 'rank_math_focus_keyword', true);
                }
                // Check for Yoast
                elseif (defined('WPSEO_VERSION')) {
                    $post_focus_keyword = get_post_meta($related_post_id, '_yoast_wpseo_focuskw', true);
                }
                // Check for AIOSEO (custom table)
                elseif (defined('AIOSEO_VERSION')) {
                    $table_name = $wpdb->prefix . 'aioseo_posts';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

                    if ($table_exists) {
                        $row = $wpdb->get_row($wpdb->prepare(
                            "SELECT keyphrases FROM $table_name WHERE post_id = %d",
                            $related_post_id
                        ));

                        if ($row && !empty($row->keyphrases)) {
                            $keyphrases = json_decode($row->keyphrases, true);
                            if (isset($keyphrases['focus']['keyphrase'])) {
                                $post_focus_keyword = $keyphrases['focus']['keyphrase'];
                            }
                        }
                    }
                }

                if (!empty($post_focus_keyword)) {
                    $focus_keyword = $post_focus_keyword;
                    break; // Use the first focus keyword found
                }
            }
        }

        // Process the focus keyword if we found one
        if ($focus_keyword) {
            $keywords_array = array_map('trim', explode(',', $focus_keyword));
            $focus_keyword = $keywords_array[0]; // Get the first keyword
        }

        wp_send_json_success(array(
            'focus_keyword' => $focus_keyword,
        ));
    }
}


/**
 * Enqueue specific modifications for the block editor.
 *
 * @return void
 */
function wpdev_enqueue_editor_modifications()
{
    $asset_file = include plugin_dir_path(__FILE__) . '../bdalt-text-gen-block/build/index.asset.php';
    wp_enqueue_script('bdaitgen-override-core-img', plugin_dir_url(__FILE__) . '../bdalt-text-gen-block/build/index.js', $asset_file['dependencies'], $asset_file['version'], true);
}
add_action('enqueue_block_editor_assets', 'wpdev_enqueue_editor_modifications');
