<?php

if (!defined('ABSPATH')) exit;

class WP_MultiCOM_Settings
{

	/**
	 * The single instance of WP_MultiCOM_Settings.
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

	/**
	 * Icon of the plugin
	 *
	 * @var string
	 */
	public $icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/PjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDU1IDU1IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1NSA1NTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPjxwYXRoIGQ9Ik00OSwwYy0zLjMwOSwwLTYsMi42OTEtNiw2YzAsMS4wMzUsMC4yNjMsMi4wMDksMC43MjYsMi44NmwtOS44MjksOS44MjlDMzIuNTQyLDE3LjYzNCwzMC44NDYsMTcsMjksMTdzLTMuNTQyLDAuNjM0LTQuODk4LDEuNjg4bC03LjY2OS03LjY2OUMxNi43ODUsMTAuNDI0LDE3LDkuNzQsMTcsOWMwLTIuMjA2LTEuNzk0LTQtNC00UzksNi43OTQsOSw5czEuNzk0LDQsNCw0YzAuNzQsMCwxLjQyNC0wLjIxNSwyLjAxOS0wLjU2N2w3LjY2OSw3LjY2OUMyMS42MzQsMjEuNDU4LDIxLDIzLjE1NCwyMSwyNXMwLjYzNCwzLjU0MiwxLjY4OCw0Ljg5N0wxMC4wMjQsNDIuNTYyQzguOTU4LDQxLjU5NSw3LjU0OSw0MSw2LDQxYy0zLjMwOSwwLTYsMi42OTEtNiw2czIuNjkxLDYsNiw2czYtMi42OTEsNi02YzAtMS4wMzUtMC4yNjMtMi4wMDktMC43MjYtMi44NmwxMi44MjktMTIuODI5YzEuMTA2LDAuODYsMi40NCwxLjQzNiwzLjg5OCwxLjYxOXYxMC4xNmMtMi44MzMsMC40NzgtNSwyLjk0Mi01LDUuOTFjMCwzLjMwOSwyLjY5MSw2LDYsNnM2LTIuNjkxLDYtNmMwLTIuOTY3LTIuMTY3LTUuNDMxLTUtNS45MXYtMTAuMTZjMS40NTgtMC4xODMsMi43OTItMC43NTksMy44OTgtMS42MTlsNy42NjksNy42NjlDNDEuMjE1LDM5LjU3Niw0MSw0MC4yNiw0MSw0MWMwLDIuMjA2LDEuNzk0LDQsNCw0czQtMS43OTQsNC00cy0xLjc5NC00LTQtNGMtMC43NCwwLTEuNDI0LDAuMjE1LTIuMDE5LDAuNTY3bC03LjY2OS03LjY2OUMzNi4zNjYsMjguNTQyLDM3LDI2Ljg0NiwzNywyNXMtMC42MzQtMy41NDItMS42ODgtNC44OTdsOS42NjUtOS42NjVDNDYuMDQyLDExLjQwNSw0Ny40NTEsMTIsNDksMTJjMy4zMDksMCw2LTIuNjkxLDYtNlM1Mi4zMDksMCw0OSwweiIvPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjwvc3ZnPg==';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct($parent)
	{
		$this->parent = $parent;

		// Initialise settings
		add_action('init', array($this, 'init_settings'), 11);

		// Register plugin settings
		add_action('admin_init', array($this, 'register_settings'));

		// Add settings page to menu
		add_action('admin_menu', array($this, 'add_menu_item'));

		// Add settings link to plugins page
		add_filter('plugin_action_links_' . plugin_basename($this->parent->file), array($this, 'add_settings_link'));

		// Add hook after enable auto sync
		add_action('update_option', array($this, 'manage_autosync_cron'), 10, 3);
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings()
	{
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item()
	{
		add_menu_page(__('MultiCOM', WP_MultiCOM_Constant::$TEXT_DOMAIN), __('MultiCOM', WP_MultiCOM_Constant::$TEXT_DOMAIN), 'manage_options', WP_MultiCOM_Constant::$TEXT_DOMAIN . '_settings',  array($this, 'settings_page'), $this->icon);
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets() { }

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link($links)
	{
		array_push(
			$links,
			'<a href="options-general.php?page=' . WP_MultiCOM_Constant::$TEXT_DOMAIN . '_settings">' . __('Settings', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '</a>'
		);
		array_push(
			$links,
			'<a href="https://wordpress.mcomsolutions.biz/wordpress_generic/home/" rel="author" target="_blank">' . __('Visit site', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '</a>'
		);

		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields()
	{
		$settings['userinfo'] = array(
			'title'							=> __('Widget', WP_MultiCOM_Constant::$TEXT_DOMAIN),
			'fields'						=> array(
				array(
					'id' 						=> 'ref_info_text',
					'label'					=> __('Content you wish to show', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'description'		=> __('This is the content inside your widget. You can use html markup and insert tags for referral info.<br><strong>Use the following tags:</strong><br><strong>%referral_name%</strong> = Referral Name<br><strong>%referral_id%</strong> = Referral ID<br><strong>%first_name%</strong> = First Name<br><strong>%last_name%</strong> = Last Name<br><strong>%phone%</strong> = Mobile Number<br><strong>%email%</strong> = E-mail<br><strong>%photo%</strong> = Profile Image', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'editor',
					'default'				=> '<div class="widget_default_style"><div style="width:100%;">Presented by <strong>%first_name% %last_name%</strong></div><div><div style="width:25%; display: inline-block;">%photo%</div><div style="width:70%; display: inline-block;">Email:<br>%email%<br>Mobile: %phone%</div></div></div>',
					'media_buttons' => 'true'
				),
				array(
					'id' 						=> 'ref_info_css',
					'label'					=> __('Custom css file for widget content', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'text',
					'default'				=> ''
				),
			)
		);

		$menus = get_terms('nav_menu');
		$listMenu = [];
		foreach ($menus as $menu) {
			$listMenu = [$menu->term_id => $menu->name];
		}

		$settings['regpage'] = array(
			'title'							=> __('Register Page', WP_MultiCOM_Constant::$TEXT_DOMAIN),
			'description'				=> __('Configure if you wish MultiCOM Plugin to create your <strong>Registration Page</strong> and <strong>Menu Entry</strong>. Once created, you can manage it in your <strong>Wordpress Pages</strong> module. You can also use the shortcode [woocommerce_multicom_registration] in any of your current pages.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
			'fields'						=> array()
		);

		if (count($listMenu) > 0) {
			$settings['regpage']['fields'] = array(
				array(
					'id' 						=> 'enable_regpage',
					'label'					=> __('Enable Registration Page', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'description'		=> __('If you check this option, MultiCOM Plugin will create a menu entry and a page with registration form.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'checkbox',
					'default'				=> ''
				),
				array(
					'id' 						=> 'page_title',
					'label'					=> __('Page Title', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'description'		=> __('The title for the registration Page.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'text',
					'default'				=> 'Become Affiliate',
					'placeholder'		=> __('Page title', WP_MultiCOM_Constant::$TEXT_DOMAIN)
				),
				array(
					'id' 						=> 'register_targetmenu',
					'label'					=> __('Menu', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'description'		=> __('Select target menu for your page\'s link', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'select',
					'options'				=> $listMenu,
					'default'				=> array('main'),
					'have_error'		=> null,
				),
				array(
					'id' 						=> 'after_registration_message',
					'label'					=> __('Welcome message', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'description'		=> __('This message is displayed after the user registers, if it is not defined, the WooCommerce dashboard is displayed', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'editor',
					'media_buttons' => 'true'
				),
			);
		} else {
			$settings['regpage']['fields'] = array(
				array(
					'label'					=> __('Menu', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'type'					=> 'select',
					'options'				=> $listMenu,
					'have_error'		=> __('Please configure the menus', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				),
			);
		}

		if (!function_exists('is_plugin_active')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}

		if (is_plugin_active('wp_multicom_dashboard/wp-multicom-dashboard.php')) {

			$settings['dashboardreports'] = array(
				'title'						=> __('Dashboard & Reports', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'description'			=> __('Configure your MultiCOM Service URL (MSU) and your MultiCOM Token (Client Key) to access to dashboard and reports.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'fields'					=> array(
					array(
						'id' 					=> 'ws_url',
						'label'				=> __('MultiCOM Service URL', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __('Your MultiCOM Service URL (MSU).', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '50',
						'default'			=> 'https://multicomapi.com',
						'placeholder'	=> __('MultiCOM Service URL', WP_MultiCOM_Constant::$TEXT_DOMAIN)
					),
					array(
						'id' 					=> 'token',
						'label'				=> __('Client Token', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __('Your Token provided by MultiCOM.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '50',
						'default'			=> '',
						'placeholder'	=> __('Insert your token', WP_MultiCOM_Constant::$TEXT_DOMAIN)
					),
					array(
						'id' 					=> 'default_associate_type',
						'label'				=> __('Default Associate Type', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '50',
						'default'			=> '',
					),
					array(
						'id' 					=> 'redirect_slug',
						'label'				=> __('Slug to redirect when replicated site', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __('Slug or endpoint to redirect when coming from a replicated site link. Leave it blank for homesite.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '50',
						'default'			=> '',
						'placeholder'	=> __('default home', WP_MultiCOM_Constant::$TEXT_DOMAIN)
					),
					array(
						'id' 				=> 'default_enrollment',
						'label'				=> __('Default replicated data for Enrollment', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '50',
						'default'			=> '',
					),
				)
			);

			$settings['Sync2Multicom'] = array(
				'title'						=> __('Manual Sync', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'description'			=> __('Sync your existing data to MultiCOM Services.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'fields'					=> array(
					array(
						'id' 					=> 'link-date-from',
						'label'				=> __('From date', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'text'				=> '<span class="dashicons dashicons-calendar"></span>',
						'type'				=> 'text',
					),
					array(
						'id' 					=> 'link-date-to',
						'label'				=> __('To date', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'text'				=> '<span class="dashicons dashicons-calendar"></span>',
						'type'				=> 'text',
					),
					array(
						'id' 					=> 'link-users-sync',
						'label'				=> __('Users from WP', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'text'				=> '<span class="dashicons dashicons-admin-users"></span> Sync Users',
						'type'				=> 'button',
					),
					array(
						'id' 					=> 'link-orders-sync',
						'label'				=> __('Orders from WooCommerce', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'text'				=> '<span class="dashicons dashicons-media-default"></span> Sync Orders',
						'type'				=> 'button',
					),
				)
			);

			$schedules = wp_get_schedules();
			$frequency_opts = array();
			$allow_keys = array("hourly", "twicedaily", "daily", "weekly", "monthly");
			foreach ($schedules as $key => $value) {
				if (in_array($key, $allow_keys)) {
					$frequency_opts[$key] = $value["display"];
				}
			}

			$settings['Sync2Auto'] = array(
				'title'				=> __('Auto Sync', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'description'			=> __('Allows automatic synchronization of user data, orders and transactions with the MultiCOM service.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'fields'			=> array(
					array(
						'id'		=> 'autosync',
						'label'		=> __('Enabled', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __('Enable automatic synchronization', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'		=> 'checkbox',
						'size'		=> '50',
						'default'	=> 'false',
					),
					array(
						'id'		=> 'eachsync',
						'label'		=> __('Frequency', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'		=> 'select',
						'default'	=> 'twicedaily',
						'options'	=> $frequency_opts,
					),
					array(
						'id'		=> 'startsync',
						'label'		=> __('Start at (24 Hrs)', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'placeholder'	=> __('Hrs', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'		=> 'number',
						'default'	=> '0',
						'min'		=> '0',
						'max'		=> '24',
					),
					array(
						'id'		=> 'changesync',
						'label'		=> '',
						'type'		=> 'hidden',
					),
					array(
						'id' 		=> 'sync-auto-now',
						'label'		=> '&nbsp;',
						'text'		=> 'Sync now',
						'description'	=> __('Synchronize all data, taking into account the configuration', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'		=> 'button',
					)
				)
			);

			if (class_exists('LifterLMS')) {
				$settings['Sync2Multicom']['fields'][] = array(
					'id' 					=> 'link-trans-sync',
					'label'				=> __('Transactions from LifterLMS', WP_MultiCOM_Constant::$TEXT_DOMAIN),
					'text'				=> 'Sync Transactions',
					'type'				=> 'button',
				);
			}

			$settings['Sync2Multicom']['fields'][] = array(
				'id' 					=> 'sync-output',
				'label'				=> __('Results', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'type'				=> 'textarea',
				'default'			=> '',
				'placeholder'	=> __('See the Sync Output here.', WP_MultiCOM_Constant::$TEXT_DOMAIN)
			);

			$settings['maintenance'] = array(
				'title'						=> __('Maintenance', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'description'			=> __('Options for maintenance mode.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'fields'					=> array(
					array(
						'id' 					=> 'clearcache',
						'label'				=> __('Reload Metadata', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __('Clears metadata cache. Please do not check unless Support Team asks. This may slowdown your reports.', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'checkbox',
						'size'				=> '50',
						'default'			=> 'false',
					),
				)
			);
		} else {
			$settings['dashboardreports'] = array(
				'title'						=> __('Dashboard & Reports', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'description'			=> __('It seems like you have not installed or activated our MultiCOM Dashboard and Reports plugin. If you do not have it, go to <a href="https://wordpress.mcomsolutions.biz/wordpress_generic/home/" target="_blank">MultiCOM</a> and get it now!', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'fields'					=> array()
			);
		}

		if (class_exists('LifterLMS')) {
			$settings['lifterconfig'] = array(
				'title'						=> __('Lifter Config', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'description'			=> __('Allows you to configure some aspects regarding the use of the LifterLMS plugin..', WP_MultiCOM_Constant::$TEXT_DOMAIN),
				'fields'					=> array(
					array(
						'id' 					=> 'restrictdash',
						'label'				=> __('Restrict dashboard access', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'checkbox',
						'size'				=> '50',
						'default'			=> 'false',
					),
					array(
						'id' 					=> 'membershipsdash',
						'label'				=> __('Memberships Access Dashboard', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __('Slug of valid memberships to access the MultiCOM dashboard, separated by comma', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '100',
						'default'			=> '',
					),
					array(
						'id' 					=> 'membershipsbefore',
						'label'				=> __('Membership List Before', WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'description'	=> __("List of id's that can acquire membership for access to the dashboard, separated by comma", WP_MultiCOM_Constant::$TEXT_DOMAIN),
						'type'				=> 'text',
						'size'				=> '100',
						'default'			=> '',
					),
				)
			);
		}

		$settings = apply_filters(WP_MultiCOM_Constant::$TOKEN . '_settings_fields', $settings);

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings()
	{
		if (is_array($this->settings)) {

			// Check posted / selected tab
			$current_section = '';
			if (isset($_POST['tab']) && $_POST['tab']) {
				$current_section = $_POST['tab'];
			} else if (isset($_GET['tab']) && $_GET['tab']) {
				$current_section = $_GET['tab'];
			}

			foreach ($this->settings as $section => $data) {

				if ($current_section && $current_section != $section) continue;

				// Add section to page
				add_settings_section($section, $data['title'], array($this, 'settings_section'), WP_MultiCOM_Constant::$TOKEN . '_settings');

				foreach ($data['fields'] as $field) {

					// Validation callback for field
					$validation = '';
					if (isset($field['callback'])) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = WP_MultiCOM_Constant::$FIELD_PREFIX . $field['id'];
					register_setting(WP_MultiCOM_Constant::$TOKEN . '_settings', $option_name, $validation);

					// Add field to page
					add_settings_field($field['id'], $field['label'], array($this->parent->admin, 'display_field'), WP_MultiCOM_Constant::$TOKEN . '_settings', $section, array('field' => $field, 'prefix' => WP_MultiCOM_Constant::$FIELD_PREFIX));
				}

				if (!$current_section) break;
			}
		}
	}

	/**
	 * Print the setting section header
	 *
	 * @param array $section
	 * @return string
	 */
	public function settings_section($section)
	{
		if (isset($this->settings[$section['id']])) {
			if (isset($this->settings[$section['id']]['description'])) {
				echo '<p> ' . $this->settings[$section['id']]['description'] . '</p>';
			}
		}
	}

	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page()
	{
		$html = '<script>var admin_ajax = "' . admin_url('admin-ajax.php') . '";</script>'
			. '<div class="wrap" id="'. WP_MultiCOM_Constant::$TOKEN . '_settings">'
			. '<h2><img src="' . $this->icon . '" alt="" height="32px" style="vertical-align: middle;" /><span style="vertical-align: middle;">'
			. __('MultiCOM Affiliate Tracker Plugin - Settings', WP_MultiCOM_Constant::$TEXT_DOMAIN) . '</span></h2>';

		$tab = '';
		if (isset($_GET['tab'])) {
			$tab .= $_GET['tab'];
		}

		// Show page tabs
		if (count($this->settings) > 0) {

			$html .= '<h2 class="nav-tab-wrapper">';
			$counter = 0;

			foreach ($this->settings as $section => $data) {

				// Set tab class
				$class = 'nav-tab';
				if (empty($tab) && $counter === 0) {
					$class .= ' nav-tab-active';
				} else if ($tab === $section) {
					$class .= ' nav-tab-active';
				}

				// Set tab link
				$tab_link = add_query_arg(array('tab' => $section));
				if (isset($_GET['settings-updated'])) {
					$tab_link = remove_query_arg('settings-updated', $tab_link);
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>';
				++$counter;
			}

			$html .= '</h2>';
		}

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">';

		// Get settings fields
		ob_start(); // Begin to catch the HTML output
		settings_fields(WP_MultiCOM_Constant::$TOKEN . '_settings');
		do_settings_sections(WP_MultiCOM_Constant::$TOKEN . '_settings');
		$html .= ob_get_clean(); // Get the HTML output to string

		if ($tab !== 'Sync2Multicom') {
			$html .= '<p class="submit">'
				. '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />'
				. '<input name="Submit" type="submit" class="button-primary" value="'
				. esc_attr(__('Save Settings', WP_MultiCOM_Constant::$TEXT_DOMAIN)) . '" />'
				. '</p>';
		}

		$html .= '</form></div>';

		echo $html;
	}

	public function manage_autosync_cron($option, $old_value, $value) {
		if ($option == WP_MultiCOM_Constant::$FIELD_PREFIX.'changesync') {
			$hook_name = WP_MultiCOM_Constant::$FIELD_PREFIX.'cron_autosync_hook';

			if ($value) {
				$json = json_decode($value);

				if ($json->mcom_autosync == "on") {
					$utc_timezone = new DateTimeZone('UTC');
					$date = new DateTime('now', $utc_timezone);
					$date->add(new DateInterval('P1D'));
					$date->setTime(intval($json->mcom_startsync), 0, 0, 0);
					// Add cron every 30 minutes
					// $date->add(new DateInterval('PT30M'));

					$timestamp = wp_next_scheduled($hook_name);
					if ($timestamp) {
						wp_unschedule_event( $timestamp, $hook_name );
					}

					wp_schedule_event($date->getTimestamp(), $json->mcom_eachsync, $hook_name);
				} else if ($json->mcom_autosync == "off") {
					$timestamp = wp_next_scheduled($hook_name);

					if ($timestamp) {
						wp_unschedule_event($timestamp, $hook_name);
					}
				}
			}
		}
	}

	/**
	 * Main WP_MultiCOM_Settings Instance
	 *
	 * Ensures only one instance of WP_MultiCOM_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WP_MultiCOM()
	 * @return Main WP_MultiCOM_Settings instance
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
