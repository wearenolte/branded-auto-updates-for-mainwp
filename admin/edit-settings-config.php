<form id="wp-post-mark-emails-settings-keys" action="" method="">
<?php wp_nonce_field( 'wp_post_mark_emails_settings' ); ?>

<input type="hidden" name="page" value="wp-post-mark-emails">
<input type="hidden" name="tab" value="config">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Enable PostMark', 'wp_post_mark_emails' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'wp_post_mark_emails' ); ?></span></legend>
<label for="enable_postmark">
<input name="enable_postmark" type="checkbox" id="enable_postmark" value="1" <?php checked( get_option( 'wp_post_mark_emails_config_enable_post_mark', '' ), 1, TRUE ); ?>>
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
<label for="server_token">
<input class="regular-text ltr" name="server_token" type="text" id="server_token" value="<?php esc_attr_e( get_option( 'wp_post_mark_emails_config_server_token', '' ) ); ?>" placeholder="<?php _e( 'PostMark Server Token', 'wp_post_mark_emails' ); ?>">
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
<label for="sender_signature">
<input class="regular-text ltr" name="sender_signature" type="text" id="sender_signature" value="<?php esc_attr_e( get_option( 'wp_post_mark_emails_config_signature', '' ) ); ?>" placeholder="<?php _e( 'sender@example.com', 'wp_post_mark_emails' ); ?>">
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
<label for="template">
<input class="regular-text ltr" name="template" type="number" id="template" value="<?php esc_attr_e( get_option( 'wp_post_mark_emails_config_template_id', '' ) ); ?>" min="0" placeholder="<?php _e( 'ID', 'wp_post_mark_emails' ); ?>">
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