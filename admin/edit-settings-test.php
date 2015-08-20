<form id="wp-post-mark-emails-settings-keys" action="" method="">
<?php wp_nonce_field( 'wp_post_mark_emails_settings' ); ?>

<input type="hidden" name="page" value="wp-post-mark-emails">
<input type="hidden" name="tab" value="test">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Test Email *', 'wp_post_mark_emails' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'wp_post_mark_emails' ); ?></span></legend>
<label for="test_email">
<?php $test_email = isset( $_REQUEST['test_email'] ) ? (string) $_REQUEST['test_email'] : ''; ?>
<?php $disabled = ( '' === get_option( 'wp_post_mark_emails_config_server_token', '' ) && '' === get_option( 'wp_post_mark_emails_config_signature', '' ) ); ?>
<input class="regular-text ltr" name="test_email" type="email" id="test_email" value="<?php esc_attr_e( $test_email ); ?>" placeholder="receiver@example.com" required <?php echo ( $disabled ) ? 'disabled' : ''; ?>>
</label>
<p class="description"><?php _e( 'Send a test email using the settings you provided on the configuration tab.', 'wp_post_mark_emails' ); ?></p>
</fieldset>
</td>
</tr>
</tbody>
</table>
<p>*<?php echo wp_kses( __( 'Indicates <strong>required</strong> fields.', 'wp_post_mark_emails' ), array( 'strong' => array() ) ); ?></p>
<?php submit_button( __( 'Test', 'wp_post_mark_emails' ), 'primary', 'wp_post_mark_emails_test_send', FALSE ); ?>
</form>