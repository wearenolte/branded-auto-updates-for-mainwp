<?php
/**
 * The PostMark email tests UI file
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */
?>

<form id="branded-auto-updates-for-mainwp-settings-keys" action="" method="">
<?php wp_nonce_field( 'baufm_settings_nonce' ); ?>

<input type="hidden" name="page" value="branded-auto-updates-for-mainwp">
<input type="hidden" name="tab" value="test">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Test Email *', 'baufm' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'baufm' ); ?></span></legend>
<label for="test_email">
<?php $test_email = isset( $_REQUEST['test_email'] ) ? (string) $_REQUEST['test_email'] : ''; ?>
<?php $disabled = ( '' === get_option( 'baufm_config_server_token', '' ) && '' === get_option( 'baufm_config_signature', '' ) ); ?>
<input class="regular-text ltr" name="test_email" type="email" id="test_email" value="<?php esc_attr_e( $test_email ); ?>" placeholder="receiver@example.com" required <?php echo ( $disabled ) ? 'disabled' : ''; ?>>
</label>
<p class="description"><?php _e( 'Send a test email using the settings you provided on the configuration tab.', 'baufm' ); ?></p>
</fieldset>
</td>
</tr>
</tbody>
</table>
<p>*<?php echo wp_kses( __( 'Indicates <strong>required</strong> fields.', 'baufm' ), array( 'strong' => array() ) ); ?></p>
<?php submit_button( __( 'Test', 'baufm' ), 'primary', 'baufm_test_send', FALSE ); ?>
</form>