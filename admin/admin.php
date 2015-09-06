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
    __( 'Main WP Automatic Updates', 'baufm' ),
    __( 'Branded Updates', 'baufm' ),
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
 * with offline checks in this plugin.
 */
function baufm_add_multiple_email_field( $website ) {
  ?>
  <tr>
    <th scope="row">
      <?php _e( 'Notification Emails after Offline Checks', 'baufm' ); ?>
      <?php MainWPUtility::renderToolTip( 'Add a list of comma-separated emails for multiple notifications.' ); ?>
    </th>
    <td>
      <?php
      $emails = MainWPDB::Instance()->getWebsiteOption( $website, 'baufm_emails_after_offline_check' );
      if ( empty( $emails ) ) {
        $emails = '';
      }
      ?>
      <textarea style="height: 140px; width: 100%;" name="baufm_emails_after_offline_check" id="baufm_emails_after_offline_check"><?php esc_html_e( $emails ); ?></textarea>
    </td>
  </tr>
  <?php
}
add_action( 'mainwp_extension_sites_edit_tablerow', 'baufm_add_multiple_email_field' );
