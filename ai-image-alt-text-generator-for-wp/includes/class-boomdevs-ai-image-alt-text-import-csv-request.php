<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-boomdevs-ai-image-alt-text-import-csv-generator.php';

class BDAIATG_Ai_Image_Alt_Text_Import_Csv_Request extends WP_Background_Process
{
    /**
     * @var string
     */
    protected $action = 'alt_text_import_csv_process';

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($item)
    {
        Boomdevs_Ai_Image_Alt_Text_Import_Csv_Generator::import_image_generator($item);
        sleep(5);
        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete()
    {
        error_log('completed');
        parent::complete();
    }
}