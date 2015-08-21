<?php

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

function mwp_men_format_email( $to, $body, $website_name, $website_url ) {
	return '<br>
			<div>
            <br>
            <div style="background:#ffffff;padding:0 1.618em;font:13px/20px Helvetica,Arial,Sans-serif;padding-bottom:50px!important">
                <div style="width:600px;background:#fff;margin-left:auto;margin-right:auto;margin-top:10px;margin-bottom:25px;padding:0!important;border:10px Solid #fff;border-radius:10px;overflow:hidden">
                    <div style="display: block; width: 100% ; background: #fafafa; border-bottom: 2px Solid #7fb100 ; overflow: hidden;">
                      <div style="display: block; width: 95% ; margin-left: auto ; margin-right: auto ; padding: .5em 0 ;">
                         <div style="float: left;"><a href="' . $website_url . '">' . $website_name . '</a></div>
                         <div style="clear: both;"></div>
                      </div>
                    </div>
                    <div>
                        <p>Hello ' . $to . '!<br></p>
                        ' . $body . '
                        <div></div>
                        <br />
                        <div> '. $website_name .'</div>
                        <div><a href="' . $website_url . '" target="_blank">' . $website_url . '</a></div>
                        <p></p>
                    </div>

                    <div style="display: block; width: 100% ; background: #1c1d1b;">
                      <div style="display: block; width: 95% ; margin-left: auto ; margin-right: auto ; padding: .5em 0 ;">
                        <div style="padding: .5em 0 ; float: left;"><p style="color: #fff; font-family: Helvetica, Sans; font-size: 12px ;">© ' . date( 'Y' ) . ' ' . $website_name . '. All Rights Reserved.</p></div>
                        <div style="float: right;"><a href="' . $website_url . '"></a></div><div style="clear: both;"></div>
                      </div>
                   </div>
                </div>
                <center>
                    <br><br><br><br><br><br>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#ffffff;border-top:1px solid #e5e5e5">
                        <tbody><tr>
                            <td align="center" valign="top" style="padding-top:20px;padding-bottom:20px">
                                <table border="0" cellpadding="0" cellspacing="0">
                                    <tbody><tr>
                                        <td align="center" valign="top" style="color:#606060;font-family:Helvetica,Arial,sans-serif;font-size:11px;line-height:150%;padding-right:20px;padding-bottom:5px;padding-left:20px;text-align:center">
                                            This email is sent from your MainWP Multiple Email Notifications.
                                            <br>
                                            If you do not wish to receive these notices please re-check your preferences in the MainWP Settings page.
                                            <br>
                                            <br>
                                        </td>
                                    </tr>
                                </tbody></table>
                            </td>
                        </tr>
                    </tbody></table>

                </center>
            </div>
</div>
<br>';
}

function mwp_men_add_multiple_email_field( $website ) {
	?>
	<tr>
		<th scope="row">
			<?php _e( 'Notification Emails after Offline Checks', 'wp_post_mark_emails' ); ?>
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
add_action( 'mainwp_extension_sites_edit_tablerow', 'mwp_men_add_multiple_email_field' );

function mwp_men_update_site( $website_id ) {
	$website = MainWPDB::Instance()->getWebsiteById( $website_id );
	if ( ! empty( $_POST['mwp-me-emails'] ) ) {
		MainWPDB::Instance()->updateWebsiteOption( $website, 'mwp_me_emails', trim( $_POST['mwp-me-emails'] ) );
	} else {
		MainWPDB::Instance()->updateWebsiteOption( $website, 'mwp_me_emails', '' );
	}
}
add_action( 'mainwp_update_site', 'mwp_men_update_site' );

function mwp_men_send_emails_after_update( $pluginsNewUpdate, $pluginsToUpdate, $pluginsToUpdateNow, $themesNewUpdate, $themesToUpdate, $themesToUpdateNow, $coreNewUpdate, $coreToUpdate, $coreToUpdateNow ) {
	update_option( 'wp_post_mark_emails_my_cron_test', 'working_naman_ah' );

	MainWPLogger::Instance()->info( 'MainWP Multiple Email Notification' );

	$website_updates = array();
	$template_model = new stdClass();

	$temlate_model->plugins_update 	= array();
	$temlate_model->themes_update 	= array();
	$temlate_model->core_update 	= array();

	// Plugin
	foreach ( $pluginsNewUpdate as $new_plugin ) {
		$website_updates[ $new_plugin[0] ]['plugins'][] = $new_plugin[1] . $new_plugin[2];
	}
	foreach ( $pluginsToUpdate as $plugin ) {
		$website_updates[ $plugin[0] ]['plugins'][] = $plugin[1] . $plugin[2];
	}

	// Theme
	foreach ( $themesNewUpdate as $new_theme ) {
		$website_updates[ $new_theme[0] ]['themes'][] = $new_theme[1] . $new_theme[2];
	}
	foreach ( $themesToUpdate as $theme ) {
		$website_updates[ $theme[0] ]['themes'][] = $theme[1] . $theme[2];
	}

	// Core
	foreach ( $coreNewUpdate as $new_core ) {
		$website_updates[ $new_core[0] ]['core'][] = $new_core[1] . $new_core[2];
	}
	foreach ( $coreToUpdate as $core ) {
		$website_updates[ $core[0] ]['core'][] = $core[1] . $core[2];
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
				$temlate_model->plugins_update[] = array(
					'name' => $plugin_update,
				);
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
			MainWPLogger::Instance()->info( var_export( $emails,true ) );
			$emails = explode( ',', $emails );
			MainWPLogger::Instance()->info( var_export( $emails, true ) );

			if ( ! empty( $emails ) && is_array( $emails ) ) {
				foreach ( $emails as $email ) {
					$email = trim( $email );
					MainWPLogger::Instance()->info( var_export( $email, true ) );
					if ( is_email( $email ) ) {
						
						$server_token 		= get_option( 'wp_post_mark_emails_config_server_token', '' );
						$sender_signature 	= get_option( 'wp_post_mark_emails_config_signature', '' );
						$template 			= (int) get_option( 'wp_post_mark_emails_config_template_id', '' );
						$enable_post_mark  	= (bool) get_option( 'wp_post_mark_emails_config_enable_post_mark', FALSE );

						if ( $enable_post_mark && $server_token && $sender_signature ) {
							
							$client = new PostmarkClient( $server_token );

							if ( $template ) {
								$sendResult = $client->sendEmailWithTemplate(
									$sender_signature, 
									$test_email, 
									$template,
									$temlate_model
								);
							} else {
								$sendResult = $client->sendEmail(
									$sender_signature, 
									$test_email, 
									$website->name . ' - ' . __( 'Trusted Automated Updates', 'wp_post_mark_emails' ),
									$body
								);
							}
						} else {
							$body = mwp_men_format_email( $email, $mail_content, $website->name, $website->url );
							wp_mail( $email, $website->name . ' - Trusted Automated Updates', $body, array( 'From: "' . get_option( 'admin_email' ) . '" <' . get_option( 'admin_email' ) . '>', 'content-type: text/html' ) );	
						}
						
					}
				}
			}
		}
	}
}
add_action( 'mainwp_cronupdatecheck_action', 'mwp_men_send_emails_after_update', 10, 9 );