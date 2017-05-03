<?php

class Custom_Post_Tax_Hierarchy_Public {

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
	private $arr_customPostTermSlug = array();
	private $arr_cpt_for_rewrite = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->cpth_filters_hooks();
		$this->cpth_get_list_of_cpts();
		$this->cpth_check_flush_rewrites();
	}

	private function cpth_filters_hooks() {
		add_filter('generate_rewrite_rules', array(&$this, 'rewriteRulesForCustomPostTypeAndTax'));
		add_filter('post_type_link', array(&$this, 'cpth_url_link'), 1, 2);

		add_action('save_post', array(&$this, 'cpth_post_save_edit'), 1, 2);
		add_action('edit_post', array(&$this, 'cpth_post_save_edit'), 1, 2);

		add_action('wp_footer', array(&$this, 'cpth_add_footer_comment'));
	}

	public function cpth_add_footer_comment() {
		echo "<!-- SEO boosted by : https://wordpress.org/plugins/custom-post-taxonomy-hierarchy-seo/ --> \n";
	}

	public function cpth_post_save_edit($post_ID, $post_obj) {
		$this->cpth_flush_rewrites();
	}

	private function cpth_flush_rewrites() {
		flush_rewrite_rules();
	}

	private function cpth_check_flush_rewrites() {
		$str_option_name = 'cpth_settings_md5';
		$arr_option_cptmd5 = get_option($str_option_name);
		$md5_cpts = md5(json_encode($this->arr_cpt_for_rewrite));
		if (empty($arr_option_cptmd5)) {
			update_option($str_option_name, $md5_cpts);
		} else {
			if ($arr_option_cptmd5 != $md5_cpts) { //CPT's have changed flush rewrite rules
				$this->cpth_flush_rewrites();
				update_option($str_option_name, $md5_cpts);
			}
		}
	}

	private function cpth_get_list_of_cpts() {
		$arr_all_registered_cpts = get_post_types(array(), 'objects');

		$options = get_option('cpth_settings');
		if (!empty($options['selected_cpt'])) {
			foreach ($options['selected_cpt'] as $cpt) {
				$this->arr_cpt_for_rewrite[$cpt] = $arr_all_registered_cpts[$cpt];
			}
		}
	}

	protected function getTermHierarchy($arr_term, $custom_tax) {
		foreach ($arr_term as $term) {
			array_push($this->arr_customPostTermSlug, $term->slug);
			if ($term->parent > 0) {
				$this->getTermHierarchy(get_term($term->parent), $custom_tax);
			}
		}
	}

	public function getSlugsForPostTax($id) {
		$links = array();
		$taxonomy = get_post_taxonomies($id);
		$terms = get_the_terms($id, $taxonomy[0]);

		if (is_wp_error($terms))
			return $terms;
		if (empty($terms))
			return false;

		$this->arr_customPostTermSlug = array(); //reset the hierarchy
		$this->getTermHierarchy($terms, $taxonomy[0]);

		$terms = array_filter($this->arr_customPostTermSlug);
		foreach ($terms as $term) {
			$links[] = $term;
		}
		return implode('/', array_reverse($links));
	}

	public function getTermFromCurrentURL($post) {
		$arr_terms = wp_get_object_terms($post->ID, get_post_taxonomies($post), array('fields' => 'all'));
		foreach ($arr_terms as $term) {
			$check = $term->slug;
			if ($term->parent > 0) {
				$parent = get_term($term->parent);
				$check = $parent->slug;
			}
			if (stristr($this->current_url, $check)) {
				return $term;
			}
		}
	}

	public function rewriteRulesForCustomPostTypeAndTax($wp_rewrite) {

		if (empty($this->arr_cpt_for_rewrite)) {
			return;
		}

		$tax_rules = array();
		$custom_post_rules = array();

		foreach ($this->arr_cpt_for_rewrite as $post_type) {

			$args = array(
					'post_type' => $post_type->name,
					'posts_per_page' => -1
			);

			$custom_post_type_posts = new WP_Query($args);
			foreach ($custom_post_type_posts->posts as $post_key => $post_val) {

				$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post_val->ID)]->name;

				if (!empty($this->arr_cpt_for_rewrite[get_post_type($post_val->ID)]->rewrite['slug'])) {
					$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post_val->ID)]->rewrite['slug'];
				}

				$arr_slugs = $this->getSlugsForPostTax($post_val->ID);

				if (!empty($arr_slugs)) {
					foreach ((array) $arr_slugs as $slug_key => $slug_val) {
						$single_post_slug = array();
						$single_post_slug[] = $cpt_base_slug; //replace the old base taxonomy with the new one.
						$single_post_slug[] = $slug_val; //add the post name at the end of the array
						$single_post_slug[] = $post_val->post_name; //add the post name at the end of the array
						$single_post_slug = implode('/', $single_post_slug) . '-' . $post_val->ID;
						$custom_post_rules['^' . $single_post_slug . '$'] = 'index.php?' . $post_type->name . '=' . $post_val->post_name;
					}
				} else { //only one slug available, create the rule
					$single_post_slug = $cpt_base_slug . '/' . $post_val->post_name . '-' . $post_val->ID;
					$custom_post_rules['^' . $single_post_slug . '$'] = 'index.php?' . $post_type->name . '=' . $post_val->post_name;
				}
			}
		}

		$final_rules = array_merge($custom_post_rules, $tax_rules);
		$wp_rewrite->rules = $final_rules + $wp_rewrite->rules;
	}

	public function cpth_url_link($post_link, $post = NULL) {
		
		if(empty($this->arr_cpt_for_rewrite[get_post_type($post->ID)])){
			return $post_link;
		}
		
		$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post->ID)]->name;

		if (!empty($this->arr_cpt_for_rewrite[get_post_type($post->ID)]->rewrite['slug'])) {
			$cpt_base_slug = $this->arr_cpt_for_rewrite[get_post_type($post->ID)]->rewrite['slug'];
		}

		$terms = wp_get_object_terms($post->ID, get_post_taxonomies($post), array('fields' => 'all'));

		if (!empty($terms)) { //add the terms into the url structure
			$this->arr_customPostTermSlug = array(); //re-initialize the array
			$this->getTermHierarchy($terms, get_post_type($post->ID));

			$slug = implode('/', array_reverse(array_filter($this->arr_customPostTermSlug)));

			$post_link = home_url() . '/' . $cpt_base_slug . '/' . $slug . '/' . $post->post_name . '-' . $post->ID . '/';
		} else { //append post-id to any custom posts not inside a taxonomy
			if (is_a($post, 'WP_Term')) { //this is a term link
				$post_link = '/' . $post->slug . '/';
			} else {
				$post_link = home_url() . '/' . $cpt_base_slug . '/' . $post->post_name . '-' . $post->ID . '/';
			}
		}

		return $post_link;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/custom-post-tax-hierarchy-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/custom-post-tax-hierarchy-public.js', array('jquery'), $this->version, false);
	}

}
