<?php
/**
 * The main plugin file for setup and initialization
 *
 * @since 0.1.0
 *
 * @package WP_Post_Mark_Emails
 */

if ( ! function_exists( 'add_action' ) && ! function_exists( 'add_filter' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

class WP_Post_Mark_Emails {

	/**
	 * Initialise the program after everything is ready.
	 *
	 * @since 0.1.0
	 */
	public static function init() {

	    // ALWAYS make sure the plugin version is up-to-date.
	    update_option( wp_post_mark_emails( 'plugin_version' ), GEOIPSL_PLUGIN_VERSION );
  	}

	/**
	 * Checks program environment to see if all dependencies are available. If at least one
	 * dependency is absent, deactivate the plugin.
	 *
	 * @since 0.1.0
	 */
	public static function maybe_deactivate() {

		global $wp_version;

		load_plugin_textdomain( 'wp_post_mark_emails' );

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( version_compare( $wp_version, wp_post_mark_emails_MINIMUM_WP_VERSION, '<' ) ) {

			deactivate_plugins( wp_post_mark_emails_PLUGIN_NAME );

		  	$message = sprintf( esc_html__( 'WP JSON Movies %s requires WordPress %s or higher.', 'wp_post_mark_emails' ), wp_post_mark_emails_PLUGIN_VERSION, wp_post_mark_emails_MINIMUM_WP_VERSION );

	  		wp_die( $message );
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
