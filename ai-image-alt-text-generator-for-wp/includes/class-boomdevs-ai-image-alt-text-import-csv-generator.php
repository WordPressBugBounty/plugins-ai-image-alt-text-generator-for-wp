<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/vendor/autoload.php';
require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-import-csv-request.php';

class Boomdevs_Ai_Image_Alt_Text_Import_Csv_Generator {
    protected static $instance;
    protected $process_generate_bulk_post;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        $this->process_generate_bulk_post = new BDAIATG_Ai_Image_Alt_Text_Import_Csv_Request();
        add_action("wp_ajax_import_csv", [$this, 'import_csv']);
        add_action("wp_ajax_nopriv_import_csv", [$this, 'import_csv']);
    }

    public function import_csv() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'import_csv' ) ) {
            die( 'Permission denied!' );
        }

        $data = $_REQUEST['result'];

        $this->background_process($data);
    }

    public function background_process($data) {
        foreach ($data as $single_data) {
            $this->process_generate_bulk_post->push_to_queue($single_data);
        }
        $this->process_generate_bulk_post->save()->dispatch();
    }

    public static function import_image_generator($item) {
        if(isset($item[0])) {
            $post = get_post($item[0]);

            if($post) {
                $post->post_content = $item[3];
                $post->post_title = $item[4];
                $post->post_excerpt = $item[5];
                wp_update_post($post);

                update_post_meta(intval($post->ID), '_wp_attachment_image_alt', $item[2]);
            }
        }
        error_log(json_encode($post));
    }
}

// Initialize the class instance
Boomdevs_Ai_Image_Alt_Text_Import_Csv_Generator::get_instance();