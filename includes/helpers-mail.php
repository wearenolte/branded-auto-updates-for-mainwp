<?php
/**
 * Email helpers
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */

/**
 * Format HTML email message.
 *
 * Do not use this when sending out templated PostMark HTML emails. When using
 * the Template API, you must edit your own PostMark template and use the template
 * fields provided by this plugin.
 *
 * @since 0.2.0
 * @author Udit Desai <udesai@getmoxied.net>
 *
 * @param String $to The email address to send an email to.
 * @param String $body The message body.
 * @param String $website_name The website name of the client.
 * @param String $website_url The website URL of the client.
 * @return String The HTML email message.
 */
if ( ! function_exists( 'baufm_format_email' ) ) :
	function baufm_format_email( $to, $body, $website_name, $website_url ) {
		if ( ! is_email( $to ) ) {
			return '';
		}

		if ( ! is_string( $body ) ) {
			return '';
		}

		if ( ! is_string( $website_name ) ) {
			return '';
		}

		if ( ! is_string( $website_url ) ) {
			return '';
		}

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
endif;
