<?php

class Custom_Post_Tax_Hierarchy_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('admin_menu', array(&$this, 'cpth_add_admin_menu'));
		add_action('admin_init', array(&$this, 'cpth_settings_init'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/custom-post-tax-hierarchy-tax-hierarchy-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/custom-post-tax-hierarchy-tax-hierarchy-admin.js', array('jquery'), $this->version, false);
	}

	public function cpth_add_admin_menu() {

		add_menu_page('Custom Post Tax Hierarchy', 'Custom Post Tax Hierarchy', 'manage_options', 'custom_post_tax_hierarchy', array(&$this, 'cpth_options_page'));
	}

	public function cpth_settings_init() {

		register_setting('cpth_admin_options', 'cpth_settings');

		add_settings_section(
				'cpth_cpth_admin_options_section', __('', 'wordpress'), array(&$this, 'cpth_settings_section_callback'), 'cpth_admin_options'
		);

		add_settings_field(
				'cpth_select_cpt', __('Choose which custom post types to apply SEO friendly URL structures to:', 'wordpress'), array(&$this, 'cpth_select_cpt'), 'cpth_admin_options', 'cpth_cpth_admin_options_section'
		);
	}

	public function cpth_select_cpt() {
		$args = array(
				'public' => true,
				'_builtin' => false
		);
		$arr_post_types = get_post_types($args);

		$options['selected_cpt'] = array();
		if(!empty(get_option('cpth_settings'))){
			$options = get_option('cpth_settings');
		}
		?>
		<ul>
			<?php
			foreach ($arr_post_types as $key => $value) {
				?>
				<li><input <?php echo in_array($key, $options['selected_cpt'], true) ? 'checked' : '' ?> type='checkbox' name='cpth_settings[selected_cpt][]' value='<?php echo $key; ?>'><?php echo $value; ?></li>
				<?php
			}
			?>
		</ul>
		<?php
	}

	public function cpth_settings_section_callback() {

		echo __('Settings for Custom Post Type and Taxonomy hierachy', 'wordpress');
	}

	public function cpth_options_page() {
		?>
		<form action='options.php' method='post'>

			<h2>Custom Post Tax Hierarchy</h2>

			<?php
			settings_fields('cpth_admin_options');
			do_settings_sections('cpth_admin_options');
			submit_button();
			?>

		</form>
		<?php
	}

}
