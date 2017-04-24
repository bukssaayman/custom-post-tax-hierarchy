<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Custom Post Tax Hierarchy
 * @subpackage custom_post_tax_hierarchy/includes
 * @author     Your Name <email@example.com>
 */
class Custom_Post_Tax_Hierarchy_Deactivator {

	/**
	 * Method to run when deactivating the plugin.
	 *
	 * When deactivating the plugin flush the rewrite rules.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

}
