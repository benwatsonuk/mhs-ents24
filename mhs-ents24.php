<?php
/**
 * Plugin Name: MHS-events24
 * Description: Connect to your events24 account and retrieve data from the API. Then store it in your Wordpress Database
 * Version: 1.0.0
 * Author: Ben Watson
 * Author URI: http://benwatson.uk/
 * Requires at least: 4.0.0
 * Tested up to: 4.0.0
 *
 *
 * @package MHS_Ents24
 * @category Core
 * @author BenWatson
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of MHS_Ents24 to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object MHS_Ents24
 */
function MHS_Ents24() {
	return MHS_Ents24::instance();
} // End MHS_Ents24()

add_action( 'plugins_loaded', 'MHS_Ents24' );

/**
 * Main MHS_Ents24 Class
 *
 * @class MHS_Ents24
 * @version	1.0.0
 * @since 1.0.0
 * @package	MHS_Ents24
 * @author Matty
 */
final class MHS_Ents24 {
	/**
	 * MHS_Ents24 The single instance of MHS_Ents24.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin directory URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

	/**
	 * The plugin directory path.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The settings object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings;
	// Admin - End

	// Post Types - Start
	/**
	 * The post types we're registering.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();
	// Post Types - End
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct () {
		$this->token 			= 'mhs-ents24';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';

		// Admin - Start
		require_once( 'classes/class-mhs-ents24-settings.php' );
			$this->settings = MHS_Ents24_Settings::instance();

		if ( is_admin() ) {
			require_once( 'classes/class-mhs-ents24-admin.php' );
			$this->admin = MHS_Ents24_Admin::instance();
		}

		//Main functions
		require_once( 'classes/class-mhs-ents24-main.php' );
		$this->main = MHS_Ents24_Main::instance();

		if ( ! function_exists( 'MHS_Ents24_GetRawData' ) ) {
			function MHS_Ents24_GetRawData() {
				return MHS_Ents24_Main::get_the_raw_data();
			}
		}

		if ( ! function_exists( 'MHS_Ents24_GetFormattedData' ) ) {
			function MHS_Ents24_GetFormattedData() {
				return MHS_Ents24_Main::get_the_formatted_data();
			}
		}

		// Admin - End

		// Register an example post type. To register other post types, duplicate this line.
//		$this->post_types['thing'] = new MHS_Ents24_Post_Type( 'thing', __( 'Thing', 'mhs-ents24' ), __( 'Things', 'mhs-ents24' ), array( 'menu_icon' => 'dashicons-carrot' ) );
		// Post Types - End
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	} // End __construct()

	/**
	 * Main MHS_Ents24 Instance
	 *
	 * Ensures only one instance of MHS_Ents24 is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see MHS_Ents24()
	 * @return Main MHS_Ents24 instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'mhs-ents24', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()
} // End Class
