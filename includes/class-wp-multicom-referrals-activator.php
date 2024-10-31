<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WP_MultiCOM
 * @subpackage WP_MultiCOM/includes
 */
class WP_MultiCOM_Activator
{
	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct($file = '')
	{
		// Load plugin environment variables
		$this->file = $file;

		register_activation_hook($this->file, array($this, 'activate'));
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		$my_class = new WP_MultiCOM_Activator();
		$my_class->some_woocommerce_addon_activate();
	}

	/**
	 * It's responsible to clone the customer role, to create a new one Affiliate
	 *
	 * @return void
	 */
	private function clone_role()
	{
		$adm = get_role('customer');
		$adm_cap = array_keys($adm->capabilities); // get customer capabilities
		add_role(WP_MultiCOM_Constant::$FIELD_PREFIX . 'affiliate', 'Affiliate'); // create new role
		$affiliate_role = get_role(WP_MultiCOM_Constant::$FIELD_PREFIX . 'affiliate');

		foreach ($adm_cap as $cap) {
			$affiliate_role->add_cap($cap); // clone customer capabilities to new role
		}
	}

	/**
	 * This function run when the plugin is activated, to check if the woocommerce plugin it's install
	 *
	 * @return void
	 */
	private function some_woocommerce_addon_activate()
	{
		if (!class_exists('WooCommerce')) {
			if (class_exists('LifterLMS')) {
				$this->clone_role();
			} else {
				deactivate_plugins(plugin_basename($this->file));
				wp_die(
					__('Please install and activate WooCommerce.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'Plugin dependency check',
					array('back_link' => true)
				);
			}
		} else {
			$this->clone_role();
		}
	}
}
