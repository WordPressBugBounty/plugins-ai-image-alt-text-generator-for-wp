<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wpmessiah.com
 * @since      1.0.0
 *
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/includes
 * @author     BoomDevs <contact@boomdevs.com>
 */
class Boomdevs_Ai_Image_Alt_Text_Generator
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Boomdevs_Ai_Image_Alt_Text_Generator_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_VERSION')) {
            $this->version = BDAIATG_AI_IMAGE_ALT_TEXT_GENERATOR_VERSION;
        } else {
            $this->version = '1.1.6';
        }
        $this->plugin_name = 'ai-image-alt-text-generator-for-wp';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_settings();

        $this->register_rest_api();
        $this->update_image_alt_text();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Boomdevs_Ai_Image_Alt_Text_Generator_Loader. Orchestrates the hooks of the plugin.
     * - Boomdevs_Ai_Image_Alt_Text_Generator_i18n. Defines internationalization functionality.
     * - Boomdevs_Ai_Image_Alt_Text_Generator_Admin. Defines all hooks for the admin area.
     * - Boomdevs_Ai_Image_Alt_Text_Generator_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-generator-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-generator-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-boomdevs-ai-image-alt-text-generator-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-boomdevs-ai-image-alt-text-generator-public.php';

        /**
         * The class responsible for loading codestar framework of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'libs/codestar-framework/codestar-framework.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-notification-widget-ai-image-alttext.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-generator-settings.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-generator-text.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-bulk-image-generator.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-rest-api.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-generator-custom-menu.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-generator-gutenburg.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-boomdevs-ai-image-alt-text-image-generator-update-history.php';

        $this->loader = new Boomdevs_Ai_Image_Alt_Text_Generator_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Boomdevs_Ai_Image_Alt_Text_Generator_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Boomdevs_Ai_Image_Alt_Text_Generator_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Boomdevs_Ai_Image_Alt_Text_Generator_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Boomdevs_Ai_Image_Alt_Text_Generator_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    public function register_rest_api()
    {
        new BDAIATG_Ai_Image_Alt_Text_Generator_Rest_Api();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Boomdevs_Ai_Image_Alt_Text_Generator_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Register plugin settings.
     *
     * @access   private
     */
    private function register_settings()
    {
        $plugin_settings = new BDAIATG_Boomdevs_Ai_Image_Alt_Text_Generator_Settings();
        $plugin_settings->generate_settings();
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

    public function update_image_alt_text()
    {
        return Boomdevs_Ai_Image_Alt_Text_Generator_Image_Update_History::run();
    }

}
