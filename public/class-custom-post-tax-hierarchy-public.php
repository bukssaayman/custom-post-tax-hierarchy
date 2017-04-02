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

		$options = get_option('cpth_settings');
		$this->arr_cpt_for_rewrite = $options['selected_cpt'];

		add_filter('generate_rewrite_rules', array(&$this, 'rewriteRulesForCustomPostTypeAndTax'));
		add_filter('post_type_link', array(&$this, 'cpth_admin_link'), 1, 2);
	}

	protected function getTermHierarchy($term, $custom_tax) {
		array_push($this->arr_customPostTermSlug, $term->slug);
		if ($term->parent > 0) {
			$this->getTermHierarchy(get_term($term->parent), $custom_tax);
		}
	}

	function getSlugsForPostTax($id) {
		$links = array();
		$taxonomy = get_post_taxonomies($id);
		$terms = get_the_terms($id, $taxonomy[0]);
		if (is_wp_error($terms))
			return $terms;
		if (empty($terms))
			return false;
		foreach ($terms as $term) {
			$link = get_term_link($term, $taxonomy);
			if (is_wp_error($link)) {
				return $link;
			}
			$links[] = esc_url(str_replace(home_url(), '', $link));
		}
		return $links;
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
		$tax_rules = array();
		$custom_post_rules = array();

		foreach ($this->arr_cpt_for_rewrite as $post_type) {
			$args = array(
					'post_type' => $post_type,
					'posts_per_page' => -1
			);
			$custom_post_type_posts = new WP_Query($args);
			foreach ($custom_post_type_posts->posts as $post_key => $post_val) {
				$arr_slugs = $this->getSlugsForPostTax($post_val->ID);

				//$base_taxonomy = $post_type['basepage']->post_name;
				if (!empty($arr_slugs)) { //there are more than one slug for the same post, create a rule for each
					foreach ((array) $arr_slugs as $slug_key => $slug_val) {
						$single_post_slug = explode('/', $slug_val);
						$single_post_slug[] = $post_val->post_name; //add the post name at the end of the array
						$single_post_slug = array_values(array_filter($single_post_slug)); //re-index after removing all the empty keys from array
						//$single_post_slug[0] = $base_taxonomy; //replace the old base taxonomy with the new one.
						$single_post_slug = implode('/', $single_post_slug) . '-' . $post_val->ID;
						$custom_post_rules['^' . $single_post_slug . '$'] = 'index.php?' . $post_type . '=' . $post_val->post_name;
					}
				} else { //only one slug available, create the rule
					$single_post_slug = '/' . $post_val->post_name . '-' . $post_val->ID;
					$custom_post_rules['^' . $single_post_slug . '$'] = 'index.php?' . $post_type . '=' . $post_val->post_name;
				}
			}
			$arr_categories = get_categories(array('type' => $post_type, 'taxonomy' => get_post_taxonomies($post_val), 'hide_empty' => 0));
			foreach ($arr_categories as $category) {
				$tax_rules['^' . $base_taxonomy . '/' . $category->slug . '/?$'] = 'index.php?' . $category->taxonomy . '=' . $category->slug;
			}
		}

		$final_rules = array_merge($custom_post_rules, $tax_rules);
		$wp_rewrite->rules = $final_rules + $wp_rewrite->rules;
	}

	public function cpth_admin_link($post_link, $post = NULL) {
		
		$cpt_base_slug = sanitize_title_with_dashes(get_post_type($post->ID)); //this should later be content managed

		$this->arr_customPostTermSlug = array(); //re-initialize the array
//		if (!empty($this->arr_list_of_posst_taxs[get_post_type($post->ID)])) { //this is for a custom post type
//			$custom_tax = $this->arr_list_of_post_taxs[get_post_type($post->ID)];
//			if (!empty($custom_tax)) { //I'm inside one of my custom taxonomies
		if (is_admin()) {
			$terms = wp_get_object_terms($post->ID, get_post_taxonomies($post), array('fields' => 'all'));

			$terms = count($terms) > 0 ? $terms[0] : $terms;
		} else {
			$terms = $this->getTermFromCurrentURL($post);
		}

		if (!empty($terms)) { //add the terms into the url structure
			$this->getTermHierarchy($terms, $custom_tax['name']);
			
			
			$slug = implode('/', array_reverse($this->arr_customPostTermSlug));
			$post_link = '/' .$cpt_base_slug.'/'. $slug . '/' . $post->post_name . '-' . $post->ID . '/';
		} else { //append post-id to any custom posts not inside a taxonomy
			if (is_a($post, 'WP_Term')) { //this is a term link
				$post_link = '/' . $post->slug . '/';
			} else {
				$post_link = $post->slug . '/' . $post->post_name . '-' . $post->ID . '/';
			}
		}
		//}
		//}
//		if (is_a($post, 'WP_Term')) { //this is a term link
//			$post_link_parts = array_values(array_filter(explode('/', str_replace(home_url(), '', $post_link))));
//			$taxonomy = 'td_' . $post_link_parts[0];
//			if (!empty($this->arr_list_of_post_taxs[$taxonomy])) {
//				$updated_taxonomy = $this->arr_list_of_post_taxs[$taxonomy]['basepage']->post_name;
//				$post_link = str_replace($post_link_parts[0], $updated_taxonomy, $post_link);
//			}
//		}
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
