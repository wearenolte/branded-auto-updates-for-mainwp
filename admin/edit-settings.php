<?php
/**
 * The main settings file
 *
 * This file handles the saving and loading of all other settings files.
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Cheatin&#8217; uh?' ) );
}

// All the actions we can do.
$actions = array(
	'baufm_config_save',
	'baufm_config_clear_and_save',
	'baufm_test_send',
	'baufm_save_site_group_sched',
	'baufm_update_now',
	'baufm_sched_cancel_now',
);

// Set the action to the first encountered action.
$do_action = '';
foreach ( $actions as $action_ ) {
	if ( isset( $_REQUEST[ $action_ ] ) ) { // Input var ok.
		$do_action = $action_;
		break;
	}
}

// Set the page default tab.
$current_tab = 'site-groups';

// If user specified a tab to view, use it.
if ( isset( $_REQUEST['tab'] ) && in_array( wp_unslash( $_REQUEST['tab'] ) , array( 'email-config', 'email-test', 'site-groups' ) ) ) { // Input var okay.
	$current_tab = wp_unslash( $_REQUEST['tab'] );
}

// Any alternate content other than the default.
if ( isset( $_REQUEST['tab-content'] ) && in_array( $_REQUEST['tab-content'] , array( 'site-group-schedule', 'site-group-update-now' ) ) ) {
	$current_content = wp_unslash( $_REQUEST['tab-content'] );
} else {
	$current_content = $current_tab;
}

// We return to this file after every submit.
$parent_file = 'admin.php?page=branded-auto-updates-for-mainwp';

$navigation_tabs = array(
	array(
	'href'  => add_query_arg( array( 'tab' => 'site-groups' ) , admin_url( $parent_file ) ),
	'class' => 'nav-tab ' . ( ( 'site-groups' === $current_tab ) ? 'nav-tab-active' : '' ),
	'text'  => esc_html__( 'Batch Group Updates', 'baufm' ),
	),

	array(
	'href'  => add_query_arg( array( 'tab' => 'email-config' ) , admin_url( $parent_file ) ),
	'class' => 'nav-tab ' . ( ( 'email-config' === $current_tab ) ? 'nav-tab-active' : '' ),
	'text'  => esc_html__( 'Email Service', 'baufm' ),
	),

	array(
	'href'  => add_query_arg( array( 'tab' => 'email-test' ) , admin_url( $parent_file ) ),
	'class' => 'nav-tab ' . ( ( 'email-test' === $current_tab ) ? 'nav-tab-active' : '' ),
	'text'  => esc_html__( 'Email Test', 'baufm' ),
	),
);

if ( $do_action ) {

	$really = check_admin_referer( 'baufm_settings_nonce' );

	// Construct the send back url.
	$send_back = remove_query_arg( $actions, wp_get_referer() );

	if ( ! $send_back ) {
		$send_back = self_admin_url( $parent_file );
	}

	switch ( $do_action ) {
		case 'baufm_config_save':
			$enable_postmark 	= isset( $_REQUEST['enable_postmark'] ) ? (bool) $_REQUEST['enable_postmark'] : false;
			$server_token	= isset( $_REQUEST['server_token'] ) ? wp_unslash( $_REQUEST['server_token'] ) : false;
			$sender_signature	= isset( $_REQUEST['sender_signature'] ) ? wp_unslash( $_REQUEST['sender_signature'] ): false;
			$template	= isset( $_REQUEST['template'] ) ? absint( $_REQUEST['template'] ) : false;

			update_option( 'baufm_config_enable_post_mark', $enable_postmark );
			update_option( 'baufm_config_server_token', $server_token );
			update_option( 'baufm_config_signature', $sender_signature );
			update_option( 'baufm_config_template_id', $template );

			$query_args = array(
				'enable_postmark',
				'server_token',
				'sender_signature',
				'template',
			);

			$send_back = remove_query_arg( $query_args, $send_back );

			$query_args = array();

			if ( ! $server_token || ! $sender_signature ) {
				update_option( 'baufm_config_enable_post_mark', false );

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

		case 'baufm_config_clear_and_save':
			update_option( 'baufm_config_enable_post_mark', '' );
			update_option( 'baufm_config_server_token', '' );
			update_option( 'baufm_config_signature', '' );
			update_option( 'baufm_config_template_id', '' );

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

		case 'baufm_test_send':

			$test_email = isset( $_REQUEST['test_email'] ) ? wp_unslash( $_REQUEST['test_email'] ) : false;

			$server_token	= get_option( 'baufm_config_server_token', '' );
			$sender_signature	= get_option( 'baufm_config_signature', '' );
			$template	= get_option( 'baufm_config_template_id', '' );

			$query_args	= array();
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
							array( 'name' => esc_html__( 'Sample Plugin Update 1', 'baufm' ) ),
							array( 'name' => esc_html__( 'Sample Plugin Update 1', 'baufm' ) ),
						);

						$template_model->themes_update = array(
							array( 'name' => esc_html__( 'Sample Theme Update 1', 'baufm' ) ),
							array( 'name' => esc_html__( 'Sample Theme Update 2', 'baufm' ) ),
						);

						$template_model->core_update = array(
							array( 'name' => esc_html__( 'Sample Core Update 1', 'baufm' ) ),
							array( 'name' => esc_html__( 'Sample Core Update 2', 'baufm' ) ),
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
							esc_html__( "Yeh. It's working!", 'baufm' ),
							esc_html__( "This is just a friendly 'hello' from your friends at Postmark.", 'baufm' )
						);
					}

						$query_args['postmark_success'] = base64_encode( wp_json_encode( array(
							'message' => sprintf( esc_html__( 'Email sent to %s', 'baufm' ), $test_email ),
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
						$query_args['postmark_exception'] = base64_encode( wp_json_encode( array(
							'httpStatusCode' => $ex->httpStatusCode,
							'message'	=> $ex->message,
							'postmarkApiErrorCode' => $ex->postmarkApiErrorCode,
						) ) );
					}
				} catch ( Exception $generalException ) {
					/*
           * A general exception is thown if the API was unreachable or times out.
    			 */
					$query_args['postmark_general_exception'] = base64_encode( wp_json_encode( array(
						'message' => esc_html__( 'API is unreachable or server has timed out.', 'baufm' ),
					) ) );
				}
			}

			$send_back = add_query_arg( $query_args, $send_back, $sender_signature, $template );

			unset( $query_args, $test_email, $server_token );
			break;

		case 'baufm_save_site_group_sched':
			$group_id	= isset( $_REQUEST['group-id'] ) ? absint( $_REQUEST['group-id'] ) : false; // Input var okay.
			$schedule_in_week = isset( $_REQUEST['schedule_in_week'] ) ? absint( $_REQUEST['schedule_in_week'] ) : false;
			$schedule_in_day	= isset( $_REQUEST['schedule_in_day'] ) ? absint( $_REQUEST['schedule_in_day'] ) : false;
			$scheduled_action	= isset( $_REQUEST['scheduled_action'] ) ? absint( $_REQUEST['scheduled_action'] ) : false;

			update_option( "baufm_schedule_in_week_group_$group_id", $schedule_in_week );
			update_option( "baufm_schedule_in_day_group_$group_id",	$schedule_in_day );
			update_option( "baufm_scheduled_action_group_$group_id", $scheduled_action );

			$query_args = array(
				'schedule_in_week',
				'schedule_in_day',
				'scheduled_action',
			);

			$send_back = remove_query_arg( $query_args, $send_back );

			unset( $query_args );
			break;

		case 'baufm_update_now':
			$group_id	= isset( $_REQUEST['group-id'] ) ? absint( $_REQUEST['group-id'] ) : false; // Input var okay.
			$scheduled_action	= isset( $_REQUEST['scheduled_action'] ) ? absint( $_REQUEST['scheduled_action'] ) : false;

			wp_schedule_single_event( time(), 'baufm_update_now', array( $group_id, $scheduled_action ) );

			$send_back = remove_query_arg( $query_args, $send_back );
			break;

		default:
			break;
	}

	wp_redirect( $send_back );
	exit;

} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) { // Input var okay.
	wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	exit;
}

// Notification messages.
$nags = $messages = array();

if ( ! empty( $_REQUEST['options_action'] ) ) { // Input var okay.
	if ( 'save' === $_REQUEST['options_action'] ) { // Input var okay.
		$messages[] = esc_html__( 'Options saved.', 'baufm' );
	}

	if ( 'clear_and_save' === $_REQUEST['options_action'] ) { // Input var okay.
		$nags[] = wp_kses( __( 'Options cleared. Please supply a <strong>Server Token</strong> and a <strong>Sender Signature</strong> in order to use this PostMark.', 'baufm' ), array( 'strong' => array() ) );
	}
} else {
	if ( '' === get_option( 'baufm_config_server_token', '' ) && '' === get_option( 'baufm_config_signature', '' ) ) {
		$nags[] = wp_kses( __( 'Please supply a <strong>Server Token</strong> and a <strong>Sender Signature</strong> in order to use this PostMark.', 'baufm' ), array( 'strong' => array() ) );
	}
}

// Success messages.
if ( ! empty( $_REQUEST['postmark_success'] ) ) { // Input var okay.
	$postmark_success = wp_unslash( $_REQUEST['postmark_success'] );
	$postmark_success = (array) json_decode( base64_decode( preg_replace( '/\s+/', '', $postmark_success ) ) );
	$postmark_success = wp_parse_args( $postmark_success, array(
		'message' 				=> '',
	) );

	$messages[] = $postmark_success['message'];

	unset( $postmark_success );
}

// Error messages.
if ( isset( $_REQUEST['postmark_general_exception'] ) ) { // Input var okay.
	$postmark_gen_except = wp_unslash( $_REQUEST['postmark_general_exception'] );
	$postmark_gen_except = (array) json_decode( base64_decode( preg_replace( '/\s+/', '', $postmark_gen_except ) ) );
	$postmark_gen_except = wp_parse_args( $postmark_gen_except, array(
		'message' 				=> '',
	) );

	$nags[] = $postmark_gen_except['message'];

	unset( $postmark_gen_except );
}

if ( ! empty( $_REQUEST['postmark_exception'] ) ) { // Input var okay.
	$postmark_exception = strip_tags( wp_unslash( $_REQUEST['postmark_exception'] ) );
	$postmark_exception = (array) base64_decode( json_decode( preg_replace( '/\s+/', '', $postmark_exception ) ) );
	$postmark_exception = wp_parse_args( $postmark_exception, array(
		'httpStatusCode' 		=> '',
		'message' 				=> '',
		'postmarkApiErrorCode' 	=> '',
	) );

	$message 				= $postmark_exception['message'];
	$postmarkApiErrorCode 	= '<code>postmarkApiErrorCode</code> : ' . $postmark_exception['postmarkApiErrorCode'];
	$httpStatusCode 		= '<code>httpStatusCode</code>: ' . $postmark_exception['httpStatusCode'];

		$nags[] = sprintf( __( 'PostMark returned a PostMark Exception: %s. %s, %s', 'baufm' ), $message, $postmarkApiErrorCode, $httpStatusCode );

		unset( $message, $postmarkApiErrorCode, $httpStatusCode );
} ?>

<?php // Print out any warning messages we have.
if ( ! empty( $nags ) ) :
	foreach ( $nags as $nag ) :
		?><div class="update-nag"><p><?php echo wp_kses( $nag, array() ); ?></p></div>;<?php
	endforeach;
endif;
?>

<?php // Print out any other messages that we have aside from warnings.
if ( ! empty( $messages ) ) :
	foreach ( $messages as $message ) :
		?><div class="updated"><p><?php echo wp_kses( $message, array() ); ?></p></div>;<?php
	endforeach;
endif;
?>

<div class="wrap">
	<h2><?php echo wp_kses( sprintf( esc_html__( 'Branded Auto Updates for MainWP %s', 'baufm' ), BAUFM_PLUGIN_VERSION ), array() ); ?></h2>
	<p class="description"><?php echo wp_kses( __( 'Notify your clients about site updates. <i>With style!</i>', 'baufm' ), array( 'i' => array() ) ); ?></p>

	<h2 class="nav-tab-wrapper" style="padding-bottom: 0;">
	<?php foreach ( $navigation_tabs as $tab ) : ?>
	<a href="<?php echo esc_url( $tab['href'] ); ?>" class="<?php esc_attr_e( $tab['class'] ); ?>"><?php esc_html_e( $tab['text'] ); ?></a>
	<?php endforeach; ?>
	</h2>

	<?php include_once( BAUFM_PLUGIN_DIR . "admin/edit-settings-$current_content.php" ); ?>

	<div id="ajax-response"></div>
	<br class="clear" />
</div>
