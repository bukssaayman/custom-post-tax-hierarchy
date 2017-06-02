<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Plugin_Name
 *
 * @wordpress-plugin
 * Plugin Name:       Custom Post & Taxonomy Hierarchy SEO
 * Description:       Boost your SEO by including your custom post taxonomy and terms in your URL structure.
 * Version:           1.0.3
 * Author:            Buks Saayman
 * Author URI:        http://bukssaayman.co.za/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wordpress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-custom-post-tax-hierarchy-activator.php
 */
function activate_custom_post_tax_hierarchy() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-tax-hierarchy-activator.php';
	Custom_Post_Tax_Hierarchy_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-custom-post-tax-hierarchy-deactivator.php
 */
function deactivate_custom_post_tax_hierarchy() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-tax-hierarchy-deactivator.php';
	Custom_Post_Tax_Hierarchy_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_custom_post_tax_hierarchy' );
register_deactivation_hook( __FILE__, 'deactivate_custom_post_tax_hierarchy' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-custom-post-tax-hierarchy.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_custom_post_tax_hierarchy() {

	$plugin = new Custom_Post_Tax_Hierarchy();
	$plugin->run();

}
add_action( 'wp_loaded', 'run_custom_post_tax_hierarchy' ); //this needs to run only after all the CPTs and Taxs have been registered