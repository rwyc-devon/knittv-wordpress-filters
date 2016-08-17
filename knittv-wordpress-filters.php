<?php
/*
Plugin Name: KnitTV Taxonomy Filters
Description: Taxonomies and Filters For the KnitTV Site
Version:     0.1
Author:      Devon Sawatzky
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/
define("WP_DEBUG_LOG", true);

function dumpp($var)
{
	ob_start();
	var_dump($var);
	$contents = ob_get_contents();
	ob_end_clean();
	error_log($contents);
}
/*
 * Register Taxonomies
 */
function knittv_get_taxonomies()
{
	return [
		"difficulty" => [
			"label"=> "Difficulty",
			"defaults"=> [
				"Beginner",
				"Intermediate",
				"Advanced"
			]
		],
		"technique" => [
			"label"=> "Technique",
			"defaults"=> [
				"Knit",
				"Crochet",
			]
		]
	];
}
function knittv_register_taxonomies()
{
	$taxonomies=knittv_get_taxonomies();
	foreach($taxonomies as $name=>$tax) {
		register_taxonomy($name, "post", [ "label"=>$tax["label"], "rewrite"=>["slug"=>$name] ] );
		if(isset($name["defaults"])) {
			foreach($name["defaults"] as $val) {
				wp_insert_term($val, $name);
			}
		}
	}
}

/*
 * Enqueue Styles and Scripts
 */
function knittv_enqueue_scripts() {
	wp_enqueue_style('knittv-filters', plugin_dir_url(__FILE__) . '/style.css');
	wp_enqueue_script('knittv-filters-submit', plugin_dir_url(__FILE__) . '/submit.js', array(), false, true);
}
/*
 * Metabox Stuff
 */
function knittv_metabox($post) {
	$taxonomies=knittv_get_taxonomies();
	$nonce=wp_create_nonce('knittv_metabox');
	echo "<input type='hidden' name='knittv_metabox_nonce' value='$nonce'>";
	foreach($taxonomies as $name=>$tax) {
		knittv_metabox_select($name);
	}
	echo "</label>";
}
function knittv_metabox_select($taxonomy) {
	global $post;
	$name=get_taxonomy($taxonomy)->label;
	$terms=get_terms($taxonomy, "hide_empty=0");
	echo "<label style='display: block; overflow: hidden'>$name\n";
	echo "<select style='float:right; clear: both' name='post-$taxonomy'>";
	$post_terms=get_the_terms(get_the_ID(), $taxonomy);
	foreach ($terms as $term) {
		$selected=(!is_wp_error($terms) && !empty($terms) && (strcmp($term->slug, $post_terms[0]->slug) == 0))?" selected":"";
		$value=$term->slug;
		$text=$term->name;
		echo "<option value='$value'$selected>$text</option>\n";
	}
	echo "</select>\n";
	echo "</label>\n";
}
function add_knittv_metabox() {
	add_meta_box('knittv-metabox', __('KnitTV Metadata'), 'knittv_metabox', 'post', 'side', 'core');
	$taxonomies=knittv_get_taxonomies();
	foreach($taxonomies as $name=>$tax) {
		remove_meta_box("tagsdiv-$name", "post", "core");
	}
}

function save_knittv_meta($postId) {
	$taxonomies=knittv_get_taxonomies();
	if(
		!wp_verify_nonce($_POST["knittv_metabox_nonce"], "knittv_metabox") ||        #check nonce
		(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) ||                             #only save if it's explicitly submitted; not for autosaves
		($_POST["post_type"]=="page" && !current_user_can("edit_page", $postId)) ||  #check privileges
		($_POST["post_type"]=="post" && !current_user_can("edit_post", $postId))
	) {
		return $postId;
	}
	$post=get_post($post_id);
	if($post->post_type=="post" || $post->post_type=="page") {
		foreach($taxonomies as $name=>$tax) {
			if(isset($_POST["post-$name"])) wp_set_object_terms($postId, $_POST["post-$name"], $name);
		}
	}
}

/*
 * Widget
 */
function knittv_enqueue_widget_scripts() {
	wp_enqueue_script("knittv-widget-edit-script", plugin_dir_url(__FILE__) . "/edit.js", array(), false, true);
	wp_register_style("knittv-widget-edit-style", plugin_dir_url(__FILE__) . "/edit.css");
	wp_enqueue_style("knittv-widget-edit-style");
}
class KnittvFilter extends WP_Widget {
	function __construct() {
		parent::__construct(false, __("Filters"), array('description'=>'Filter Widget for KnitTV Taxonomies'));
	}
	function widget($args, $instance) {
		global $wp_query;
		#echo "<pre>";var_dump($wp_query);echo "</pre>";
		$classes="knittv-widget knittv-filter";
		$classes.=($instance["submitonchange"]?" submitOnChange": "");
		$classes.=($instance["popupfilters"]?" popupFilters": "");
		echo "<form method='get' class='$classes' data-prefix='${instance["prefix"]}'>";
		if($instance['showsearch']){
			echo "<label class='knittv-search'><input name='s' value='".get_search_query()."'></input><input type='submit' value='".__("Search")."'></input></label>";
		}
		elseif(get_search_query() != "") {
			echo "<input name='s' value='".get_search_query()."' hidden></input>";
		}
		$taxonomies=array();
		if($instance["taxes"]) {
			foreach($instance["taxes"] as $name) {
				array_push($taxonomies, get_taxonomy($name));
			}
		}
		if($instance["showcategories"]) $taxonomies=array_merge($taxonomies, get_taxonomies(array("name"=>"category"), "objects")); #append category taxonomy if enabled
		if($instance['showfilters']) {
			echo "<h2 class='widget-title'>Filters</h2>";
			echo "<input class='reset' type='reset'></input>";
		}
		foreach($taxonomies as $tax) {
			$slug=$tax->rewrite["slug"];
			$query=get_query_var($tax->query_var);
			$parent="";
			if($tax->name=="category") {
				$root_cat=explode("/", $wp_query->query["category_name"], 2)[0];
				$parent=get_term_by("slug", $root_cat, $tax->name);
				$terms=get_terms(array("taxonomy"=>$tax->name, "orderby"=>$instance["orderby"], "hide_empty"=>$instance["hideempty"], "parent"=>$parent->term_id));
			}
			else {
				$terms=get_terms(array("taxonomy"=>$tax->name, "orderby"=>$instance["orderby"], "hide_empty"=>$instance["hideempty"]));
			}
			if($instance['showfilters']) {
				$class=(count($terms)<3)?" class='small'":"";
				echo "<h3>$tax->label</h3>";
				echo "<div$class>";
				$slug=($parent!="")? $parent->slug: "";
				$checked=($query && $query==$parent->query_var)? "": " checked";
				echo "<input id='knittv-filter-$tax->name-all' type='radio' value='$slug' name='$tax->query_var'$checked></input><label tabindex='0' for='knittv-filter-$tax->name-all'>All</label>";
			}
			foreach($terms as $term) {
				$checked=($query==$term->slug)? " checked" : "";
				$id="knittv-filter-$tax->name-$term->slug";
				if($checked || $instance['showfilters']) {
					echo "<input id='$id' type='radio' value='$term->slug' name='$tax->query_var'$checked></input>";
				}
				if($instance['showfilters']) {
					echo "<label tabindex='0' for='$id'>$term->name</label>";
				}
			}
			echo "</div>";
		}
		echo "</form>";
	}
	function form($instance) {
		$nonce=wp_create_nonce('knittv_widget_form');
		echo "<input type='hidden' name='knittv_widget_nonce' value='$nonce'>";
		$this->knittv_checkbox($instance, 'showsearch', 'Show Search');
		$this->knittv_checkbox($instance, 'showfilters', 'Show Filters');
		echo "<div class='filterchooser ifprev'>";
		echo "<h3>Filters</h3>";
		$this->knittv_taxonomies($instance);
		echo "</div>";
		$this->knittv_checkbox($instance, 'submitonchange', 'Auto Submit');
		$this->knittv_checkbox($instance, 'popupfilters', 'Pop Up Filters');
		$this->knittv_checkbox($instance, 'hideempty', 'Hide Empty Terms');
		$this->knittv_input($instance, 'orderby', 'Order Terms By');
		$this->knittv_input($instance, 'prefix', 'URL Prefix');
		echo "<p>Set a path to apply searches and filters to only a subset of the site. All filter urls will be prefixed with this path.</p>";
		echo "<p>For example, if a user searches <code>'cats'</code> the resulting URL will be <code>?s=cats</code>, whereas if the prefix is set to <code>gifs</code> the URL will be <code>/gifs?s=cats</code></p>";
	}
	function knittv_taxonomies($instance) {
		$n=0;
		while(isset($instance["taxes"]) && isset($instance["taxes"][$n]) && $instance["taxes"][$n]) {
			$this->knittv_tax_select($n, $instance["taxes"][$n]);
			$n++;
		}
		$this->knittv_tax_select($n, "");
	}
	function knittv_tax_select($n, $val) {
		$name=esc_attr($this->get_field_name("tax" . sprintf("%02d", $n)));
		$class=($n==0)?" class='first'":"";
		echo "<select$class name='$name' value='$val' autocomplete='off'>";
			$taxonomies=get_taxonomies(array("public"=>true, "show_ui"=>true), "objects");
			echo "<option value=''></option>";
			foreach($taxonomies as $tax) {
				$selected=($tax->name==$val)? " selected": "";
				echo "<option value='$tax->name'$selected>$tax->label</option>";
			}
		echo "</select>";
	}
	function knittv_checkbox($instance, $attribute, $label) {
		$name=esc_attr($this->get_field_name($attribute));
		$id=esc_attr($this->get_field_id($attribute));
		$checked=$instance[$attribute]? " checked": "";
		echo "<input type='checkbox' id='$id' name='$name'$checked></input><label for='$id'>$label</label>";
	}
	function knittv_input($instance, $attribute, $label, $default="") {
		$name=esc_attr($this->get_field_name($attribute));
		$id=esc_attr($this->get_field_id($attribute));
		$value=esc_attr(isset($instance[$attribute])? $instance[$attribute]: $default);
		echo "<label for='$id'>$label</label><input id='$id' name='$name' value='$value'></input>";
	}
	function update($new, $old) {
		$instance=array();
		if(wp_verify_nonce($_POST["knittv_widget_nonce"], "knittv_widget_form")) {
			#extract taxonomies array
			$taxkeys=preg_grep('/^tax[0-9][0-9]$/', array_keys($new));
			asort($taxkeys);
			$instance["taxes"]=[];
			foreach($taxkeys as $i) {
				if($new[$i]) {
					array_push($instance["taxes"], $new[$i]);
				}
			}
			foreach(array("showsearch", "showfilters", "showcategories", "submitonchange", "popupfilters", "hideempty") as $i) {
				$instance[$i]=(isset($new[$i]) && ($new[$i]=="on"));
			}
			$instance["orderby"]=$new["orderby"];
			$instance["prefix"]=$new["prefix"];
			return $instance;
		}
		else {
			return $old;
		}
	}
}
function knittv_register_widgets() {
	register_widget("KnittvFilter");
}

/*
 * Actually calling the stuff
 */
if(defined('ABSPATH')) { #don't actually do anything if this file was directly requested
	add_action( 'wp_enqueue_scripts', 'knittv_enqueue_scripts' );
	register_activation_hook("knittv/knittv.php", knittv_activate);
	register_deactivation_hook("knittv/knittv.php", knittv_deactivate);
	add_action('init', 'knittv_register_taxonomies');
	add_action('widgets_init', 'knittv_register_widgets');
	if(is_admin()) {
		add_action('save_post', 'save_knittv_meta');
		add_action('admin_menu', 'add_knittv_metabox');
		add_action( 'admin_enqueue_scripts', 'knittv_enqueue_widget_scripts' );
	}
}
