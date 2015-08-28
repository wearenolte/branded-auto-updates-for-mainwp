<form id="branded-auto-updates-for-mainwp-settings-keys" action="" method="">
<?php wp_nonce_field( 'branded_auto_updates_for_mainwp_settings' ); ?>

<input type="hidden" name="page" value="branded-auto-updates-for-mainwp">
<input type="hidden" name="tab" value="config">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Enable PostMark', 'branded_auto_updates_for_mainwp' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'branded_auto_updates_for_mainwp' ); ?></span></legend>
<label for="enable_postmark">
<input name="enable_postmark" type="checkbox" id="enable_postmark" value="1" <?php checked( get_option( 'branded_auto_updates_for_mainwp_config_enable_post_mark', '' ), 1, TRUE ); ?>>
<?php _e( 'Use PostMark instead of  <code>wp_mail()</code> when sending auto-update notifications from MainWP.', 'branded_auto_updates_for_mainwp' ); ?>
</label>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'PostMark Server Token *', 'branded_auto_updates_for_mainwp' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'PostMark Server Token', 'branded_auto_updates_for_mainwp' ); ?></span></legend>
<label for="server_token">
<input class="regular-text ltr" name="server_token" type="text" id="server_token" value="<?php esc_attr_e( get_option( 'branded_auto_updates_for_mainwp_config_server_token', '' ) ); ?>" placeholder="<?php _e( 'PostMark Server Token', 'branded_auto_updates_for_mainwp' ); ?>" required>
</label>
<p class="description"><?php echo sprintf( wp_kses( __( 'Get your <a href="%s">server token</a> from your PostMark account. This is required for authentication on server specific endpoints.', 'branded_auto_updates_for_mainwp' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/servers' ) ); ?></p>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Sender Signature *', 'branded_auto_updates_for_mainwp' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Settings', 'branded_auto_updates_for_mainwp' ); ?></span></legend>
<label for="sender_signature">
<input class="regular-text ltr" name="sender_signature" type="text" id="sender_signature" value="<?php esc_attr_e( get_option( 'branded_auto_updates_for_mainwp_config_signature', '' ) ); ?>" placeholder="<?php _e( 'sender@example.com', 'branded_auto_updates_for_mainwp' ); ?>" required>
<p class="description"><?php echo sprintf( wp_kses( __( 'Select from one of the validated <a href="%s">sender signatures</a> you have on your PostMark account.', 'branded_auto_updates_for_mainwp' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/signatures' ) ); ?></p>
</label>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Email Template', 'branded_auto_updates_for_mainwp' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Settings', 'branded_auto_updates_for_mainwp' ); ?></span></legend>
<label for="template">
<input class="regular-text ltr" name="template" type="number" id="template" value="<?php esc_attr_e( get_option( 'branded_auto_updates_for_mainwp_config_template_id', '' ) ); ?>" min="0" placeholder="<?php _e( 'ID', 'branded_auto_updates_for_mainwp' ); ?>">
<p class="description"><?php echo sprintf( wp_kses( __( 'Find the <abr>HTML</abr> template you wish to use in the "Templates" section of <a href="%s">server</a> you wish to use.', 'branded_auto_updates_for_mainwp' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/servers' ) ); ?></p>
</label>
</fieldset>
</td>
</tr>
</tbody>
</table>
<br>
<p>*<?php echo wp_kses( __( 'Indicates <strong>required</strong> fields.', 'branded_auto_updates_for_mainwp' ), array( 'strong' => array() ) ); ?></p>
<?php submit_button( __( 'Clear', 'branded_auto_updates_for_mainwp' ), 'secondary', 'branded_auto_updates_for_mainwp_config_clear_and_save', false ); ?>  
<?php submit_button( __( 'Save', 'branded_auto_updates_for_mainwp' ), 'primary', 'branded_auto_updates_for_mainwp_config_save', false ); ?>
</form>