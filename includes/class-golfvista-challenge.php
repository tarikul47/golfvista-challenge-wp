<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/includes
 * @author     Golfvista <your-email@example.com>
 */
class Golfvista_Challenge
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Golfvista_Challenge_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('GOLFVISTA_CHALLENGE_VERSION')) {
            $this->version = GOLFVISTA_CHALLENGE_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'golfvista-challenge';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        $this->loader->add_action('updated_user_meta', $this, 'trigger_media_check', 10, 4);
        $this->loader->add_action('golfvista_run_media_check', $this, 'run_media_check', 10, 1);
        $this->loader->add_action('woocommerce_order_status_completed', $this, 'handle_successful_payment', 10, 1);
        $this->loader->add_action('init', $this, 'register_business_plan_cpt');
        $this->loader->add_action('publish_business_plan', $this, 'notify_admin_on_business_plan', 10, 2);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Golfvista_Challenge_Loader. Orchestrates the hooks of the plugin.
     * - Golfvista_Challenge_i18n. Defines internationalization functionality.
     * - Golfvista_Challenge_Admin. Defines all hooks for the admin area.
     * - Golfvista_Challenge_Public. Defines all hooks for the public side of the site.
     * - Golfvista_Sightengine_Api. A wrapper for the Sightengine API.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        // Core orchestrator
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-golfvista-challenge-loader.php';

        // Internationalization
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-golfvista-challenge-i18n.php';

        // Admin and Public functionality
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-golfvista-challenge-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-golfvista-challenge-public.php';

        // Sightengine API wrapper
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-golfvista-sightengine-api.php';

        $this->loader = new Golfvista_Challenge_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Golfvista_Challenge_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Golfvista_Challenge_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Golfvista_Challenge_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Golfvista_Challenge_Public($this->get_plugin_name(), $this->get_version(), $this);

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_shortcode('golfvista_challenge', $plugin_public, 'render_challenge_page');

    }

    /**
     * Trigger the media check when a user's status is updated to 'media_uploaded'.
     *
     * @since 1.0.0
     */
    public function trigger_media_check($meta_id, $object_id, $meta_key, $_meta_value)
    {
        error_log("Golfvista Challenge: trigger_media_check called. meta_key: {$meta_key}, object_id: {$object_id}, _meta_value: {$_meta_value}");
        if ($meta_key === '_golfvista_challenge_status' && $_meta_value === 'media_pending_verification') {
            if ( ! wp_next_scheduled( 'golfvista_run_media_check', array( 'user_id' => $object_id ) ) ) {
                wp_schedule_single_event(time(), 'golfvista_run_media_check', array('user_id' => $object_id));
                error_log("Golfvista Challenge: Scheduled media check for user ID: {$object_id}");
            } else {
                error_log("Golfvista Challenge: Media check already scheduled for user ID: {$object_id}");
            }
        }
    }

    /**
     * Run the media check for the specified user.
     *
     * @since 1.0.0
     */
    public function run_media_check($user_id)
    {
        error_log("Golfvista Challenge: run_media_check started for user ID: {$user_id}");
        $media_ids = get_user_meta($user_id, '_golfvista_challenge_media_ids', true);
        if (empty($media_ids)) {
            error_log("Golfvista Challenge: No media IDs found for user ID: {$user_id}. Aborting media check.");
            return;
        }
        error_log("Golfvista Challenge: Media IDs for user ID {$user_id}: " . implode(', ', $media_ids));

        $sightengine_api = new Golfvista_Sightengine_Api();
        if (!$sightengine_api->has_credentials()) {
            error_log("Golfvista Challenge: Sightengine API credentials are not set. Aborting media check for user ID: {$user_id}");
            // Handle missing credentials, maybe log an error
            update_user_meta($user_id, '_golfvista_challenge_status', 'media_failed'); // Fail if no credentials
            $user = get_userdata($user_id);
            if ($user) {
                $this->send_notification($user->user_email, 'Media Check Failed', 'Your media could not be checked due to missing API credentials. Please contact site administrator.');
            }
            return;
        }

        $is_original = true;
        foreach ($media_ids as $media_id) {
            $media_url = wp_get_attachment_url($media_id);
            if ( ! $media_url ) {
                error_log("Golfvista Challenge: Media URL not found for attachment ID: {$media_id} for user ID: {$user_id}");
                $is_original = false;
                break;
            }
            error_log("Golfvista Challenge: Checking media URL: {$media_url} for user ID: {$user_id}");
            $result = $sightengine_api->check_image($media_url);

            if (is_wp_error($result)) {
                error_log("Golfvista Challenge: Sightengine API error for user ID {$user_id}: " . $result->get_error_message());
                // Handle API error
                $is_original = false;
                break;
            }

            error_log("Golfvista Challenge: Sightengine API response for {$media_id} for user ID {$user_id}: " . print_r($result, true));

            if (isset($result['ai_generated']) && $result['ai_generated']['prob'] > 0.5) {
                error_log("Golfvista Challenge: AI Generated detected for media ID {$media_id} with probability: {$result['ai_generated']['prob']}");
                $is_original = false;
                break;
            }
        }

        $user = get_userdata($user_id);
        if ($is_original) {
            update_user_meta($user_id, '_golfvista_challenge_status', 'media_approved');
            error_log("Golfvista Challenge: Media approved for user ID: {$user_id}");
            if ($user) {
                $this->send_notification($user->user_email, 'Media Approved', 'Congratulations! Your media has been approved and you can now proceed to payment.');
            }
        } else {
            update_user_meta($user_id, '_golfvista_challenge_status', 'media_failed');
            error_log("Golfvista Challenge: Media failed for user ID: {$user_id}");
            if ($user) {
                $this->send_notification($user->user_email, 'Media Rejected', 'Unfortunately, your media has been rejected. Please ensure your photos and videos are not AI-generated and try again.');
            }
        }
        error_log("Golfvista Challenge: run_media_check finished for user ID: {$user_id}");
    }

    /**
     * Handle the successful payment of the challenge entry fee.
     *
     * @since 1.0.0
     */
    public function handle_successful_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        $options = get_option('golfvista_challenge_main');
        $product_id = isset($options['challenge_product_id']) ? $options['challenge_product_id'] : 0;

        if (!$user_id || !$product_id) {
            return;
        }

        $items = $order->get_items();
        foreach ($items as $item) {
            if ($item->get_product_id() == $product_id) {
                update_user_meta($user_id, '_golfvista_challenge_status', 'paid');
                break;
            }
        }
    }

    /**
     * Register the Business Plan CPT.
     *
     * @since 1.0.0
     */
    public function register_business_plan_cpt()
    {
        $labels = array(
            'name' => _x('Business Plans', 'post type general name', 'golfvista-challenge'),
            'singular_name' => _x('Business Plan', 'post type singular name', 'golfvista-challenge'),
            'menu_name' => _x('Business Plans', 'admin menu', 'golfvista-challenge'),
            'name_admin_bar' => _x('Business Plan', 'add new on admin bar', 'golfvista-challenge'),
            'add_new' => _x('Add New', 'book', 'golfvista-challenge'),
            'add_new_item' => __('Add New Business Plan', 'golfvista-challenge'),
            'new_item' => __('New Business Plan', 'golfvista-challenge'),
            'edit_item' => __('Edit Business Plan', 'golfvista-challenge'),
            'view_item' => __('View Business Plan', 'golfvista-challenge'),
            'all_items' => __('All Business Plans', 'golfvista-challenge'),
            'search_items' => __('Search Business Plans', 'golfvista-challenge'),
            'parent_item_colon' => __('Parent Business Plans:', 'golfvista-challenge'),
            'not_found' => __('No business plans found.', 'golfvista-challenge'),
            'not_found_in_trash' => __('No business plans found in Trash.', 'golfvista-challenge')
        );

        $args = array(
            'labels' => $labels,
            'description' => __('Description.', 'golfvista-challenge'),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => false,
            'rewrite' => array('slug' => 'business_plan'),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author'),
            'show_in_rest' => false,
        );

        register_post_type('business_plan', $args);
    }

    /**
     * Notify the admin when a new business plan is submitted.
     *
     * @since 1.0.0
     */
    public function notify_admin_on_business_plan($post_id, $post)
    {
        $admin_email = get_option('admin_email');
        $subject = 'New Business Plan Submitted';
        $message = 'A new business plan has been submitted. View it here: ' . get_edit_post_link($post_id);
        $this->send_notification($admin_email, $subject, $message);
    }

    /**
     * Send a notification email.
     *
     * @since 1.0.0
     */
    public function send_notification($to, $subject, $message)
    {
        wp_mail($to, $subject, $message);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Golfvista_Challenge_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
