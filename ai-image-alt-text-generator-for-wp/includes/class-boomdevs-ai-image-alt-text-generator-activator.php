<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wpmessiah.com
 * @since      1.0.0
 *
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 * @subpackage Boomdevs_Ai_Image_Alt_Text_Generator/includes
 * @author     BoomDevs <contact@boomdevs.com>
 */
class Boomdevs_Ai_Image_Alt_Text_Generator_Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */


    public static function activate()
    {
        self::create_tables();
    }

    protected static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'ai_alt_text_generator_history';

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `attachment_id` INT NOT NULL,
        `total_count` INT NULL,        
        `gen_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,  
        `gen_by` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB $charset_collate;";

        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

}
