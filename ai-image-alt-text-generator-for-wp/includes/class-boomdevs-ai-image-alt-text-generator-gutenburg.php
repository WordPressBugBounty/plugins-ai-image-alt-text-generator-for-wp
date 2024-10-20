<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-generator-settings.php';

class BDAIATG_Ai_Image_Alt_Text_Generator_Gutenburg {

    protected static $instance;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        // Hooks into WordPress actions to perform tasks
        add_action('add_meta_boxes', [$this, 'gutenburg_alt_text_metabox'] );
    }

    /**
     * Register Metabox
     */
    public function gutenburg_alt_text_metabox(){
        add_meta_box( 'bd_ai_image_alt_text_generate', __( 'AI ALT Text','ai-image-alt-text-generator-for-wp' ), [$this, 'render_button_on_posts'], ['page', 'post'], 'side' );
    }

    /**
     * Meta field callback function
     */
    public function render_button_on_posts() {?>
            <div class="bdai_alt_text_gutenburg_generator">
                <div class="bdaiatg_alt_text_gutenburg_generator_content">
                    <p>Populate alt text using values from your media library images. If missing, alt text will be generated for an image and added to the post.</p>
                    <div class="bdaiatg_alt_text_gutenburg_generator_content_checkbox">
                        <input type="checkbox" id="bdaiatg-generate-button-overwrite-checkbox" data-post-bulk-generate-overwrite="">
                        <label for="bdaiatg-generate-button-overwrite-checkbox">Overwrite existing alt text</label>
                    </div>
<!--                    <div class="bdaiatg_alt_text_gutenburg_generator_content_checkbox">-->
<!--                        <input type="checkbox" id="bdaiatg-generate-button-process-external-checkbox" data-post-bulk-generate-process-external="">-->
<!--                        <label for="bdaiatg-generate-button-process-external-checkbox">Include images not in library</label>-->
<!--                    </div>-->
                    <div class="bdaiatg_alt_text_gutenburg_generator_content_checkbox">
                        <input type="checkbox" id="bdaiatg-generate-button-keywords-checkbox" data-post-bulk-generate-keywords-checkbox="">
                        <label for="bdaiatg-generate-button-keywords-checkbox">Add SEO keywords</label>
                        <input type="text" class="hidden" id="bdaiatg-generate-button-keywords-seo" data-post-bulk-generate-keywords="" placeholder="keyword1, keyword2" maxlength="512">
                    </div>
                    <div id="bdaiatg_alt_text_gen_btn" class="bdaiatg_alt_text_gen_btn_post">
                        <button>
                            <span class="loader"></span>
                            <span class="button_text">Generate ALT Text</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php
    }
}

// Initialize the class instance
BDAIATG_Ai_Image_Alt_Text_Generator_Gutenburg::get_instance();