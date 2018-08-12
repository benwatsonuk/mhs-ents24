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

		$transientName = 'mhs-ents24-events-formatted';
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
	 * Return the raw data as provided by ents24.
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
	 * Get an auth token from ents24 and put it in the database
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_auth_token() {

		$clientData   = get_option( 'mhs-ents24-standard-fields' );
		$clientId     = rawurlencode( $clientData['client-id'] );
		$clientSecret = rawurlencode( $clientData['client-secret'] );

		if ( ! isset( $clientId ) || ! isset ( $clientSecret ) ) {
			return false;
		}

		$endpoint   = 'https://api.ents24.com/auth/token';
		$postString = 'client_id=' . $clientId . '&client_secret=' . $clientSecret;

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $postString );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$response = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			echo "ERROR: " . curl_error( $ch );
			echo "\n<br />";
			$response = '';
		} else {
			curl_close( $ch );
		}
		function isJson( $string ) {
			json_decode( $string );

			return ( json_last_error() == JSON_ERROR_NONE );
		}

		if ( ! isJson( $response ) || ! strlen( $response ) ) {
			//Add alert to let admin now that there has been an issue retrieving the API
			MHS_Ents24_Main::send_email_to_admin( $response );

			return $response;
		}

		if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) !== 401 ) {
			$authToken = json_decode( $response )->access_token;
			update_option( 'mhs-ents24-auth-token', $authToken );
		} else {
			return false;
		}

		return $authToken;

	}

	/**
	 * Grab the data from ents24.
	 * @access  public
	 * @since   1.0.0
	 */
	public function curl_the_data() {

		$artistId = get_option( 'mhs-ents24-standard-fields' );
		$artistId = rawurlencode( $artistId['artist-id'] );

		if ( ! isset( $artistId ) ) {
			return false;
		}

		$authToken = get_option( 'mhs-ents24-auth-token' );
//		$authToken = rawurlencode( $authToken['auth-token'] );

		if ( ! isset( $authToken ) || $authToken === '' || $authToken === false ) {
			$authToken = MHS_Ents24_Main::get_auth_token();
		}

		$url = "https://api.ents24.com/artist/events?id=" . $artistId . "&results_per_page=25";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $authToken
		) );
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

		if ( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) !== 401 ) {
			update_option( 'mhs-ents24-raw-data', $events );
		} else {
			return false;
		}

		return $events;

	} // End get_the_data()

	/**
	 * Format the ents24 data and store in options table as HTML.
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
			$obj['datetime']        = $event->startDate;
			$obj['formatted_day']   = date( 'D', strtotime( $event->startDate ) );
			$obj['formatted_date']  = date( 'd', strtotime( $event->startDate ) );
			$obj['formatted_month'] = date( 'M', strtotime( $event->startDate ) );
			$obj['formatted_year']  = date( 'Y', strtotime( $event->startDate ) );
			$obj['formatted_time']  = $event->startTimeString;
			$obj['city']            = $event->venue->address->town;
			$obj['region']          = $event->venue->address->county;
			$obj['venue']           = $event->venue->name;
			$obj['eventHeadline']   = $event->headline;
			$obj['eventLink']       = $event->webLink;
			$obj['eventTitle']      = $event->title;
			$obj['eventDetails']    = $event->description;
			$obj['eventImage']      = $event->image->url;
			$obj['eventImageWidth'] = $event->image->width;
			if ( $event->ticketsAvailable === true ) {
				$obj['tickets']     = $event->webLink;
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
	 * Send an email to admin to let them know ents24 API has failed.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function send_email_to_admin( $error ) {
		$email     = get_bloginfo( 'admin_email' );
		$blogTitle = get_bloginfo( 'wpurl' );
		$subject   = 'Issue with ' . $blogTitle;
		$msg       = "The MHS-ents24 plugin has failed to connect to the ents24 API. We received the following error: ";
		$msg .= $error;
		// send email
		mail( $email, $subject, $msg );

	}//End store_the_formatted_data


} // End Class