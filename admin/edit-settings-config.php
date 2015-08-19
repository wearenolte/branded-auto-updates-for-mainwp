<form id="wp-post-mark-emails-settings-keys" action="" method="">
<?php wp_nonce_field( 'wp_post_mark_emails_settings' ); ?>

<input type="hidden" name="page" value="wp-post-mark-emails/wp-post-mark-emails.php">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Enable PostMark', 'wp_post_mark_emails' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'wp_post_mark_emails' ); ?></span></legend>
<label for="wp_post_mark_emails_server_token">
<input name="wp_post_mark_emails_server_token" type="checkbox" id="wp_post_mark_emails_server_token" value="">
<?php _e( 'Override <code>wp_mail()</code> to send out emails to using PostMark.', 'wp_post_mark_emails' ); ?>
</label>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'PostMark Server Token', 'wp_post_mark_emails' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'PostMark Server Token', 'wp_post_mark_emails' ); ?></span></legend>
<label for="wp_post_mark_emails_server_token">
<input class="regular-text ltr" name="wp_post_mark_emails_server_token" type="text" id="wp_post_mark_emails_server_token" value="" placeholder="<?php _e( 'PostMark Server Token', 'wp_post_mark_emails' ); ?>">
</label>
<p><?php echo sprintf( wp_kses( __( 'Get your <a href="%s">server token</a> from your PostMark account. This is required for authentication on server specific endpoints.', 'wp_post_mark_emails' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/servers' ) ); ?></p>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Sender Signature', 'wp_post_mark_emails' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Settings', 'wp_post_mark_emails' ); ?></span></legend>
<label for="wp_post_mark_emails_sender_signature">
<input class="regular-text ltr" name="wp_post_mark_emails_sender_signature" type="text" id="wp_post_mark_emails_sender_signature" value="" placeholder="<?php _e( 'email@example.com', 'wp_post_mark_emails' ); ?>">
<p><?php echo sprintf( wp_kses( __( 'Select from one of the validated <a href="%s">sender signatures</a> you have on your PostMark account.', 'wp_post_mark_emails' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/signatures' ) ); ?></p>
</label>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Email Template', 'wp_post_mark_emails' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Settings', 'wp_post_mark_emails' ); ?></span></legend>
<label for="wp_post_mark_emails_template">
<input class="regular-text ltr" name="wp_post_mark_emails_template" type="number" id="wp_post_mark_emails_template" value="" min="0" placeholder="<?php _e( 'ID', 'wp_post_mark_emails' ); ?>">
<p><?php echo sprintf( wp_kses( __( 'Find the template you wish to use in the "Templates" section of <a href="%s">server</a> you wish to use.', 'wp_post_mark_emails' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/servers' ) ); ?></p>
</label>
</fieldset>
</td>
</tr>
</tbody>
</table>
<br>
<?php submit_button( __( 'Clear', 'wp_post_mark_emails' ), 'secondary', 'wp_post_mark_emails_config_clear_and_save', false ); ?>  
<?php submit_button( __( 'Save', 'wp_post_mark_emails' ), 'primary', 'wp_post_mark_emails_config_save', false ); ?>
</form>