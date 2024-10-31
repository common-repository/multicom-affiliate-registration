<?php

/**
 * Registration form.
 *
 * @author			MultiCOM
 * @package 		wp-multicom
 * @version			1.0.0
 */

if (!defined('ABSPATH')) exit;

wp_enqueue_script('wc-password-strength-meter');

if (class_exists('WP_MultiCOM_Constant')) {
	$text_domain = WP_MultiCOM_Constant::$TEXT_DOMAIN;
} else {
	$text_domain = '';
}
?>

<div class="woocommerce-checkout">
	<div class="registration-form woocommerce">
		<?php wc_print_notices(); ?>

		<h3><?php _e('Please fill below information', $text_domain); ?></h3>
		<form method="post" class="register woocommerce-checkout">

			<div class="col2-set" style="width: 100%;">
				<div class="col-12 p-0">
					<div class="woocommerce-affiliate-fields">
						<p class="form-row form-row-first validate-required">
							<label for="billing_first_name">
								<?php _e('First name', $text_domain); ?>
								<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
							</label>
							<span class="woocommerce-input-wrapper">
								<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_first_name" id="billing_first_name" value="<?php if (!empty($_POST['billing_first_name'])) esc_attr_e($_POST['billing_first_name']); ?>" required />
							</span>
						</p>
						<p class="form-row form-row-last validate-required">
							<label for="billing_last_name">
								<?php _e('Last name', $text_domain); ?>
								<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
							</label>
							<span class="woocommerce-input-wrapper">
								<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_last_name" id="billing_last_name" value="<?php if (!empty($_POST['billing_last_name'])) esc_attr_e($_POST['billing_last_name']); ?>" required/>
							</span>
						</p>

						<p class="form-row form-row-wide validate-required validate-phone">
							<label for="billing_phone">
								<?php _e('Mobile', $text_domain); ?>
								<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
							</label>
							<span class="woocommerce-input-wrapper">
								<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="billing_phone" value="<?php if (!empty($_POST['billing_phone'])) esc_attr_e($_POST['billing_phone']); ?>" required/>
							</span>
						</p>

						<?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>

							<p class="form-row form-row-wide validate-required">
								<label for="reg_username">
									<?php _e('Username', $text_domain); ?>
									<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
								</label>
								<span class="woocommerce-input-wrapper">
									<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" value="<?php if (!empty($_POST['username'])) echo esc_attr($_POST['username']); ?>" required />
								</span>
							</p>

						<?php endif; ?>

						<p class="form-row form-row-wide validate-required validate-email">
							<label for="reg_email">
								<?php _e('Email address', $text_domain); ?>
								<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
							</label>
							<span class="woocommerce-input-wrapper">
								<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" value="<?php if (!empty($_POST['email'])) echo esc_attr($_POST['email']); ?>" required />
							</span>
						</p>

						<?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>

							<p class="form-row form-row-wide validate-required">
								<label for="reg_password">
									<?php _e('Password', $text_domain); ?>
									<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
								</label>
								<span class="woocommerce-input-wrapper">
									<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" required />
								</span>
							</p>

						<?php endif; ?>

						<!-- Spam Trap -->
						<div style="<?php echo ((is_rtl()) ? 'right' : 'left'); ?>: -999em; position: absolute;"><label for="trap"><?php _e('Anti-spam', $text_domain); ?></label><input type="text" name="email_2" id="trap" tabindex="-1" autocomplete="off" /></div>

						<p class="form-row form-row-wide validate-required">
							<label for="nikname">
								<?php _e('Your Referral Name', $text_domain); ?>
								<abbr class="required" title="<?php _e('Required', $text_domain); ?>">*</abbr>
							</label>
							<span class="woocommerce-input-wrapper">
								<input type="text" class="input-text" name="refname" id="refname" value="<?php if (!empty($_POST['refname'])) esc_attr_e($_POST['refname']); ?>" required />
								<?php _e('No spaces or special characters', $text_domain); ?>
							</span>
						</p>
						<p class="form-row form-row-wide" style="text-align: center;">
							<code>
								<?= home_url() ?>/<span id="referralname" style="font-weight: bold; color: #f00;"><?= (isset($_POST["refname"])) ? $_POST["refname"] : "YourReferralName" ?></span>
							</code><br/><?php _e('(Use this link to share the good news with others)', $text_domain); ?>
						</p>
						<script type="text/javascript">
							jQuery("#refname").on("keyup", function() {
								var nic = jQuery("#refname").val();
								if (nic.length == 0) {
									nic = "YourReferralName";
								}
								jQuery("#referralname").text(nic);
							});
						</script>

						<?php do_action('woocommerce_simple_registration_form'); ?>

						<p class="form-row form-row-first">
							<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
							<input type="submit" class="button" name="register" value="<?php esc_attr_e('Register', $text_domain); ?>" />
						</p>

						<p class="form-row form-row-last" style="text-align: right;">
							<a class="button" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"><?php esc_html_e('Log in', $text_domain); ?></a>
						</p>

						<?php do_action('woocommerce_register_form_end'); ?>
					</div>
				</div>
			</div>

		</form>
	</div>
</div>