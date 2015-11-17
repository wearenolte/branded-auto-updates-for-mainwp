<?php
/**
 * The main admin file
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

if ( ! function_exists( 'add_action' ) && ! function_exists( 'add_filter' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

/**
 * Callback function for hooking up the admin menu page.
 *
 * @since 0.1.0
 */
function baufm_create_plugin_menu_page() {
	include_once( BAUFM_PLUGIN_DIR . 'admin/edit-settings.php' );
}

/**
 * Add the admin menu page as a sub-tab of the MainWP dashboard tab.
 *
 * @since 0.1.0
 */
function baufm_add_plugin_pages() {
	add_submenu_page(
		'mainwp_tab',
		esc_html__( 'Main WP Automatic Updates', 'baufm' ),
		esc_html__( 'Branded Updates', 'baufm' ),
		'manage_options',
		'branded-auto-updates-for-mainwp',
		'baufm_create_plugin_menu_page'
	);
}
add_action( 'admin_menu', 'baufm_add_plugin_pages', 20 );

/**
 * Add UI to allow for multiple e-mail notifications after offline checks.
 *
 * Dashboard -> MainWP -> Sites -> General Options ->
 * Notification Emails after Offline Checks
 *
 * @since 0.2.0
 * @author Udit Desai
 *
 * @todo This needs to be moved elsewhere since we are not even dealing
 *       with offline checks in this plugin.
 */
function baufm_add_multiple_email_field( $website ) {
	?>
  <tr>
  <th scope="row">
    <?php
	esc_html_e( 'Notification Emails', 'baufm' );

	MainWPUtility::renderToolTip(
		esc_html__( 'Enter a list of comma-separated emails.', 'baufm' )
	);
	?>
  </th>
  <td>
    <?php
	/*
     * @todo Shorten the name and remove reference to 'offline'.
     * @todo Write a delta update once you do that.
     */
	$emails = MainWPDB::Instance()->getWebsiteOption( $website, 'baufm_emails_after_offline_check' );

	if ( empty( $emails ) ) {
		$emails = '';
	}
	?>
    <textarea name="baufm_emails_after_offline_check"><?php esc_html_e( $emails ); ?></textarea>
  </td>
  </tr><?php
}
add_action( 'mainwp_extension_sites_edit_tablerow', 'baufm_add_multiple_email_field' );

function baufm_add_mirror_site_url( $website ) {
	?>
  <tr>
  <th scope="row">
    <?php
	  esc_html_e( 'Mirror Site URL', 'baufm' );

	  MainWPUtility::renderToolTip(
	esc_html__( 'Enter the mirror site associated with this site.', 'baufm' )
	  );
	?>
  </th>
  <td>
    <?php
	  $mirror_site_url = MainWPDB::Instance()->getWebsiteOption( $website, 'baufm_mirror_site_url' );

	  if ( empty( $mirror_site_url ) ) {
	$mirror_site_url = '';
	  }
	?>
    <input name="baufm_mirror_site_url" value="<?php echo esc_url( $mirror_site_url ); ?>" >
  </td>
  </tr><?php
}
add_action( 'mainwp_extension_sites_edit_tablerow', 'baufm_add_mirror_site_url' );

function baufm_update_site( $website_id ) {
	$website = MainWPDB::Instance()->getWebsiteById( $website_id );

	if ( ! empty( $_POST['baufm_emails_after_offline_check'] ) ) {
		MainWPDB::Instance()->updateWebsiteOption(
			$website,
			'baufm_emails_after_offline_check',
			trim( $_POST['baufm_emails_after_offline_check'] )
		);
	} else {
		MainWPDB::Instance()->updateWebsiteOption(
			$website,
			'baufm_emails_after_offline_check',
			''
		);
	}

	if ( ! empty( $_POST['baufm_mirror_site_url'] ) ) {
		MainWPDB::Instance()->updateWebsiteOption(
			$website,
			'baufm_mirror_site_url',
			trim( $_POST['baufm_mirror_site_url'] )
		);
	} else {
		MainWPDB::Instance()->updateWebsiteOption(
			$website,
			'baufm_mirror_site_url',
			''
		);
	}
}
add_action( 'mainwp_update_site', 'baufm_update_site' );
