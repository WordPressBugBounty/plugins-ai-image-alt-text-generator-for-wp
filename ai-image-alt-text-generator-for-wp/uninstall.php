<?php

/**
 * This file is responsible for handling plugin uninstallation.
 * It ensures that the uninstallation process is triggered from within WordPress.
 * It checks the authentication and verifies the plugin name before proceeding.
 * This file may be updated in future versions of the plugin, but the general
 * outline and functionality remain consistent.
 *
 * For more information on plugin uninstallation, refer to the following link:
 * @link       https://wpmessiah.com/
 * @since      1.0.0
 *
 * @package    Boomdevs_Ai_Image_Alt_Text_Generator
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}