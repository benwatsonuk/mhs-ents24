<?php
//if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * MHS_Ents24_Main Class
 *
 * @class MHS_Ents24_Main
 * @version    1.0.0
 * @since 1.0.0
 * @package    MHS_Ents24
 * @author Ben
 */
final class MHS_Ents24_Main {
	/**
	 * MHS_Ents24_Main The single instance of MHS_Ents24_Main.
	 * @var    object
	 * @access  private
	 * @since    1.0.0
	 */
	private static $_instance = null;

	/**
	 * The string containing the dynamically generated hook token.
	 * @var     string
	 * @access  private
	 * @since   1.0.0
	 */
	private $_hook;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {
		// Register the settings with WordPress.
//		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Register the settings screen within WordPress.
//		add_action( 'admin_menu', array( $this, 'register_settings_screen' ) );
	} // End __construct()

	/**
	 * Main MHS_Ents24_Main Instance
	 *
	 * Ensures only one instance of MHS_Ents24_Main is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Main MHS_Ents24_Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	} // End instance()

	/**
	 * Return the re-formatted data.
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_the_formatted_data() {

		$transientName = 'mhs-ents24-tour-formatted';
		$formattedData = get_transient( $transientName );

		//If there is no transient - create it
		if ( $formattedData == false ) {
			MHS_Ents24_Main::store_the_formatted_data( $transientName );
			$formattedData = get_transient( $transientName );
		}

		//Get the data
		return $formattedData;

	}//End get_the_formatted_data

	/**
	 * Return the raw data as provided by events24.
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_the_raw_data() {

		$transientName = 'mhs-ents24-events-raw';
		$rawData       = get_transient( $transientName );

		//If there is no transient - create it
		if ( $rawData == false ) {
			$rawData = MHS_Ents24_Main::curl_the_data();
			set_transient( $transientName, $rawData, 60 * 60 * 24 ); //Reset Transient
		}

		//Get the data
		return json_decode( $rawData );

	}//End get_the_raw_data


	/**
	 * Grab the data from events24.
	 * @access  public
	 * @since   1.0.0
	 */
	public function curl_the_data() {

		$artistId = get_option( 'mhs-ents24-standard-fields' );
		$artistId = rawurlencode( $artistId['artist-id'] );

		if ( ! isset( $artistId ) ) {
			return false;
		}

		$url = "https://api.ents24.com/artist/events?id=" . $artistId . "&results_per_page=25";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$events = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			echo "ERROR: " . curl_error( $ch );
			echo "\n<br />";
			$events = '';
		} else {
			curl_close( $ch );
		}

		function isJson( $string ) {
			json_decode( $string );

			return ( json_last_error() == JSON_ERROR_NONE );
		}

		if ( ! isJson( $events ) || ! strlen( $events ) ) {
			$response = $events;
//			echo "Failed to get contents.";
			$events = get_option( 'mhs-ents24-raw-data' );
			//Add alert to let admin now that there has been an issue retrieving the API
			MHS_Ents24_Main::send_email_to_admin( $response );

			return $events;
		}

		update_option( 'mhs-ents24-raw-data', $events );

		return $events;

	} // End get_the_data()

	/**
	 * Format the events24 data and store in options table as HTML.
	 * @access  public
	 * @since   1.0.0
	 */
	public function format_the_data( $theData ) {
		//Get the data
//		$theData = MHS_Ents24_Main::get_the_raw_data();
		$formattedData = [];

		//Reformat it into something that we can use
		foreach ( $theData as $event ) {
			$obj                    = [];
			$obj['datetime']        = $event->datetime;
			$obj['formatted_day']   = date( 'D', strtotime( $event->datetime ) );
			$obj['formatted_date']  = date( 'd', strtotime( $event->datetime ) );
			$obj['formatted_month'] = date( 'M', strtotime( $event->datetime ) );
			$obj['formatted_year']  = date( 'Y', strtotime( $event->datetime ) );
			$obj['formatted_time']  = date( 'h:i A', strtotime( $event->datetime ) );
			$obj['city']            = $event->venue->city;
			$obj['region']          = $event->venue->region;
			$obj['country']         = $event->venue->country;
			$obj['venue']           = $event->venue->name;
			$obj['eventdetails']    = $event->url;
			foreach ( $event->offers as $offer ) {
				if ( $offer->type === 'Tickets' ) {
					$obj['tickets'] = $offer->url;
				}
			}
			array_push( $formattedData, $obj );
		}

		return $formattedData;

	}//End format_the_data

	/**
	 * Store the formatted data.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function store_the_formatted_data( $transientName ) {
		//Get the data
		$getData = MHS_Ents24_Main::get_the_raw_data();
		$theData = json_encode( MHS_Ents24_Main::format_the_data( $getData ) );
		set_transient( $transientName, $theData, 60 * 60 * 24 ); //Reset Transient

	}//End store_the_formatted_data

	/**
	 * Send an email to admin to let them know events24 API has failed.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function send_email_to_admin( $error ) {
		$email     = get_bloginfo( 'admin_email' );
		$blogTitle = get_bloginfo( 'wpurl' );
		$subject   = 'Issue with ' . $blogTitle;
		$msg       = "The MHS-events24 plugin has failed to connect to the events24 API. We received the following error: ";
		$msg .= $error;
		// send email
		mail( $email, $subject, $msg );

	}//End store_the_formatted_data


} // End Class