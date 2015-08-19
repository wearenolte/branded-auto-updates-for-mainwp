<?php

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

if ( ! current_user_can( 'manage_options' ) ) {
  wp_die( __( 'Cheatin&#8217; uh?' ) );
}

// All the actions we can do.
$actions = array(
  'wp_post_mark_emails_config_save',
  'wp_post_mark_emails_config_clear_and_save',
  'wp_post_mark_emails_test_send',
);

// Set the action to the first encountered action.
$do_action = '';
foreach ( $actions as $action_ ) {
 if ( isset( $_REQUEST[ $action_ ] ) ) {
    $do_action = $action_;
    break;
  }
}

// Set the page default tab.
$current_tab = 'config';

// if user specified a tab to view, use it
if ( isset( $_REQUEST['tab'] ) && in_array( $_REQUEST['tab'] , array( 'config', 'test' ) ) ) {
	$current_tab = $_REQUEST['tab'];
}

// We return to this file after every submit.
$parent_file = 'admin.php?page=wp-post-mark-emails';

$navigation_tabs = array(
  array(
    'href'  => add_query_arg( array( 'tab' => 'config' ) , admin_url( $parent_file ) ),
    'class' => 'nav-tab ' . ( ( 'config' === $current_tab ) ? 'nav-tab-active' : '' ),
    'text'  => __( 'Config', 'wp_post_mark_emails' ),
  ),

  array(
    'href'  => add_query_arg( array( 'tab' => 'test' ) , admin_url( $parent_file ) ),
    'class' => 'nav-tab ' . ( ( 'test' === $current_tab ) ? 'nav-tab-active' : '' ),
    'text'  => __( 'Test', 'wp_post_mark_emails' ),
  ),
);

if ( $do_action ) {
	
	$really = check_admin_referer( 'wp_post_mark_emails_settings' );
	
	// Construct the send back url.
	$send_back = remove_query_arg( array_keys( $actions ), wp_get_referer() );
	
	if ( ! $send_back ) {
		$send_back = self_admin_url( $parent_file );
	}

	switch ( $do_action ) {
		case 'wp_post_mark_emails_config_save':
			$enable_postmark = isset( $_REQUEST['enable_postmark'] ) ? (bool) $_REQUEST['enable_postmark'] : FALSE;
			update_option( 'wp_post_mark_emails_config_enable_post_mark', $enable_postmark );

			$server_token = isset( $_REQUEST['server_token'] ) ? (string) $_REQUEST['server_token'] : FALSE;
			update_option( 'wp_post_mark_emails_config_server_token', $server_token );

			$sender_signature = isset( $_REQUEST['sender_signature'] ) ? (string) $_REQUEST['sender_signature'] : FALSE;
			update_option( 'wp_post_mark_emails_config_signature', $sender_signature );

			$template = isset( $_REQUEST['template'] ) ? (int) $_REQUEST['template'] : FALSE;
			update_option( 'wp_post_mark_emails_config_template_id', $template );

			$query_args = array(
				'enable_postmark',
				'server_token',
				'sender_signature',
				'template',
			);
			
			$send_back = remove_query_arg( $query_args, $send_back );

			$query_args = array(
				'success' => 1,
			);

			$send_back = add_query_arg( $query_args, $send_back );

			unset( $enable_postmark, $server_token, $sender_signature, $template, $query_args );
			break;
		case 'wp_post_mark_emails_config_clear_and_save':
			update_option( 'wp_post_mark_emails_config_enable_post_mark', '' );
			update_option( 'wp_post_mark_emails_config_server_token', '' );
			update_option( 'wp_post_mark_emails_config_signature', '' );
			update_option( 'wp_post_mark_emails_config_template_id', '' );
			
			$query_args = array(
				'enable_postmark',
				'server_token',
				'sender_signature',
				'template',		
			);
			
			$send_back = remove_query_arg( $query_args, $send_back );

			$query_args = array(
				'success' => 1,
			);

			$send_back = add_query_arg( $query_args, $send_back );
			
			break;

		case 'wp_post_mark_emails_test_send':
		
			$test_email = isset( $_REQUEST['test_email'] ) ? (string) $_REQUEST['test_email'] : FALSE;

			$server_token 		= get_option( 'wp_post_mark_emails_config_server_token', '' );
			$sender_signature 	= get_option( 'wp_post_mark_emails_config_signature', '' );
			$template 			= get_option( 'wp_post_mark_emails_config_template_id', '' );

			if ( $server_token && $sender_signature ) {
				try {
					$client = new PostmarkClient( $server_token ); //'dc67bdf2-17bc-4b8f-828b-5af71b0628af'
					$sendResult = $client->sendEmail(
						$sender_signature, 
						$test_email, 
						"Yeh. It's working!",
						"This is just a friendly 'hello' from your friends at Postmark."
					);
				} catch ( PostmarkException $ex ) {
					// If client is able to communicate with the API in a timely fashion,
					// but the message data is invalid, or there's a server error,
					// a PostmarkException can be thrown.
					echo $ex->httpStatusCode;
					echo $ex->message;
					echo $ex->postmarkApiErrorCode;
				} catch ( Exception $generalException ) {
					// A general exception is thown if the API
					// was unreachable or times out.
				}
			}
			
			$query_args = array();
			$query_args['test_email'] = $test_email;
			$send_back = add_query_arg( $query_args, $send_back );
		
			break;
		
		default:
			break;
	}

	wp_redirect( $send_back );
	exit;
} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
  wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
  exit;
}

// Notification messages.
$nags = $messages = array();

// Warning messages.
if ( ! empty( $_REQUEST['notice'] ) ) {
  if ( 1 === (int) $_REQUEST['notice'] ) {
  	$nags[] = __( 'Something went wrong.' );
  }
}

// Success messages.
if ( ! empty( $_REQUEST['success'] ) ) {
  if ( 1 === (int) $_REQUEST['success'] ) {
  	$messages[] = __( 'Options saved.' );
  }
}

if ( ! empty( $nags ) ) {
	foreach ( $nags as  $nag ) {
	  echo '<div class="update-nag">' . $nag . '</div>';
	}	
}

if ( ! empty( $messages ) ) {
	foreach ( $messages as $message ) {
	  echo '<div class="updated">' . wpautop( $message ) . '</div>';
	}
}

?>

<div class="wrap">
	<h2><?php _e( 'WP Post Mark Emails', 'wp_post_mark_emails' ); ?></h2>

	<h2 class="nav-tab-wrapper">
	<?php
		foreach ( $navigation_tabs as  $navigation_tab ) { ?>
		<a href="<?php echo $navigation_tab['href']; ?>" class="<?php esc_attr_e( $navigation_tab['class'] ); ?>"><?php esc_html_e( $navigation_tab['text'] ); ?></a><?php
		}
	?>
	</h2>

	<?php require_once( WP_POST_MARK_EMAILS_PLUGIN_DIR . 'admin/edit-settings-' . ( ( isset( $current_tab ) ) ? $current_tab : 'config' ) . '.php' ); ?>

	<div id="ajax-response"></div>
	<br class="clear" />
</div>