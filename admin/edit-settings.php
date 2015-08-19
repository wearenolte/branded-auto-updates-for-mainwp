<?php

if ( ! current_user_can( 'manage_options' ) ) {
  wp_die( __( 'Cheatin&#8217; uh?' ) );
}

// All the actions we can do.
$actions = array(
  'wp_post_mark_emails_settings_config_save',
  'wp_post_mark_emails_config_clear_and_save',
);

// set the action to the first encountered action
$do_action = '';
foreach ( $actions as $action_ ) {
 if ( isset( $_REQUEST[ $action_ ] ) ) {
    $do_action = $action_;
    break;
  }
}

// We return to this file after every submit.
$parent_file = 'admin.php?page=wp-post-mark-emails/wp-post-mark-emails.php/';

// Notification messages.
$nags = $messages = array();

// Warning messages.
if ( true ) {
  $nags[] = 'Test nag.';
}

// Success messages.
if ( true ) {
  $messages[] = 'Test message.';
}

if ( ! empty( $nags ) ) {
	foreach ( $nags as  $nag ) {
	  //echo '<div class="update-nag">' . $nag . '</div>';
	}	
}

if ( ! empty( $messages ) ) {
	foreach ( $messages as $message ) {
	  //echo '<div class="updated">' . wpautop( $message ) . '</div>';
	}
}

?>

<div class="wrap">
	<h2><?php _e( 'WP Post Mark Emails', 'wp_post_mark_emails' ); ?></h2>

	<?php require_once( WP_POST_MARK_EMAILS_PLUGIN_DIR . 'admin/edit-settings-config.php' ); ?>

	<div id="ajax-response"></div>
	<br class="clear" />
</div>