<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://golfvista.com/
 * @since      1.0.0
 *
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Golfvista_Challenge
 * @subpackage Golfvista_Challenge/includes
 * @author     Golfvista <your-email@example.com>
 */
class Golfvista_Challenge_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'golfvista-challenge',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}
