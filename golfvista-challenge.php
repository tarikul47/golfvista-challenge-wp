<?php
/**
 * Plugin Name:       Golfvista Brain & Beauty Challenge
 * Plugin URI:        https://golfvista.com/
 * Description:       A plugin to manage the Brain & Beauty Challenge, including media uploads, payments, and quizzes.
 * Version:           1.0.0
 * Author:            Golfvista
 * Author URI:        https://golfvista.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       golfvista-challenge
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-golfvista-challenge.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_golfvista_challenge()
{

    $plugin = new Golfvista_Challenge();
    $plugin->run();

}
run_golfvista_challenge();

