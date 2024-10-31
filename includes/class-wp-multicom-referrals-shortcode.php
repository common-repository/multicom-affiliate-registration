<?php

if (!defined('ABSPATH')) exit;

class WP_MultiCOM_Shortcode
{
  /**
   * The single instance of WP_MultiCOM_Shortcode.
   * @var 	object
   * @access  private
   * @since 	1.0.0
   */
  private static $_instance = null;

  /**
   * The main plugin object.
   *
   * @var 	object
   * @access  public
   * @since 	1.0.0
   */
  public $parent = null;

  public function __construct($parent)
  {
    $this->parent = $parent;

    add_shortcode('wp_multicom_show_referral', array($this, 'show_multicom_referral'));
    add_shortcode('woocommerce_multicom_registration', array($this, 'registration_template'));
  }

  /**
   * Show the registration page
   *
   * @return string
   */
  public function registration_template() {
    ob_start();
    if (!is_user_logged_in()) :
      $message = apply_filters('woocommerce_registration_message', '');
      if (!empty($message)) :
        wc_add_notice($message);
      endif;
      wc_get_template(
        'registration-form.php',
        array(),
        dirname(plugin_basename($this->parent->file)) . DIRECTORY_SEPARATOR,
        plugin_dir_path($this->parent->file) . 'templates' . DIRECTORY_SEPARATOR
      );
    else :
      $registration_message = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . "after_registration_message");

      if (isset($registration_message) && !empty($registration_message)) :
        echo $registration_message;
      else:
        echo do_shortcode('[woocommerce_my_account]');
      endif;
    endif;

    $return = ob_get_contents();
    ob_end_clean();

    return $return;
  }

  /**
   * Show multicom referral link
   *
   * @return string
   */
  public function show_multicom_referral() {
    return isset($_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral']) ? $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral'] : __('There is no referral link', WP_MultiCOM_Constant::$TEXT_DOMAIN);
  }

  /**
   * Main WP_MultiCOM_Shortcode Instance
   *
   * Ensures only one instance of WP_MultiCOM_Shortcode is loaded or can be loaded.
   *
   * @since 1.0.0
   * @static
   * @see WP_MultiCOM()
   * @return Main WP_MultiCOM_Shortcode instance
   */
  public static function instance($parent)
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self($parent);
    }

    return self::$_instance;
  } // End instance()

  /**
   * Cloning is forbidden.
   *
   * @since 1.0.0
   */
  public function __clone()
  {
    _doing_it_wrong(__FUNCTION__, __('Cheating huh?'), WP_MultiCOM_Constant::$VERSION);
  } // End __clone()

  /**
   * Deserializing instances of this class is forbidden.
   *
   * @since 1.0.0
   */
  public function __wakeup()
  {
    _doing_it_wrong(__FUNCTION__, __('Cheating huh?'), WP_MultiCOM_Constant::$VERSION);
  } // End __wakeup()
}
