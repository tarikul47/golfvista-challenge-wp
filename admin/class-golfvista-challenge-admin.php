<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/admin
 * @author     Golfvista <your-email@example.com>
 */
class Golfvista_Challenge_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' ), array( $this, 'add_action_links' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_menu', array( $this, 'add_participants_page' ) );
        add_action( 'admin_init', array( $this, 'options_update' ) );
        add_action( 'admin_init', array( $this, 'handle_participant_actions' ) );
    }

    /**
     * Handle actions related to participants.
     *
     * @since    1.0.0
     */
    public function handle_participant_actions() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'reset_challenge' && isset( $_GET['user_id'] ) ) {
            $user_id = absint( $_GET['user_id'] );
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'golfvista_reset_participant_' . $user_id ) ) {
                wp_die( 'Security check failed.' );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'You do not have permission to perform this action.' );
            }

            delete_user_meta( $user_id, '_golfvista_challenge_status' );
            delete_user_meta( $user_id, '_golfvista_challenge_media_ids' );

            wp_safe_redirect( admin_url( 'edit.php?post_type=business_plan&page=golfvista-challenge-participants&participant_reset=1' ) );
            exit;
        }
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links( $links ) {
        $settings_link = array(
            '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', $this->plugin_name ) . '</a>',
        );
        return array_merge( $settings_link, $links );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/golfvista-challenge-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/golfvista-challenge-admin.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Add the administration menu for this plugin.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            'Golfvista Challenge Settings',
            'Golfvista Challenge',
            'manage_options',
            $this->plugin_name,
            array( $this, 'admin_page_display' )
        );
    }

    /**
     * Add the participants page to the admin menu.
     *
     * @since    1.0.0
     */
    public function add_participants_page() {
        add_submenu_page(
            'edit.php?post_type=business_plan',
            'Challenge Participants',
            'Participants',
            'manage_options',
            'golfvista-challenge-participants',
            array( $this, 'participants_page_display' )
        );
    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function admin_page_display() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'main';
        include_once 'partials/golfvista-challenge-admin-display.php';
    }

    /**
     * Render the participants page for this plugin.
     *
     * @since    1.0.0
     */
    public function participants_page_display() {
        if ( isset( $_GET['participant_reset'] ) && $_GET['participant_reset'] == 1 ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Participant has been reset.', 'golfvista-challenge' ); ?></p>
            </div>
            <?php
        }
        include_once 'partials/golfvista-challenge-participants-display.php';
    }

    /**
     * Update the plugin settings.
     *
     * @since    1.0.0
     */
    public function options_update() {
        register_setting( 'golfvista_challenge_main', 'golfvista_challenge_main', array( $this, 'validate_main' ) );
        register_setting( 'golfvista_challenge_quiz', 'golfvista_challenge_quiz', array( $this, 'validate_quiz' ) );
    }

    /**
     * Validate the main plugin settings.
     *
     * @since    1.0.0
     */
    public function validate_main( $input ) {
        $valid = array();
        $valid['sightengine_api_user'] = sanitize_text_field( $input['sightengine_api_user'] );
        $valid['sightengine_api_secret'] = sanitize_text_field( $input['sightengine_api_secret'] );
        $valid['challenge_product_id'] = absint( $input['challenge_product_id'] );
        return $valid;
    }

    /**
     * Validate the quiz settings.
     *
     * @since    1.0.0
     */
    public function validate_quiz( $input ) {
        $valid = array();
        if ( isset( $input['questions'] ) && is_array( $input['questions'] ) ) {
            $questions = array_slice( $input['questions'], 0, 6 );
            foreach ( $questions as $question ) {
                if ( ! empty( $question['text'] ) && ! empty( $question['keyword'] ) ) {
                    $valid['questions'][] = array(
                        'text' => sanitize_text_field( $question['text'] ),
                        'keyword' => sanitize_text_field( $question['keyword'] ),
                    );
                }
            }
        }
        return $valid;
    }
}
