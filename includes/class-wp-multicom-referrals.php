<?php

if (!defined('ABSPATH')) exit;

class WP_MultiCOM
{

	/**
	 * The single instance of WP_MultiCOM.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * Actions class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $actions = null;

	/**
	 * Shortcode class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $shortcode = null;

	/**
	 * Filters class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $filters = null;

	/**
	 * Activator class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $activator = null;

	/**
	 * Activator class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $deactivator = null;

	/**
	 * Woocommerce class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $woo = null;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

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
		$this->dir = dirname($this->file);
		$this->assets_dir = trailingslashit($this->dir) . 'assets';
		$this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

		$this->activator = new WP_MultiCOM_Activator($this->file);
		$this->deactivator = new WP_MultiCOM_Deactivator($this->file);

		// Fires when the plugin was activated
		register_activation_hook($this->file, array($this, 'install'));

		// Create shortcode
		add_shortcode('multicom_referral_info', array($this, 'get_shortcode_referral_info'));

		// Load frontend JS & CSS
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);

		// Load API for generic admin functions
		if (is_admin()) {
			$this->admin = new WP_MultiCOM_Admin_API();

			add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 10);
		}

		// Handle localization
		$this->load_plugin_textdomain();
	} // End __construct ()

	/**
	 * Enqueue scripts for admin users
	 * @access  public
	 * @since   1.3.0
	 * @return void
	 */
	public function enqueue_admin_scripts()
	{
		wp_register_script(WP_MultiCOM_Constant::$TOKEN . '-admin', esc_url($this->assets_url) . 'js/admin-front.js', array('jquery'), WP_MultiCOM_Constant::$VERSION);
		wp_enqueue_script(WP_MultiCOM_Constant::$TOKEN . '-admin');
	} // End enqueue_admin_scripts

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type($post_type = '', $plural = '', $single = '', $description = '', $options = array())
	{
		if (!$post_type || !$plural || !$single) return;

		$post_type = new WP_MultiCOM_Post_Type($post_type, $plural, $single, $description, $options);

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy($taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array())
	{
		if (!$taxonomy || !$plural || !$single) return;

		$taxonomy = new WP_MultiCOM_Taxonomy($taxonomy, $plural, $single, $post_types, $taxonomy_args);

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles()
	{
		wp_register_style(WP_MultiCOM_Constant::$TOKEN . '-frontend', esc_url($this->assets_url) . 'css/frontend.css', array(), WP_MultiCOM_Constant::$VERSION);
		wp_enqueue_style(WP_MultiCOM_Constant::$TOKEN . '-frontend');
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts()
	{
		wp_register_script(WP_MultiCOM_Constant::$TOKEN . '-frontend', esc_url($this->assets_url) . 'js/frontend.js', array('jquery'), WP_MultiCOM_Constant::$VERSION);
		wp_enqueue_script(WP_MultiCOM_Constant::$TOKEN . '-frontend');
	} // End enqueue_scripts ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain()
	{
		$domain = WP_MultiCOM_Constant::$TEXT_DOMAIN;
		$folder = dirname(plugin_basename($this->file));
		$locale = apply_filters('plugin_locale', get_locale(), $domain);
		$with_locale = $folder . '-' . $locale . '.mo';

		load_textdomain($domain, WP_LANG_DIR . DIRECTORY_SEPARATOR . $with_locale);
		load_plugin_textdomain($domain, false, $folder . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR);
	} // End load_plugin_textdomain ()

	/**
	 * Main WP_MultiCOM Instance
	 *
	 * Ensures only one instance of WP_MultiCOM is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WP_MultiCOM()
	 * @return Main WP_MultiCOM instance
	 */
	public static function instance($file = '')
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($file, WP_MultiCOM_Constant::$VERSION);
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheating huh?'), WP_MultiCOM_Constant::$VERSION);
	} // End __clone ()

	/**
	 * Deserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheating huh?'), WP_MultiCOM_Constant::$VERSION);
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install()
	{
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number()
	{
		update_option(WP_MultiCOM_Constant::$TOKEN . '_version', WP_MultiCOM_Constant::$VERSION);
	} // End _log_version_number ()

	public function get_shortcode_referral_info() {
		ob_start();
		echo "<div class='multicom-referral-info-shortcode'>";
		echo $this->get_widget_referral_info();
		echo "</div>";

		$return = ob_get_contents();
		ob_end_clean();

		return $return;
	}

	/**
	 * Get the referral information
	 *
	 * @return string
	 */
	public function get_widget_referral_info()
	{
		$content = "";
		if (isset($_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'referral_id'])) {
			$content = get_option(WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_info_text');
			$user_data = $_SESSION[WP_MultiCOM_Constant::$FIELD_PREFIX . 'ref_data'];

			$content = trim($content);
			if (isset($content) && !empty($content)) {
				$content = stripslashes($content);
				foreach ($user_data as $key => $value) {
					if (isset($value)) {
						$to_find = "%" . $key . "%";
						if ($key === 'photo') {
							$value = '<img src="' . $value . '" alt="" />';
						}

						$content = str_replace($to_find, $value, $content);
					}
				}
			} else {
				echo "<p>" . __('Widget content has not been configured or is empty.', WP_MultiCOM_Constant::$VERSION) . "</p>";
			}
		}

		return $content;
	}
}
