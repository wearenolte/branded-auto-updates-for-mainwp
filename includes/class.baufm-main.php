<?php
/**
 * The main plugin file for setup and initialization
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

if ( ! function_exists( 'add_action' ) && ! function_exists( 'add_filter' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

class BAUFM_Main {

	/**
	 * Initialise the program after everything is ready.
	 *
	 * @since 0.1.0
	 */
	public static function init() {

	    // ALWAYS make sure the plugin version is up-to-date.
	    update_option( 'baufm_plugin_version', BAUFM_PLUGIN_VERSION );

	    // Initialize configuration options.
	    add_option( 'baufm_config_enable_post_mark', '' );
	    add_option( 'baufm_config_server_token', '' );
	    add_option( 'baufm_config_signature', '' );
	    add_option( 'baufm_config_template_id', '' );

	    wp_clear_scheduled_hook( 'mainwp_cronupdatescheck_action' );
	    $baufm_updater = new BAUFM_Updater();

	    ob_start();
	}

	/**
	 * Checks program environment to see if all dependencies are available. If at least one
	 * dependency is absent, deactivate the plugin.
	 *
	 * @since 0.1.0
	 */
	public static function maybe_deactivate() {

		global $wp_version;

		load_plugin_textdomain( 'baufm' );

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( version_compare( $wp_version, BAUFM_MIN_WP_VERSION, '<' ) ) {

			deactivate_plugins( BAUFM_PLUGIN_NAME );

		  	$message = sprintf( __( 'Branded Auto Updates for MainWP %s requires WordPress %s or higher.', 'baufm' ), BAUFM_PLUGIN_VERSION, BAUFM_MIN_WP_VERSION );

	  		wp_die( $message );
		  	exit;
		}

		if ( ! defined( 'MAINWP_PLUGIN_FILE' ) ) {
			deactivate_plugins( BAUFM_PLUGIN_NAME );

			wp_die( __( "Hi there! I'm just a MainWP extension, not much I can do on my own.", 'baufm' ), __( 'MainWP Required', 'baufm' ) );
			exit;
		}
	}

	/**
	 * Function to call when the way the plugin models data (e.g. stores in the database)
	 * changes. If the way data is stored actually do change, the body of this function should
	 * contain code that triggers a series of database updates in the event that a newer version
	 * of this plugin is installed replacing an older version.
	 *
	 * @since 0.1.0
	 */
	public static function maybe_update() {}
}
