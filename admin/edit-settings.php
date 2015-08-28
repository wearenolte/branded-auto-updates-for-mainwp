<?php

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

if ( ! current_user_can( 'manage_options' ) ) {
  wp_die( __( 'Cheatin&#8217; uh?' ) );
}

// All the actions we can do.
$actions = array(
  'branded_auto_updates_for_mainwp_config_save',
  'branded_auto_updates_for_mainwp_config_clear_and_save',
  'branded_auto_updates_for_mainwp_test_send',
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

// If user specified a tab to view, use it.
if ( isset( $_REQUEST['tab'] ) && in_array( $_REQUEST['tab'] , array( 'config', 'test', 'site-groups' ) ) ) {
	$current_tab = $_REQUEST['tab'];
}

// Any alternate content other than the default.
if ( isset( $_REQUEST['tab-content'] ) && in_array( $_REQUEST['tab-content'] , array( 'site-group-schedule' ) ) ) {
 	$current_content = $_REQUEST['tab-content'];
}

// We return to this file after every submit.
$parent_file = 'admin.php?page=branded-auto-updates-for-mainwp';

$navigation_tabs = array(
  array(
    'href'  => add_query_arg( array( 'tab' => 'config' ) , admin_url( $parent_file ) ),
    'class' => 'nav-tab ' . ( ( 'config' === $current_tab ) ? 'nav-tab-active' : '' ),
    'text'  => __( 'Email Service', 'branded_auto_updates_for_mainwp' ),
  ),

  array(
    'href'  => add_query_arg( array( 'tab' => 'test' ) , admin_url( $parent_file ) ),
    'class' => 'nav-tab ' . ( ( 'test' === $current_tab ) ? 'nav-tab-active' : '' ),
    'text'  => __( 'Email Test', 'branded_auto_updates_for_mainwp' ),
  ),

  array(
    'href'  => add_query_arg( array( 'tab' => 'site-groups' ) , admin_url( $parent_file ) ),
    'class' => 'nav-tab ' . ( ( 'site-groups' === $current_tab ) ? 'nav-tab-active' : '' ),
    'text'  => __( 'Site Group Schedule', 'branded_auto_updates_for_mainwp' ),
  ),
);

if ( $do_action ) {
	
	$really = check_admin_referer( 'branded_auto_updates_for_mainwp_settings' );
	
	// Construct the send back url.
	$send_back = remove_query_arg( array_keys( $actions ), wp_get_referer() );
	
	if ( ! $send_back ) {
		$send_back = self_admin_url( $parent_file );
	}

	switch ( $do_action ) {
		case 'branded_auto_updates_for_mainwp_config_save':
			$enable_postmark 	= isset( $_REQUEST['enable_postmark'] ) ? (bool) $_REQUEST['enable_postmark'] : FALSE;
			$server_token 		= isset( $_REQUEST['server_token'] ) ? (string) $_REQUEST['server_token'] : FALSE;
			$sender_signature 	= isset( $_REQUEST['sender_signature'] ) ? (string) $_REQUEST['sender_signature'] : FALSE;
			$template 			= isset( $_REQUEST['template'] ) ? (int) $_REQUEST['template'] : FALSE;
			
			update_option( 'branded_auto_updates_for_mainwp_config_enable_post_mark', $enable_postmark );
			update_option( 'branded_auto_updates_for_mainwp_config_server_token', $server_token );
			update_option( 'branded_auto_updates_for_mainwp_config_signature', $sender_signature );
			update_option( 'branded_auto_updates_for_mainwp_config_template_id', $template );

			$query_args = array(
				'enable_postmark',
				'server_token',
				'sender_signature',
				'template',
			);
			
			$send_back = remove_query_arg( $query_args, $send_back );

			$query_args = array();

			if ( ! $server_token || ! $sender_signature ) {
				update_option( 'branded_auto_updates_for_mainwp_config_enable_post_mark', FALSE );

				if ( $enable_postmark ) {
					$query_args = array(
						'options_action' => 'post_mark_disabled',
					);
				}
			} else {
				$query_args = array(
					'options_action' => 'save',
				);
			}

			$send_back = add_query_arg( $query_args, $send_back );

			unset( $enable_postmark, $server_token, $sender_signature, $template, $query_args );
			break;
		case 'branded_auto_updates_for_mainwp_config_clear_and_save':
			update_option( 'branded_auto_updates_for_mainwp_config_enable_post_mark', '' );
			update_option( 'branded_auto_updates_for_mainwp_config_server_token', '' );
			update_option( 'branded_auto_updates_for_mainwp_config_signature', '' );
			update_option( 'branded_auto_updates_for_mainwp_config_template_id', '' );
			
			$query_args = array(
				'enable_postmark',
				'server_token',
				'sender_signature',
				'template',		
			);
			
			$send_back = remove_query_arg( $query_args, $send_back );

			$query_args = array(
				'options_action' => 'clear_and_save',
			);

			$send_back = add_query_arg( $query_args, $send_back );

			unset( $query_args );
			break;

		case 'branded_auto_updates_for_mainwp_test_send':
		
			$test_email = isset( $_REQUEST['test_email'] ) ? (string) $_REQUEST['test_email'] : FALSE;

			$server_token 		= get_option( 'branded_auto_updates_for_mainwp_config_server_token', '' );
			$sender_signature 	= get_option( 'branded_auto_updates_for_mainwp_config_signature', '' );
			$template 			= get_option( 'branded_auto_updates_for_mainwp_config_template_id', '' );
			
			$query_args 				= array();
			$query_args['test_email']	= urlencode( $test_email );

			if ( $server_token && $sender_signature ) {
				try {
					$client = new PostmarkClient( $server_token );

					if ( $template ) {
						$template_model = new stdClass();
						$template_model->from_date 	= date( 'd/m/Y', strtotime( 'last monday', time() ) );
						$template_model->to_date  	= date( 'd/m/Y', time() );
						$template_model->site_url  	= get_option( 'siteurl' );

						$template_model->plugins_update = array(
							array( 'name' => 'Sample Plugin Update 1' ),
							array( 'name' => 'Sample Plugin Update 1' ),
						);

						$template_model->themes_update = array(
							array( 'name' => 'Sample Theme Update 1' ),
							array( 'name' => 'Sample Theme Update 2' ),
						);

						$template_model->core_update = array(
							array( 'name' => 'Sample Core Update 1' ),
							array( 'name' => 'Sample Core Update 2' ),
						);

						$sendResult = $client->sendEmailWithTemplate(
							$sender_signature, 
							$test_email, 
							$template,
							$template_model
						);
					} else {
						$sendResult = $client->sendEmail(
							$sender_signature, 
							$test_email, 
							__( "Yeh. It's working!", 'branded_auto_updates_for_mainwp' ),
							__( "This is just a friendly 'hello' from your friends at Postmark.", 'branded_auto_updates_for_mainwp' )
						);
					}

 					$query_args['postmark_success'] = base64_encode( json_encode( array(
						'message' => sprintf( __( 'Email sent to %s', 'branded_auto_updates_for_mainwp' ), $test_email ),
					) ) );
				} catch ( PostmarkException $ex ) {
					/* 
					 * If client is able to communicate with the API in a timely fashion,
					 * but the message data is invalid, or there's a server error,
					 * a PostmarkException can be thrown.
					 */
					echo $ex->httpStatusCode;
					echo $ex->message;
					echo $ex->postmarkApiErrorCode;

					if ( '' !== $ex->httpStatusCode || '' !== $ex->message || '' !== $ex->postmarkApiErrorCode ) {
						$query_args['postmark_exception'] = base64_encode( json_encode( array(
							'httpStatusCode' 		=> $ex->httpStatusCode,
							'message' 				=> $ex->message,
							'postmarkApiErrorCode' 	=> $ex->postmarkApiErrorCode,
						) ) );
					}
				} catch ( Exception $generalException ) {
					/* 
					 * A general exception is thown if the API was unreachable or times out. 
    				 */
					$query_args['postmark_general_exception'] = base64_encode( json_encode( array(
						'message' => __( 'API is unreachable or server has timed out.', 'branded_auto_updates_for_mainwp' ),
					) ) );
				}
			}
			
			$send_back = add_query_arg( $query_args, $send_back, $sender_signature, $template );
			
			unset( $query_args, $test_email, $server_token);
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

if ( ! empty( $_REQUEST['options_action'] ) ) {
  if ( 'save' === $_REQUEST['options_action'] ) {
  	$messages[] = __( 'Options saved.', 'branded_auto_updates_for_mainwp' );
  }

  if ( 'clear_and_save' === $_REQUEST['options_action'] ) {
  	$nags[] = __( 'Options cleared. Please supply a <strong>Server Token</strong> and a <strong>Sender Signature</strong> in order to use this WP Post Mark Emails.', 'branded_auto_updates_for_mainwp' );
  }
} else {
	if ( '' === get_option( 'branded_auto_updates_for_mainwp_config_server_token', '' ) && '' === get_option( 'branded_auto_updates_for_mainwp_config_signature', '' ) ) {
		$nags[] = __( 'Please supply a <strong>Server Token</strong> and a <strong>Sender Signature</strong> in order to use this WP Post Mark Emails.', 'branded_auto_updates_for_mainwp' );
	}
}

if ( ! empty( $_REQUEST['postmark_exception'] ) ) {
	$postmark_exception = strip_tags( wp_unslash( $_REQUEST['postmark_exception'] ) );
	$postmark_exception = (array) base64_decode( json_decode( preg_replace('/\s+/', '', $postmark_exception ) ) );

	$postmark_exception = wp_parse_args( $postmark_exception, array(
		'httpStatusCode' 		=> '',
		'message' 				=> '',
		'postmarkApiErrorCode' 	=> '',
	) );

	$message 				= $postmark_exception['message'];
	$postmarkApiErrorCode 	= '<code>postmarkApiErrorCode</code> : ' . $postmark_exception['postmarkApiErrorCode'];
	$httpStatusCode 		= '<code>httpStatusCode</code>: ' . $postmark_exception['httpStatusCode'];

  	$nags[] = sprintf( __( 'PostMark returned a PostMark Exception: %s. %s, %s', 'branded_auto_updates_for_mainwp' ), $message, $postmarkApiErrorCode, $httpStatusCode );

  	unset( $message, $postmarkApiErrorCode, $httpStatusCode );
}

if ( ! empty( $_REQUEST['postmark_success'] ) ) {
  	$postmark_success = strip_tags( wp_unslash( $_REQUEST['postmark_success'] ) );
	$postmark_success = (array) json_decode( base64_decode( preg_replace('/\s+/', '', $postmark_success ) ) );
	
	$postmark_success = wp_parse_args( $postmark_success, array(
		'message' 				=> '',
	) );

	$messages[] = $postmark_success['message'];

	unset( $postmark_success );
}

// Success messages.
if ( ! empty( $_REQUEST['postmark_general_exception'] ) ) {
  	$postmark_general_exception = strip_tags( wp_unslash( $_REQUEST['postmark_general_exception'] ) );
	$postmark_general_exception = (array) json_decode( base64_decode( preg_replace('/\s+/', '', $postmark_general_exception ) ) );
	
	$postmark_general_exception = wp_parse_args( $postmark_general_exception, array(
		'message' 				=> '',
	) );

	$nags[] = $postmark_general_exception['message'];

	unset( $postmark_general_exception );
}

// Print out any warning messages we have.
if ( ! empty( $nags ) ) {
	foreach ( $nags as  $nag ) {
	  echo '<div class="update-nag">' . $nag . '</div>';
	}	
}

// Print out any other messages that we have aside from warnings.
if ( ! empty( $messages ) ) {
	foreach ( $messages as $message ) {
	  echo '<div class="updated">' . wpautop( $message ) . '</div>';
	}
}
?>

<div class="wrap">
	<h2>Branded Auto Updates for MainWP</h2>

	<h2 class="nav-tab-wrapper" style="padding-bottom: 0;">
	<?php
		foreach ( $navigation_tabs as  $navigation_tab ) { ?>
			<a href="<?php echo $navigation_tab['href']; ?>" class="<?php esc_attr_e( $navigation_tab['class'] ); ?>"><?php esc_html_e( $navigation_tab['text'] ); ?></a><?php
		}
	?>
	</h2>

	<?php require_once( BRANDED_AUTO_UPDATES_FOR_MAINWP_PLUGIN_DIR . 'admin/edit-settings-' . ( ( isset( $current_content ) ) ? $current_content : $current_tab ) . '.php' ); ?>

	<div id="ajax-response"></div>
	<br class="clear" />
</div>