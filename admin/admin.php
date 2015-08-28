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

function baufm_create_plugin_menu_page() {
  require_once( BRANDED_AUTO_UPDATES_FOR_MAINWP_PLUGIN_DIR . 'admin/edit-settings.php' );
}

function baufm_add_plugin_pages() {
  add_submenu_page(
    'mainwp_tab',
    __( 'Main WP Automatic Updates', 'branded_auto_updates_for_mainwp' ),
    __( 'Branded Updates', 'branded_auto_updates_for_mainwp' ),
    'manage_options',
    'branded-auto-updates-for-mainwp',
    'baufm_create_plugin_menu_page'
  );
}
add_action( 'admin_menu', 'baufm_add_plugin_pages', 20 );

function baufm_add_multiple_email_field( $website ) {
  ?>
  <tr>
    <th scope="row">
      <?php _e( 'Notification Emails after Offline Checks', 'branded_auto_updates_for_mainwp' ); ?>
      <?php MainWPUtility::renderToolTip( 'Add a list of comma-separated emails for multiple notifications.' ); ?>
    </th>
    <td>
      <?php
      $emails = MainWPDB::Instance()->getWebsiteOption( $website, 'mwp_me_emails' );
      if ( empty( $emails ) ) {
        $emails = '';
      }
      ?>
      <textarea style="height: 140px; width: 100%;" name="mwp-me-emails" id="mwp-me-emails"><?php echo $emails; ?></textarea>
    </td>
  </tr>
  <?php
}
add_action( 'mainwp_extension_sites_edit_tablerow', 'baufm_add_multiple_email_field' );