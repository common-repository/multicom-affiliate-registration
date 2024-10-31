<?php
/*
 * Plugin Name: MultiCOM Affiliate Tracker
 * Version: 1.4.0
 * Plugin URI: https://wordpress.mcomsolutions.biz/wordpress_generic/home/
 * Description: This plugins enables your site to register referrals and recognizes registration links.
 * Author: MCOM Solutions, LLC.
 * Author URI: https://www.mcomsolutions.com/
 * Text Domain: wp-multicom-referrals
 * Domain Path: /languages/
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package MultiCOMAffiliateRegistration
 * @author MCOM Solutions, LLC.
 * @since 1.0.0
**/

if (!defined('ABSPATH')) exit;

// Load the constant variables
require_once('utils/class-wp-multicom-referrals-const.php');

// Load plugin class files
require_once('includes/class-wp-multicom-referrals.php');
require_once('includes/class-wp-multicom-referrals-settings.php');
require_once('includes/class-wp-multicom-referrals-actions.php');
require_once('includes/class-wp-multicom-referrals-filters.php');
require_once('includes/class-wp-multicom-referrals-shortcode.php');
require_once('includes/class-wp-multicom-referrals-activator.php');
require_once('includes/class-wp-multicom-referrals-deactivator.php');

// Load plugin libraries
require_once('includes/lib/class-wp-multicom-referrals-admin-api.php');
require_once('includes/lib/class-wp-multicom-referrals-post-type.php');
require_once('includes/lib/class-wp-multicom-referrals-taxonomy.php');

/**
 * Returns the main instance of WP_MultiCOM to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WP_MultiCOM
 */
function WP_MultiCOM() {
  $instance = WP_MultiCOM::instance(__FILE__);

  // Adding widget multicom referral info
  if (!class_exists('WP_MultiCOM_Referral_Widget')) {
    require_once(plugin_dir_path(__FILE__) . 'wp-multicom-info-widget.php');
  }

  // The corresponding classes are assigned
  $instance->settings = WP_MultiCOM_Settings::instance($instance);
  $instance->actions = WP_MultiCOM_Actions::instance($instance);
  $instance->filters = WP_MultiCOM_Filters::instance($instance);
  $instance->shortcode = WP_MultiCOM_Shortcode::instance($instance);

  return $instance;
}

WP_MultiCOM();
