<?php
/**
 * The PostMark email configuration UI file
 *
 * @since 0.1.0
 *
 * @package Branded_Auto_Updates_For_MainWP
 */
?>

<form id="branded-auto-updates-for-mainwp-settings-keys" action="" method="">
<?php wp_nonce_field( 'baufm_settings_nonce' ); ?>

<input type="hidden" name="page" value="branded-auto-updates-for-mainwp">
<input type="hidden" name="tab" value="config">

<table class="form-table">
<tbody>
<tr>
<th scope="row"><?php _e( 'Enable PostMark', 'baufm' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Enable PostMark', 'baufm' ); ?></span></legend>
<label for="enable_postmark">
<input name="enable_postmark" type="checkbox" id="enable_postmark" value="1" <?php checked( get_option( 'baufm_config_enable_post_mark', '' ), 1, true ); ?>>
<?php _e( 'Use PostMark instead of  <code>wp_mail()</code> when sending auto-update notifications from MainWP.', 'baufm' ); ?>
</label>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'PostMark Server Token *', 'baufm' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'PostMark Server Token', 'baufm' ); ?></span></legend>
<label for="server_token">
<input class="regular-text ltr" name="server_token" type="text" id="server_token" value="<?php esc_attr_e( get_option( 'baufm_config_server_token', '' ) ); ?>" placeholder="<?php _e( 'PostMark Server Token', 'baufm' ); ?>" required>
</label>
<p class="description"><?php echo sprintf( wp_kses( __( 'Get your <a href="%s">server token</a> from your PostMark account. This is required for authentication on server specific endpoints.', 'baufm' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/servers' ) ); ?></p>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Sender Signature *', 'baufm' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Settings', 'baufm' ); ?></span></legend>
<label for="sender_signature">
<input class="regular-text ltr" name="sender_signature" type="text" id="sender_signature" value="<?php esc_attr_e( get_option( 'baufm_config_signature', '' ) ); ?>" placeholder="<?php _e( 'sender@example.com', 'baufm' ); ?>" required>
<p class="description"><?php echo sprintf( wp_kses( __( 'Select from one of the validated <a href="%s">sender signatures</a> you have on your PostMark account.', 'baufm' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/signatures' ) ); ?></p>
</label>
</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Email Template', 'baufm' ); ?></th>
<td>
<fieldset>
<legend class="screen-reader-text"><span><?php _e( 'Settings', 'baufm' ); ?></span></legend>
<label for="template">
<input class="regular-text ltr" name="template" type="number" id="template" value="<?php esc_attr_e( get_option( 'baufm_config_template_id', '' ) ); ?>" min="0" placeholder="<?php _e( 'ID', 'baufm' ); ?>">
<p class="description"><?php echo sprintf( wp_kses( __( 'Find the <abr>HTML</abr> template you wish to use in the "Templates" section of <a href="%s">server</a> you wish to use.', 'baufm' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://postmarkapp.com/servers' ) ); ?></p>
<br>
<p><?php _e( 'When using PostMark Templates, the following Mustachio hooks can be used for pulling data into your email template:', 'baufm' );?></p>
<p><code>{{#each plugins_update}}{{name}}{{/each}}</code>, <code>{{#each themes_update}}{{name}}{{/each}}</code>, <code>{{#each core_update}}{{name}}{{/each}}</code>, <code>{{site_url}}</code>, <code>{{from_date}}</code>, <code>{{to_date}}</code></p>
</label>
</fieldset>
</td>
</tr>
</tbody>
</table>
<br>
<p>*<?php echo wp_kses( __( 'Indicates <strong>required</strong> fields.', 'baufm' ), array( 'strong' => array() ) ); ?></p>
<?php submit_button( __( 'Clear', 'baufm' ), 'secondary', 'baufm_config_clear_and_save', false ); ?>  
<?php submit_button( __( 'Save', 'baufm' ), 'primary', 'baufm_config_save', false ); ?>
</form>
