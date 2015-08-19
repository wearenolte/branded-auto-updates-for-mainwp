<?php
if ( ! function_exists( 'add_action' ) && ! function_exists( 'add_filter' ) ) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}


function wp_post_mark_emails_create_plugin_menu_page( ) {
  require_once( WP_POST_MARK_EMAILS_PLUGIN_DIR . 'admin/edit-settings.php' );
}

function wp_post_mark_emails_add_plugin_pages( ) {
  add_menu_page(
    __( 'WP Post Mark Emails', 'wp_post_mark_emails' ),
    __( 'Post Mark', 'wp_post_mark_emails' ),
    'manage_options',
    WP_POST_MARK_EMAILS_PLUGIN_DIR,
    'wp_post_mark_emails_create_plugin_menu_page',
    '',
    1
  );
}

add_action( 'admin_menu', 'wp_post_mark_emails_add_plugin_pages', 10 );