<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WP_MultiCOM
 * @subpackage WP_MultiCOM/includes
 */
class WP_MultiCOM_Deactivator
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

		register_deactivation_hook($this->file, array($this, 'deactivate'));
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
	{
	}
}
