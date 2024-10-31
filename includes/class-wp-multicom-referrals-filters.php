<?php

if (!defined('ABSPATH')) exit;

class WP_MultiCOM_Filters
{

  /**
   * The single instance of WP_MultiCOM_Filters.
   * @var 	object
   * @access  private
   * @since 	1.0.0
   */
  private static $_instance = null;

  /**
   * The main plugin object.
   * @var 	object
   * @access  public
   * @since 	1.0.0
   */
  public $parent = null;

  public function __construct($parent)
  {
    $this->parent = $parent;

    add_filter("tiny_mce_before_init", array($this, 'before_init_tinymce'));
    add_filter('manage_users_columns', array($this, 'new_modify_user_table'));
    add_filter('manage_users_custom_column', array($this, 'new_modify_user_table_row'), 10, 3);
    add_filter('body_class', array($this, 'add_custom_body_class'), 10, 1);

    if (class_exists('WooCommerce')) {
      add_filter('manage_edit-shop_order_columns', array($this, 'wc_new_order_column'));
      add_filter('woocommerce_registration_errors', array($this, 'validate_registration_fields'), 10, 3);
    } else if (class_exists('LifterLMS')) {
      add_filter('lifterlms_user_registration_data', array($this, 'validate_registration_fields'), 10, 3);
      add_filter('lifterlms_user_update_data', array($this, 'validate_registration_fields'), 10, 3);
    }
  }

  /**
   * This function add a custom body class
   *
   * @param array $classes
   * @return array
   */
  public function add_custom_body_class($classes) {
    if (isset($_SESSION)) {
      if (isset($_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral'])) {
        $classes[] = 'multicom-is-referral';
      }
    }

    return $classes;
  }

  /**
   * Filters the display output of custom columns in the Users list table.
   *
   * @param string $val
   * @param string $column_name
   * @param string $user_id
   * @return string
   */
  public function new_modify_user_table_row($val, $column_name, $user_id) {
    if ($column_name == 'ref_info') {
      $data = '';
      $ref_name = get_user_meta($user_id, 'refname', true);
      $data .= sprintf( __('Name: <strong>%1$s</strong><br/>', WP_MultiCOM_Constant::$TEXT_DOMAIN), $ref_name);

      $sponsor = get_user_meta($user_id, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by', true);
      $sponsor_id = get_user_meta($user_id, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by_id', true);

      $data .= sprintf( __('Sponsor: <em>%1$s</em><br/>', WP_MultiCOM_Constant::$TEXT_DOMAIN), ($sponsor) ? $sponsor . "(" . $sponsor_id . ")" : '');
      $args = array(
        'meta_query' => array(
          array(
            'key'   => WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by_id',
            'value' => $user_id,
          )
        ),
        'count_total' => true
      );

      $user_query = new WP_User_Query($args);
      $data .= sprintf( __('Enrollments: <strong>%1$d</strong>', WP_MultiCOM_Constant::$TEXT_DOMAIN), $user_query->get_total());

      return $data;
    }

    return $val;
  }

  /**
   * Assign the column name into the user list
   *
   * @param array $column
   * @return array
   */
  public function new_modify_user_table($column) {
    $column['ref_info'] = __('Referral Information', WP_MultiCOM_Constant::$TEXT_DOMAIN);

    return $column;
  }

  /**
   * Check if a referral name already was assigned
   *
   * @param string $ref_name
   * @param string $user_id
   * @return boolean
   */
  public function check_if_ref_name_exist($ref_name, $user_id) {
    $nicks = get_users(['meta_value' => $ref_name, 'meta_key' => 'refname']);
    $exist = false;

    foreach ($nicks as $nick) {
      if ($nick->ID != $user_id) {
        $exist = true;
      }
    }

    return $exist;
  }

  /**
   * It serves to validate the custom fields that have been entered into the Woocommerce registration form
   *
   * @param object $errors - Array of errors
   * @param boolean $data
   * @param string $user_email
   * @return array
   */
  public function validate_registration_fields($errors, $data, $screen) {
    if (isset($_POST['refname'])) {
      $meta_value = $_POST['refname'];
      $pattern = "/^[a-zA-Z\d-]+$/";
      if (!preg_match($pattern, $meta_value)) {
        $errors->add('refname', __('Your Referral Name cannot contain spaces nor special characters.', WP_MultiCOM_Constant::$TEXT_DOMAIN));
      }

      if ($this->check_if_ref_name_exist($_POST['refname'], $data['user_id'])) {
        $errors->add('refname', __('The <strong>Referral Name</strong> you selected already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN));
        $errors->add('<strong>' . esc_html($meta_value) . '</strong> ' . __(' already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN), 'error');
      }

      if (class_exists('WooCommerce')) {
        if (isset($_POST['billing_first_name']) && empty($_POST['billing_first_name'])) {
          $errors->add('billing_first_name_error', __('<strong>Error</strong>: First name is required!', WP_MultiCOM_Constant::$TEXT_DOMAIN));
        }
        if (isset($_POST['billing_last_name']) && empty($_POST['billing_last_name'])) {
          $errors->add('billing_last_name_error', __('<strong>Error</strong>: Last name is required!.', WP_MultiCOM_Constant::$TEXT_DOMAIN));
        }
      }
    }

    return $errors;
  }

  /**
   * Fired before init the Editor
   *
   * @param array $init_array
   * @return string
   */
  public function before_init_tinymce($init_array = array()) {
    $stylesheet = $this->get_stylesheet();

    if (isset($init_array['content_css'])) {
      $init_array['content_css'] .= !empty($stylesheet) ? ',' . $stylesheet : '';
    }
    $init_array['remove_trailing_brs'] = false;
    $init_array['extended_valid_elements'] = 'img[src|onclick|class|title|alt],a[href|onclick|title],button[onclick|type],script[src|type|language]';

    return $init_array;
  }

  /**
   * Return the custom CSS link according to the user configuration
   *
   * @return string
   */
  public function get_stylesheet() {
    $stylesheet = trim(get_option('ref_info_css'));
    if (isset($stylesheet)) {
      if (!empty($stylesheet)) {
        return $stylesheet;
      }
    }

    return "";
  }

  /**
   * Main WP_MultiCOM_Filters Instance
   *
   * Ensures only one instance of WP_MultiCOM_Filters is loaded or can be loaded.
   *
   * @since 1.0.0
   * @static
   * @see WP_MultiCOM()
   * @return Main WP_MultiCOM_Filters instance
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
