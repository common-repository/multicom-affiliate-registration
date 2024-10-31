<?php

if (!defined('ABSPATH')) exit;

class WP_MultiCOM_Actions
{

  /**
   * The single instance of WP_MultiCOM_Actions.
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

    add_action('init', array($this, 'init'), 1);
    add_action('user_register', array($this, 'user_register'));
    add_action('template_redirect', array($this, 'check_referral_link'));

    // Show user info
    add_action('show_user_profile', array($this, 'additional_user_fields'));
    // Edit user info by admin
    add_action('edit_user_profile', array($this, 'additional_user_fields_admin'));

    // Validate admin user editions
    add_action('user_profile_update_errors', array($this, 'crf_user_profile_update_errors'), 10, 3);

    add_action('personal_options_update', array($this, 'save_additional_user_meta'));
    add_action('edit_user_profile_update', array($this, 'save_additional_user_meta'));

    add_action('manage_shop_order_posts_custom_column', array($this, 'shop_order_posts_custom_column'));

    // Add custom ajax for load user replicated info
    add_action('wp_ajax_nopriv_get_replicated_info', array($this, 'get_replicated_info'));
    add_action('wp_ajax_get_replicated_info', array($this, 'get_replicated_info'));

    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
      add_action('woocommerce_save_account_details', array($this, 'save_additional_user_meta'));
      add_action('woocommerce_edit_account_form_tag', array($this, 'make_uploadable_form'));
      add_action('woocommerce_edit_account_form', array($this, 'additional_user_fields'));
      add_action('woocommerce_save_account_details', array($this, 'save_user_custom'));
      add_action('woocommerce_save_account_details_errors', array($this, 'validate_my_account_edit'),  10, 2);

      // When order is set, attach referral id if available
      add_action('woocommerce_checkout_update_order_meta', array($this, 'add_referral_order'), 10, 2);

      add_action('woocommerce_email_before_order_table', array($this, 'action_woocommerce_email_before_order_table'), 10, 4);
      add_action('woocommerce_register_form_start', array($this, 'add_name_woo_account_registration'));
      add_action('woocommerce_created_customer', array($this, 'save_name_fields'));
    } else if (class_exists('LifterLMS')) {
      add_action('lifterlms_order_complete', array($this, 'add_order_complete'), 10, 1);
      add_action('lifterlms_user_updated', array($this, 'save_additional_user_meta'), 10, 3);
    }
  }

  /**
   * Return the replicated information by ajax
   *
   * @return string
   */
  public function get_replicated_info() {
    echo $this->parent->get_widget_referral_info();
    wp_die();
  }

  /**
   * When an order was completed, change the role of the user
   *
   * @param int $order_id
   * @return void
   */
  public function add_order_complete($order_id) {
    $order = new LLMS_Order($order_id);

    if (isset($order)) {
      $user_id = $order->get('user_id');
      $membership_dash = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX.'membershipsdash');

      if (!empty($membership_dash)) {
        $membership_array = explode(",", $membership_dash);
        $product_id = $order->get( 'product_id' );
        $slug = get_post_field('post_name', $product_id);

        if (in_array($slug, $membership_array)) {
          $wp_user_object = new WP_User($user_id);
          $wp_user_object->add_role(WP_MultiCOM_Constant::$FIELD_PREFIX . 'affiliate');
        }
      }
    }
  }

  /**
   * This function check if a page exist by the slug name
   *
   * @param string $slug
   * @return boolean
   */
  public function the_slug_exists($slug) {
    global $wpdb;

    if ($wpdb->get_row("SELECT post_name FROM wp_posts WHERE post_name = '" . $slug . "'", 'ARRAY_A')) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * It is responsible for updating / saving custom fields, when a customer registers
   *
   * @param string $customer_id
   * @return void
   */
  public function save_name_fields($customer_id) {
    if (isset($_POST['billing_first_name'])) {
      update_user_meta($customer_id, 'billing_first_name', $this->validate_fields($_POST, 'billing_first_name'));
      update_user_meta($customer_id, 'first_name', $this->validate_fields($_POST, 'billing_first_name'));
    }

    if (isset($_POST['billing_last_name'])) {
      update_user_meta($customer_id, 'billing_last_name', $this->validate_fields($_POST, 'billing_last_name'));
      update_user_meta($customer_id, 'last_name', $this->validate_fields($_POST, 'billing_last_name'));
    }
  }

  /**
   * Add custom field at start of the registration form
   *
   * @return void
   */
  public function add_name_woo_account_registration()
  {
    ?>
      <p class="form-row form-row-first validate-required">
        <label for="reg_billing_first_name">
          <?php _e('First name', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
          <abbr class="required" title="<?php _e('Required', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>">*</abbr>
        </label>
        <span class="woocommerce-input-wrapper">
          <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if (!empty($_POST['billing_first_name'])) esc_attr_e($_POST['billing_first_name']); ?>" />
        </span>
      </p>
      <p class="form-row form-row-last validate-required">
        <label for="reg_billing_last_name">
          <?php _e('Last name', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
          <abbr class="required" title="<?php _e('Required', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>">*</abbr>
        </label>
        <span class="woocommerce-input-wrapper">
          <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if (!empty($_POST['billing_last_name'])) esc_attr_e($_POST['billing_last_name']); ?>" />
        </span>
      </p>
    <?php
  }

  /**
   * This functions and hook adds the Store name before the detail table on emails (new order email)
   */
  public function action_woocommerce_email_before_order_table($order, $sent_to_admin, $plain_text, $email) {
    $order_id = $order->get_id();
    $store = get_post_meta($order_id, WP_MultiCOM_Constant::$FIELD_PREFIX . "referred_by", true);
    $store_id = get_post_meta($order_id, WP_MultiCOM_Constant::$FIELD_PREFIX . "referred_by_id", true);
    echo "<h2>Store: " . $store . " (#" . $store_id . ")</h2>";
  }

  /**
   * Adds 'Profit' column content to 'Orders' page immediately after 'Total' column.
   *
   * @param string[] $column name of column being displayed
   */
  public function shop_order_posts_custom_column($column) {
    global $post;

    if ('order_sponsor' === $column) {
      $order = wc_get_order($post->ID);
      $order_id = $order->get_id();
      $sponsor_order = get_post_meta($order_id, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by', true);
      $sponsor_order_id = get_post_meta($order_id, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by_id', true);
      echo $sponsor_order . " (ID " . $sponsor_order_id . ")";
    }
  }

  /**
   * It is triggered when the order is checked out, this to update the referenced fields of the order
   *
   * @param string $order_id
   * @return void
   */
  public function add_referral_order($order_id, $posted) {
    $order = wc_get_order($order_id);
    $key = WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by_id';
    $value = WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral_id';

    $order->update_meta_data($key, $this->validate_fields($_SESSION, $value, 'text', '0'));

    $key = WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by';
    $value = WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral';

    $order->update_meta_data($key, $this->validate_fields($_SESSION, $value, 'text', 'root'));
    $order->save();
  }

  /**
   * It's responsible to validate the custom fields
   *
   * @param array $args
   * @param object $user
   * @return void
   */
  public function validate_my_account_edit(&$args, &$user) {
    if (isset($_POST['refname'])) {
      $pattern = "/^[a-zA-Z\d-]+$/";
      if (!preg_match($pattern, $_POST['refname'])) {
        wc_add_notice('<strong>' . _e('Your Referral Name', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '</strong> ' . __(' cannot contain spaces nor special characters.', WP_MultiCOM_Constant::$TEXT_DOMAIN), 'error');
      } else if (empty($_POST['refname'])) {
        wc_add_notice('<strong>' . _e('Your Referral Name', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '</strong> ' . __('is a required field.', WP_MultiCOM_Constant::$TEXT_DOMAIN), 'error');
      }

      // Validate if unique
      if ($this->check_if_ref_name_exist($_POST['refname'], $user->ID)) {
        wc_add_notice('<strong>' . esc_html($_POST['refname']) . '</strong> ' . __(' already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN), 'error');
      }
    }
  }

  /**
   * It is responsible for adding custom attributes to the account edit form
   *
   * @return void
   */
  public function make_uploadable_form() {
    echo ' enctype="multipart/form-data"';
  }

  /**
   * This function it's responsible to prepare and save the custom fields
   *
   * @param string $user_id
   * @return void
   */
  public function save_user_custom($user_id) {
    if ($user_id == false) {
      return false;
    }

    if ($this->check_if_ref_name_exist($_POST['refname'], $user_id)) {
      echo  __('The <strong>Referral Name</strong> you selected already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN);
      echo '<strong>' . esc_html($_POST['refname']) . '</strong> ' . __(' already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN);
      return false;
    }
    update_user_meta($user_id, 'refname', $this->validate_fields($_POST, 'refname'));

    // If the upload field has a file in it
    if (isset($_FILES['profile_photo'])) {
      if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
      }

      // Get the type of the uploaded file. This is returned as "type/extension"
      $arr_file_type = wp_check_filetype(basename($_FILES['profile_photo']['name']));
      $uploaded_file_type = $arr_file_type['type'];
      // Set an array containing a list of acceptable formats
      $allowed_file_types = array('image/jpg', 'image/jpeg', 'image/gif', 'image/png');
      // If the uploaded file is the right format
      if (in_array($uploaded_file_type, $allowed_file_types)) {
        // Options array for the wp_handle_upload function. 'test_upload' => false
        $upload_overrides = array('test_form' => false);

        // Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array
        $uploaded_file = wp_handle_upload($_FILES['profile_photo'], $upload_overrides);
        // If the wp_handle_upload call returned a local path for the image
        if (isset($uploaded_file['file'])) {

          // The wp_insert_attachment function needs the literal system path, which was passed back from wp_handle_upload
          $file_name_and_location = $uploaded_file['file'];

          // Generate a title for the image that'll be used in the media library
          $file_title_for_media_library = 'your title here';

          // Set up options array to add this file as an attachment
          $attachment = array(
            'post_mime_type' => $uploaded_file_type,
            'post_title' => 'Uploaded image ' . addslashes($file_title_for_media_library),
            'post_content' => '',
            'post_status' => 'inherit'
          );

          // Run the wp_insert_attachment function. This adds the file to the media library and generates the thumbnails. If you wanted to attch this image to a post, you could pass the post id as a third param and it'd magically happen.
          $attach_id = wp_insert_attachment($attachment, $file_name_and_location);
          require_once(ABSPATH . "wp-admin" . '/includes/image.php');
          $attach_data = wp_generate_attachment_metadata($attach_id, $file_name_and_location);
          wp_update_attachment_metadata($attach_id,  $attach_data);

          update_user_meta($user_id, 'profile_photo', $attach_id);
        }
      }
    }
  }

  /**
  * Saves additional user fields to the database
  */
  public function save_additional_user_meta($user_id) {
    if (!empty($_POST['refname'])) {
      if ($this->check_if_ref_name_exist($_POST['refname'], $user_id)) {
        wp_die(
          printf(__('The <strong>Referral Name</strong> you selected already exists! Please select another one.<br/><strong>%s</strong> already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN), esc_html($_POST['refname'])),
          'Plugin dependency check',
          array('back_link' => true)
        );
      }

      update_user_meta($user_id, 'refname', $this->validate_fields($_POST, 'refname'));
    }

    if (!empty($_POST['billing_phone'])) {
      update_user_meta($user_id, 'billing_phone', $this->validate_fields($_POST, 'billing_phone'));
    }

    $range = range(1, 5);
    foreach ($range as $n) {
      $name = "custom" . $n;
      $type = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . $name . "_type", "text");
      if ($type == "checkbox") {
        $value = $this->validate_fields($_POST, $name, 'text', '0');
      } else {
        $value = $this->validate_fields($_POST, $name);
      }

      update_user_meta($user_id, $name, $value);
    }
  }

  /**
   * This function it's responsible to validates the custom fields
   *
   * @param object $errors
   * @param object $update
   * @param object $user
   * @return object
   */
  public function crf_user_profile_update_errors($errors, $update, $user)
  {
    if (isset($_POST['refname'])) {
      $pattern = "/^[a-zA-Z0-9\-]+$/";
      if (!preg_match($pattern, $_POST['refname'])) {
        $errors->add('refname', __('Your Referral Name cannot contain spaces nor special characters.', WP_MultiCOM_Constant::$TEXT_DOMAIN));
      }

      // Check if refname / referral name is valid
      if ($_POST['refname'] != '') {
        if ($this->check_if_ref_name_exist($_POST['refname'], $user->ID)) {
          $errors->add('refname', __('The <strong>Referral Name</strong> you selected already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN));
          $errors->add('<strong>' . esc_html($_POST['refname']) . '</strong> ' . __(' already exists! Please select another one.', WP_MultiCOM_Constant::$TEXT_DOMAIN), 'error');
        }
      } else {
        $errors->add('refname', __('The <strong>Referral Name</strong> cannot be blank!', WP_MultiCOM_Constant::$TEXT_DOMAIN));
      }

      return $errors;
    }

    return array();
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
   * Use to print a customs fields into the user form
   *
   * @param object $user
   * @return void
   */
  public function additional_user_fields_admin($user)
  {
?>
    <h3><?php _e('Affiliate Info', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></h3>
    <table class="form-table" role="presentation">
      <tbody>
        <tr class="user-refname-wrap">
          <th>
            <label for="refname"><?php _e('Referral Name', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></label>
          </th>
          <td>
            <input type="text" class="regular-text" name="refname" id="refname" value="<?php echo esc_html(get_the_author_meta('refname', $user->ID)); ?>" />
            <p class="description"><?php _e('No spaces or special characters', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></p>
            <div class="wp-refname hide-if-js" style="display: none;"></div>
          </td>
        </tr>
        <tr class="user-nickname-wrap">
          <th>&nbsp;</th>
          <td>
            <p class="description" style="text-align: center;">
              <code>
                <span id="refLnk"><?= home_url() ?>/<span id="referralname" style="font-weight: bold; color: #f00;"><?= (isset($_POST["refname"])) ? $_POST["refname"] : "YourReferralName" ?></span></span>
              </code>
              <a href="#" onclick="onClickCopyLink(event);"><?php _e('Copy to clipboard', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></a><br />
              <?php _e('(Use this link to share the good news with others)', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
            </p>
          </td>
        </tr>
        <?php
        // Generate custom fields
        $range = range(1, 5);
        foreach ($range as $n) :
          $name = "custom" . $n;
          $label = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . $name . "_label", "Custom " . $n);
          $type = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . $name . "_type", "text");
          $value = get_the_author_meta($name, $user->ID);
        ?>
          <tr id="custom-input_<?= $name ?>" class="store-wrap">
            <th><label for="custom_<?= $n ?>"><?= $label ?></label></th>
            <td>
              <?php if ($type == "checkbox") : ?>
                <input type="checkbox" class="regular-text" name="<?= $name ?>" id="id_<?= $name ?>" <?php checked('1' == trim($value)); ?> value="1" />
              <?php else : ?>
                <input type="text" class="regular-text" name="<?= $name ?>" id="id_<?= $name ?>" value="<?= $value ?>" />
              <?php endif; ?>
            </td>
          </tr>
        <?php
        endforeach;
        ?>
      </tbody>
    </table>
<?php
    $this->print_script_referral();
  }

  /**
   * Adds additional user fields
   * more info: http://justintadlock.com/archives/2009/09/10/adding-and-using-custom-user-profile-fields
   */
  public function additional_user_fields()
  {
    $id = get_current_user_id();
    $pid = get_user_meta($id, 'profile_photo', true);
    $img = wp_get_attachment_image($pid);
  ?>
    <fieldset>
      <legend><?php _e('Referral Information', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></legend>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="billing-phone">
          <?php _e('Mobile', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
          <abbr class="required" title="<?php _e('Required', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>">*</abbr>
        </label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="billing_phone" value="<?php if (!empty(get_user_meta($id, 'billing_phone', true))) esc_attr_e(get_user_meta($id, 'billing_phone', true)); ?>" />
      </p>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="refname">
          <?php _e('Your referral name', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
          <abbr class="required" title="<?php _e('Required', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>">*</abbr>
        </label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="refname" id="refname" value="<?php if (!empty(get_user_meta($id, 'refname', true))) esc_attr_e(get_user_meta($id, 'refname', true)); ?>" />
        <span><em><?php _e('No spaces or special characters', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></em></span>
      </p>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" style="text-align: center;">
        <code>
          <span id="refLnk"><?= home_url() ?>/<span id="referralname" style="font-weight: bold; color: #f00;"><?= (isset($_POST["refname"])) ? $_POST["refname"] : "YourReferralName" ?></span></span>
        </code>
        <a href="#" onclick="onClickCopyLink(event);"><?php _e('Copy to clipboard', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?></a><br />
        <?php _e('(Use this link to share the good news with others)', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
      </p>

      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="upload-profile">
          <?php _e('Your profile image', WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>
        </label>
        <?php
          if (isset($img) && !empty($img)) {
            echo $img;
            echo '<br />';
          }
        ?>
        <input name="profile_photo" type="file" class="woocommerce-Input woocommerce-Input--text input-text" id="profile_photo" value="" formenctype="multipart/form-data" />
      </p>
    </fieldset>
<?php
    $this->print_script_referral();
  }

  public function print_script_referral() {
?>
    <input type="text" id="cpyInput" style="transform: translateX(-100%) translateY(-100%); position: fixed; left: 0; top: 0;" />
    <script type="text/javascript">
      function setNickName() {
        var sNickName = jQuery("#refname").val().trim();
        if (sNickName.length == 0) {
          sNickName = "YourReferralName";
        }

        jQuery("#referralname").text(sNickName);
        if (jQuery("#cpyInput").length) {
          jQuery("#cpyInput").val(jQuery("#refLnk").text());
        }
      }

      jQuery("#refname").on("keyup", setNickName);
      setNickName();

      function onClickCopyLink(event) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }

        var sText = document.getElementById("refLnk").innerText,
          oCopy = document.getElementById("cpyInput");

        oCopy.value = sText;
        oCopy.select();
        document.execCommand("copy");
        console.log('<?php _e("Copied to clipboard", WP_MultiCOM_Constant::$TEXT_DOMAIN); ?>');
      }
    </script>
<?php
  }

  /**
   * It is triggered before performing a redirect, it is used to verify if it is trying to access the replica of a user
   */
  public function check_referral_link()
  {
    global $wp;
    $current_slug = add_query_arg(array(), $wp->request);

    if (!empty($current_slug)) {
      if (is_404()) {
        $url = explode("/", $current_slug);
        $ref = trim($url[0]);

        // Huevo de pascua para debug
        $debug_ref_name = false;
        if (substr($ref, -6) == '_debug') {
          $debug_ref_name = true;
          $ref = substr($ref, 0, strlen($ref) - 6);
        }

        // Busco si existe algún registro con dicho refname
        $user_query = new WP_User_Query(array('meta_key' => 'refname', 'meta_value' => $ref, 'fields' => 'all'));
        $users = $user_query->get_results();

        if (!empty($users)) {
          $pid = get_user_meta($users[0]->ID, 'profile_photo', true);
          $img = wp_get_attachment_url($pid);

          if (isset($img) && !empty($img)) {
            $avatar = $img;
          } else {
            $avatar = get_avatar_url($users[0]->ID);
          }

          if(session_start() == false){
            session_destroy();
            session_start();
          }

          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral_id'] = $users[0]->ID;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral'] = $ref;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['referral_name'] = $users[0]->refname;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['first_name'] = $users[0]->first_name;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['last_name'] = $users[0]->last_name;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['phone'] = $users[0]->billing_phone;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['email'] = $users[0]->user_email;
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['referral_id'] = $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral_id'];
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data']['photo'] = $avatar;

          if ($debug_ref_name) {
            echo "<pre>";
            echo print_r($_SESSION, true) . "<br/>";
            echo print_r($users, true) . "<br/>";
            echo "Is session started: ";
            var_dump($this->is_session_started());
            echo "Session ID: " . print_r(session_id()) . "<br/>";
            echo "Session Status: " . session_status() . "<br/>";
            $session_path = session_save_path();
            echo "Session save path: " . $session_path  . "<br/>";
            echo sprintf('File permissions save path: %o <br/>', fileperms($session_path));
            echo "</pre>";
            die();
          }

          unset($url[0]);
          $url = implode("/", $url);
          $redirect_after = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . "redirect_slug", "");
          wp_redirect(home_url() . "/" . $redirect_after);
          exit;
        }else{
          unset($_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'error_replicate']);
          $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'error_replicate'] = $ref;
        }
      }
    }
  }

  /**
   * Fires when the wordpress initialize
   */
  public function init()
  {
    $this->start_session();
    $this->create_become_affiliate_page();
  }

  /**
   * Initialize the session
   */
  public function start_session()
  {
    if ($this->is_session_started() === false) {
      session_start();
    }
  }

  /**
   * Crate the become affiliate page automatically
   */
  public function create_become_affiliate_page()
  {
    $create_page = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . "enable_regpage", 'off');

    if ($create_page == 'on') {
      $blog_page_title = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . "page_title", __('Become an Affiliate', WP_MultiCOM_Constant::$TEXT_DOMAIN)); //May come from settings
      $blog_page_content = '[woocommerce_multicom_registration]';
      $blog_page_check = get_page_by_title($blog_page_title);
      $blog_menu_id = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . "register_targetmenu", 2);
      $blog_page = array(
        'post_type' => 'page',
        'post_title' => $blog_page_title,
        'post_content' => $blog_page_content,
        'post_status' => 'publish',
        'post_author' => 1,
        'post_slug' => sanitize_title($blog_page_title)
      );

      if (!isset($blog_page_check->ID) && !$this->the_slug_exists('become-affiliate')) {
        $blog_page_id = wp_insert_post($blog_page);
        $this->add_page_to_menu($blog_page_id, $blog_page_title, $blog_menu_id, 0);
      }
    }
  }

  /**
   * Add a custom page to a the wordpress nav menu
   *
   * @param int $page_id - Page ID
   * @param string $page_title - Page title
   * @param int $menu_id - Menu ID
   * @param int $parent - (Optional) Menu item parent ID
   */
  public function add_page_to_menu($page_id, $page_title, $menu_id, $parent = 0) {
    wp_update_nav_menu_item(
      $menu_id,
      0,
      array(
        'menu-item-title' => $page_title,
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
        'menu-item-parent-id' => $parent
      )
    );
  }

  /**
   * Fires when a user register, is used to save a customs fields
   *
   * @param string $user_id
   * @return void
   */
  public function user_register($user_id)
  {
    $referral_id = $this->validate_fields($_SESSION, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral_id', 'text', '0'); //Ask config who has to be the default referral
    $referral = $this->validate_fields($_SESSION, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral', 'text', 'root');


    if($referral_id == '0') {
      $default_enrollment = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX.'default_enrollment');

      if(isset($default_enrollment) && !empty($default_enrollment)){
        $user_query = new WP_User_Query(array('meta_key' => 'refname', 'meta_value' => $default_enrollment, 'fields' => 'all'));
        $users = $user_query->get_results();

        if (!empty($users)) {
          $referral_id = $users[0]->ID;
          $referral = $default_enrollment;
        }
      }
    }

    update_user_meta($user_id, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by', $referral);
    update_user_meta($user_id, WP_MultiCOM_Constant::$FIELD_PREFIX . 'referred_by_id', $referral_id);
    if (class_exists('LifterLMS')) {
      if (isset($_POST['refname'])) {
        update_user_meta($user_id, 'refname', $this->validate_fields($_POST, 'refname'));
      }
    } else {
      update_user_meta($user_id, 'first_name', $this->validate_fields($_POST, 'billing_first_name'));
      update_user_meta($user_id, 'last_name', $this->validate_fields($_POST, 'billing_last_name'));
      update_user_meta($user_id, 'refname', $this->validate_fields($_POST, 'refname'));
      update_user_meta($user_id, 'billing_phone', $this->validate_fields($_POST, 'billing_phone'));

      // Add role affiliate
      if (isset($_POST['refname'])) {
        //Si el registro se está haciendo desde la pantalla del carrito donde solo te registras como cliente,
        // dejamos el role de customer de woocommerce. No va a ser un afiliado

        $wp_user_object = new WP_User($user_id);
        $wp_user_object->set_role(WP_MultiCOM_Constant::$FIELD_PREFIX . 'affiliate');
      }
    }
  }

  /**
   * This function is responsible to validate fields
   *
   * @param array $data
   * @param string $key
   * @param string $type
   * @param string $default
   * @return string
   */
  public function validate_fields($data, $key, $type = 'text', $default = '') {
    $return = $default;
    if (isset($data[$key])) {
      $value = $data[$key];

      switch ($type) {
        case 'url':
          $return = esc_url_raw($value);
          break;
        case 'email':
          $return = sanitize_email($value);
          break;
        default:
          $return = sanitize_text_field($value);
          break;
      }
    }

    return $return;
  }

  /**
   * Check if session is started
   *
   * @return boolean
   */
  public function is_session_started() {
    if ( php_sapi_name() !== 'cli' ) {
      if ( version_compare(phpversion(), '5.4.0', '>=') ) {
        return session_status() === PHP_SESSION_ACTIVE ? true : false;
      } else {
        return session_id() === '' ? false : true;
      }
    }

    return false;
  }

  /**
   * Main WP_MultiCOM_Actions Instance
   *
   * Ensures only one instance of WP_MultiCOM_Actions is loaded or can be loaded.
   *
   * @since 1.0.0
   * @static
   * @see WP_MultiCOM()
   * @return Main WP_MultiCOM_Actions instance
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
   * Unserializing instances of this class is forbidden.
   *
   * @since 1.0.0
   */
  public function __wakeup()
  {
    _doing_it_wrong(__FUNCTION__, __('Cheating huh?'), WP_MultiCOM_Constant::$VERSION);
  } // End __wakeup()
}
