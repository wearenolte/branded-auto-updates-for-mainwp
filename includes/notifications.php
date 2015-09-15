<?php

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

function baufm_send_emails_after_update( $pluginsNewUpdate, $pluginsToUpdate, $pluginsToUpdateNow, $themesNewUpdate, $themesToUpdate, $themesToUpdateNow, $coreNewUpdate, $coreToUpdate, $coreToUpdateNow ) {
	update_option('shit_150', array(
		$pluginsNewUpdate,
		$pluginsToUpdate,
		$pluginsToUpdateNow,
	));

	MainWPLogger::Instance()->info( 'MainWP Multiple Email Notification' );

	$website_updates = array();
	$template_model = new stdClass();

	$temlate_model->plugins_update 	= array();
	$temlate_model->themes_update 	= array();
	$temlate_model->core_update 	= array();

	// Plugin
	foreach ( $pluginsNewUpdate as $new_plugin ) {
		$plugin_html = $new_plugin[1] . $new_plugin[2];

		$website_updates[ $new_plugin[0] ]['plugins'][] = $plugin_html;

		$temlate_model->plugins_update[] = array(
			'name' => branded_auto_updates_for_mainwp_strip_html_and_contents( $plugin_html ),
		);
	}
	foreach ( $pluginsToUpdate as $plugin ) {
		$plugin_html = $plugin[1] . $plugin[2];

		$website_updates[ $plugin[0] ]['plugins'][] = $plugin_html;

		$temlate_model->plugins_update[] = array(
			'name' => branded_auto_updates_for_mainwp_strip_html_and_contents( $plugin_html ),
		);
	}

	// Theme
	foreach ( $themesNewUpdate as $new_theme ) {
		$theme_html = $new_theme[1] . $new_theme[2];

		$website_updates[ $new_theme[0] ]['themes'][] = $theme_html;

		$temlate_model->themes_update[] = array(
			'name' => branded_auto_updates_for_mainwp_strip_html_and_contents( $theme_html ),
		);
	}
	foreach ( $themesToUpdate as $theme ) {
		$theme_html = $theme[1] . $theme[2];

		$website_updates[ $theme[0] ]['themes'][] = $theme_html;

		$temlate_model->themes_update[] = array(
			'name' => branded_auto_updates_for_mainwp_strip_html_and_contents( $theme_html ),
		);
	}

	// Core
	foreach ( $coreNewUpdate as $new_core ) {
		$core_html = $new_core[1] . $new_core[2];

		$website_updates[ $new_core[0] ]['core'][] = $core_html;

		$temlate_model->core_update[] = array(
			'name' => branded_auto_updates_for_mainwp_strip_html_and_contents( $core_html ),
		);
	}
	foreach ( $coreToUpdate as $core ) {
		$core_html = $core[1] . $core[2];

		$website_updates[ $core[0] ]['core'][] = $core_html;

		$temlate_model->core_update[] = array(
			'name' => branded_auto_updates_for_mainwp_strip_html_and_contents( $core_html ),
		);
	}

	MainWPLogger::Instance()->info( 'Websites:' );
	MainWPLogger::Instance()->info( var_export( $website_updates, true ) );

	foreach ( $website_updates as $website_id => $updates ) {
		MainWPLogger::Instance()->info( 'Website ID : ' . $website_id );
		$website = MainWPDB::Instance()->getWebsiteById( $website_id );
		$plugins_content = '';

		if ( ! empty( $updates['plugins'] ) ) {
			$plugins_content = '<div><strong>WordPress Plugin Updates</strong></div>';
			$plugins_content .= '<ul>';
			foreach ( $updates['plugins'] as $plugin_update ) {
				$plugins_content .= ( '<li>' . $plugin_update . '</li>' );
			}
			$plugins_content .= '</ul>';
		}
		$themes_content = '';
		if ( ! empty( $updates['themes'] ) ) {
			$themes_content = '<div><strong>WordPress Theme Updates</strong></div>';
			$themes_content .= '<ul>';
			foreach ( $updates['themes'] as $theme_update ) {
				$themes_content .= ( '<li>' . $theme_update . '</li>' );
				$temlate_model->themes_update[] = array(
					'name' => $theme_update,
				);
			}
			$themes_content .= '</ul>';
		}
		$core_content = '';
		if ( ! empty( $updates['core'] ) ) {
			$core_content = '<div><strong>WordPress Core Updates</strong></div>';
			$core_content .= '<ul>';
			foreach ( $updates['core'] as $core_update ) {
				$core_content .= ( '<li>' . $core_update . '</li>' );
				$temlate_model->core_update[] = array(
					'name' => $core_update,
				);
			}
			$core_content .= '</ul>';
		}

		$mail_content = ( $plugins_content . $themes_content . $core_content );
		$mail_content = trim( $mail_content );

		MainWPLogger::Instance()->info( 'Mail Content:' );
		MainWPLogger::Instance()->info( $mail_content );

		if ( ! empty( $mail_content ) ) {
			$mail_content = ( '<div>Following updates have been applied on your WordPress Site. (<a href="' . $website->url . '">' . $website->name . '</a>)</div>' . $mail_content );

			$emails = MainWPDB::Instance()->getWebsiteOption( $website, 'mwp_me_emails' );
			MainWPLogger::Instance()->info( var_export( $emails, true ) );

			$emails = explode( ',', $emails );
			MainWPLogger::Instance()->info( var_export( $emails, true ) );

			$server_token 		= get_option( 'baufm_config_server_token', '' );
			$sender_signature 	= get_option( 'baufm_config_signature', '' );
			$template 			= (int) get_option( 'baufm_config_template_id', '' );
			$enable_post_mark  	= (bool) get_option( 'baufm_config_enable_post_mark', false );

			if ( ! empty( $emails ) && is_array( $emails ) ) {
				foreach ( $emails as $email ) {
					$email = trim( $email );
					MainWPLogger::Instance()->info( var_export( $email, true ) );

					if ( is_email( $email ) ) {
						if ( $enable_post_mark && $server_token && $sender_signature ) {
							try {
								$client = new PostmarkClient( $server_token );

								if ( $template ) {
									$sendResult = $client->sendEmailWithTemplate(
										$sender_signature,
										$email,
										$template,
										$temlate_model
									);
								} else {
									$sendResult = $client->sendEmail(
										$sender_signature,
										$email,
										$website->name . ' - ' . __( 'Trusted Automated Updates', 'baufm' ),
										$body
									);
								}
						 	} catch ( PostmarkException $ex ) {
						 		MainWPLogger::Instance()->info( var_export( $ex, true ) );
						 	} catch ( Exception $generalException ) {
						 		MainWPLogger::Instance()->info( var_export( $generalException, true ) );
						 	}
						} else {
							$body = branded_auto_updates_for_mainwp_format_email( $email, $mail_content, $website->name, $website->url );
							wp_mail( $email, $website->name . ' - Trusted Automated Updates', $body, array( 'From: "' . get_option( 'admin_email' ) . '" <' . get_option( 'admin_email' ) . '>', 'content-type: text/html' ) );
						}
					}
				}
			}
		}
	}
}
add_action( 'baufm_cronupdatecheck_action', 'baufm_send_emails_after_update', 10, 9 );
