<form id="branded-auto-updates-for-mainwp-settings-keys" action="" method="">
<?php wp_nonce_field( 'branded_auto_updates_for_mainwp_settings' ); ?>

<input type="hidden" name="page" value="branded-auto-updates-for-mainwp">
<input type="hidden" name="tab" value="test">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Test Email *', 'branded_auto_updates_for_mainwp' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'branded_auto_updates_for_mainwp' ); ?></span></legend>
<label for="test_email">
<?php $test_email = isset( $_REQUEST['test_email'] ) ? (string) $_REQUEST['test_email'] : ''; ?>
<?php $disabled = ( '' === get_option( 'branded_auto_updates_for_mainwp_config_server_token', '' ) && '' === get_option( 'branded_auto_updates_for_mainwp_config_signature', '' ) ); ?>
<input class="regular-text ltr" name="test_email" type="email" id="test_email" value="<?php esc_attr_e( $test_email ); ?>" placeholder="receiver@example.com" required <?php echo ( $disabled ) ? 'disabled' : ''; ?>>
</label>
<p class="description"><?php _e( 'Send a test email using the settings you provided on the configuration tab.', 'branded_auto_updates_for_mainwp' ); ?></p>
</fieldset>
</td>
</tr>
</tbody>
</table>
<p>*<?php echo wp_kses( __( 'Indicates <strong>required</strong> fields.', 'branded_auto_updates_for_mainwp' ), array( 'strong' => array() ) ); ?></p>
<?php submit_button( __( 'Test', 'branded_auto_updates_for_mainwp' ), 'primary', 'branded_auto_updates_for_mainwp_test_send', FALSE ); ?>
</form>