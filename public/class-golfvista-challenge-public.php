<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/public
 * @author     Golfvista <your-email@example.com>
 */
class Golfvista_Challenge_Public {

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
     * The main plugin class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Golfvista_Challenge    $plugin    The main plugin class instance.
     */
    private $plugin;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     * @param      Golfvista_Challenge    $plugin    The main plugin class instance.
     */
    public function __construct( $plugin_name, $version, $plugin ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin = $plugin;

        add_action('init', array($this, 'handle_media_submission'));
        add_action('init', array($this, 'handle_quiz_submission'));
        add_action('init', array($this, 'handle_business_plan_submission'));

        // AJAX for media verification status
        add_action('wp_ajax_golfvista_check_media_verification', array($this, 'ajax_check_media_verification_status'));
        add_action('wp_ajax_nopriv_golfvista_check_media_verification', array($this, 'ajax_check_media_verification_status'));
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/golfvista-challenge-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_media();
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/golfvista-challenge-public.js', array( 'jquery' ), $this->version, false );

        wp_localize_script(
            $this->plugin_name,
            'golfvista_challenge_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'golfvista_media_verification_nonce' ),
            )
        );
    }

    /**
     * Renders the main challenge page via a shortcode.
     *
     * @since    1.0.0
     */
    public function render_challenge_page() {
        ob_start();
        $status = $this->get_user_challenge_status(get_current_user_id());
        include_once 'partials/golfvista-challenge-public-display.php';
        return ob_get_clean();
    }

    /**
     * Retrieves the user's current status in the challenge.
     *
     * @since   1.0.0
     * @param   int   $user_id The ID of the user.
     * @return  string  The user's status.
     */
    public function get_user_challenge_status( $user_id ) {
        if ( ! $user_id ) {
            return 'not_logged_in';
        }

        $status = get_user_meta( $user_id, '_golfvista_challenge_status', true );

        if ( empty( $status ) ) {
            return 'not_started';
        }

        return $status;
    }

    /**
     * Handles the media submission from the challenge form.
     *
     * @since 1.0.0
     */
    public function handle_media_submission() {
        if ( isset( $_GET['try_again'] ) && $_GET['try_again'] === 'true' ) {
            $user_id = get_current_user_id();
            if ( $user_id ) {
                update_user_meta( $user_id, '_golfvista_challenge_status', 'not_started' );
                delete_user_meta( $user_id, '_golfvista_challenge_media_ids' );
                wp_safe_redirect( remove_query_arg( 'try_again' ) );
                exit;
            }
        }

        if ( isset( $_POST['golfvista_media_submission'] ) ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'golfvista_media_submission_nonce' ) ) {
                wp_die( 'Security check failed.' );
            }

            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return;
            }

            if ( isset( $_POST['golfvista_media_ids'] ) && !empty( $_POST['golfvista_media_ids'] ) ) {
                $media_ids = array_map( 'absint', explode( ',', $_POST['golfvista_media_ids'] ) );

                if ( count( $media_ids ) === 5 ) {
                    update_user_meta( $user_id, '_golfvista_challenge_media_ids', $media_ids );
                    update_user_meta( $user_id, '_golfvista_challenge_status', 'media_pending_verification' );
                } else {
                    // Handle error - incorrect number of files
                }
            }
        } else {
            // Handle error - no files submitted
        }
    }

    /**
     * Handles the quiz submission from the challenge form.
     *
     * @since 1.0.0
     */
    public function handle_quiz_submission() {
        if ( isset( $_POST['golfvista_quiz_submission'] ) ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'golfvista_quiz_submission_nonce' ) ) {
                wp_die( 'Security check failed.' );
            }

            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return;
            }

            $user = get_userdata( $user_id );

            $quiz_options = get_option( 'golfvista_challenge_quiz' );
            $questions = isset( $quiz_options['questions'] ) ? $quiz_options['questions'] : array();
            $answers = isset( $_POST['answers'] ) ? $_POST['answers'] : array();
            $correct_answers = 0;

            foreach ( $questions as $i => $question ) {
                if ( isset( $answers[ $i ] ) && strcasecmp( trim( $answers[ $i ] ), trim( $question['keyword'] ) ) == 0 ) {
                    $correct_answers++;
                }
            }

            if ( $correct_answers >= 4 ) {
                update_user_meta( $user_id, '_golfvista_challenge_status', 'quiz_passed' );
                $this->plugin->send_notification( $user->user_email, 'Quiz Passed', 'Congratulations! You have passed the quiz.' );
            } else {
                update_user_meta( $user_id, '_golfvista_challenge_status', 'quiz_failed' );
                $this->plugin->send_notification( $user->user_email, 'Quiz Failed', 'Unfortunately, you did not pass the quiz.' );
            }

            wp_safe_redirect( get_permalink() );
            exit;
        }
    }

    /**
     * Handles the business plan submission from the challenge form.
     *
     * @since 1.0.0
     */
    public function handle_business_plan_submission() {
        if ( isset( $_POST['golfvista_business_plan_submission'] ) ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'golfvista_business_plan_submission_nonce' ) ) {
                wp_die( 'Security check failed.' );
            }

            $user_id = get_current_user_id();
            if ( ! $user_id ) {
                return;
            }

            if ( isset( $_POST['business_plan_content'] ) && ! empty( $_POST['business_plan_content'] ) ) {
                $user_info = get_userdata( $user_id );
                $post_title = 'Business Plan from ' . $user_info->display_name;
                $post_content = sanitize_textarea_field( $_POST['business_plan_content'] );

                $post_data = array(
                    'post_title'    => $post_title,
                    'post_content'  => $post_content,
                    'post_status'   => 'publish',
                    'post_author'   => $user_id,
                    'post_type'     => 'business_plan',
                );

                $post_id = wp_insert_post( $post_data );

                if ( ! is_wp_error( $post_id ) ) {
                    update_user_meta( $user_id, '_golfvista_challenge_status', 'plan_submitted' );
                    wp_safe_redirect( get_permalink() );
                    exit;
                }
            }
        }
    }

    /**
     * AJAX handler to check media verification status.
     *
     * @since 1.0.0
     */
    public function ajax_check_media_verification_status() {
        check_ajax_referer( 'golfvista_media_verification_nonce', 'nonce' );

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'User not logged in.' ) );
            wp_die();
        }

        $current_status = $this->get_user_challenge_status( $user_id );
        $response_message = '';

        if ( 'media_pending_verification' === $current_status ) {
            // Trigger the media check process if it's still pending
            // We call run_media_check directly for immediate feedback via AJAX
            // The scheduled event will act as a fallback if AJAX fails or is not used.
            $this->plugin->run_media_check( $user_id );
            $current_status = $this->get_user_challenge_status( $user_id ); // Re-fetch status after running check
        }

        switch ( $current_status ) {
            case 'media_pending_verification':
                $response_message = 'Media verification is still in progress. Please wait...';
                break;
            case 'media_approved':
                $response_message = 'Your media has been approved! Redirecting to payment...';
                break;
            case 'media_failed':
                $response_message = 'Unfortunately, your media did not pass verification. Please try again.';
                break;
            default:
                $response_message = 'An unexpected status occurred: ' . $current_status;
                break;
        }

        wp_send_json_success( array(
            'status'  => $current_status,
            'message' => $response_message,
        ) );
        wp_die();
    }
}
