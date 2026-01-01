<?php

/**
 * A wrapper for the Sightengine API.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/includes
 */
class Golfvista_Sightengine_Api {

    /**
     * The Sightengine API user.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_user    The Sightengine API user.
     */
    private $api_user;

    /**
     * The Sightengine API secret.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_secret    The Sightengine API secret.
     */
    private $api_secret;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $options = get_option( 'golfvista_challenge_main' );
        $this->api_user = isset( $options['sightengine_api_user'] ) ? $options['sightengine_api_user'] : '';
        $this->api_secret = isset( $options['sightengine_api_secret'] ) ? $options['sightengine_api_secret'] : '';
    }

    /**
     * Check if the API credentials are set.
     *
     * @since   1.0.0
     * @return  boolean True if credentials are set, false otherwise.
     */
    public function has_credentials() {
        return ! empty( $this->api_user ) && ! empty( $this->api_secret );
    }

    /**
     * Check an image for AI generation.
     *
     * @since   1.0.0
     * @param   string $image_url The URL of the image to check.
     * @return  array|WP_Error The API response or a WP_Error on failure.
     */
    public function check_image( $image_url ) {
        if ( ! $this->has_credentials() ) {
            return new WP_Error( 'missing_credentials', 'Sightengine API credentials are not set.' );
        }

        $api_url = add_query_arg(
            array(
                'models' => 'genai',
                'api_user' => $this->api_user,
                'api_secret' => $this->api_secret,
                'url' => $image_url,
            ),
            'https://api.sightengine.com/1.0/check.json'
        );

        $response = wp_remote_get( $api_url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data && $data['status'] == 'success' ) {
            return $data;
        } else {
            return new WP_Error( 'api_error', 'Sightengine API error: ' . ( isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error' ) );
        }
    }
}
