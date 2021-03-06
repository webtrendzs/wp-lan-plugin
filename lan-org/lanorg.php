<?php

// Plugin Name: LAN Party Organization
// Plugin URI:  http://lanravageur.github.com/wp-lan-plugin
// Description: Wordpress LAN plugin is used to organize LAN Party.
// Version:     0.0
// Text Domain: lanorg
// Domain Path: /lan-org/

define('LANORG_TEMPLATE_DIR_NAME', 'templates');

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}


if (!class_exists('lanOrg')) :

require('lanorg-admin-list.php');
require('lanorg-form.php');
require('lanorg-event.php');
require('lanorg-tournament.php');
require('lanorg-team.php');
require('lanorg-admin.php');
require('lanorg-account.php');
require('lanorg-registration.php');
require('lanorg-contactMethods.php');
require('lanorg-profile.php');
require('lanorg-widget.php');

// Main Object
class LanOrg {
	public $form_prefix = 'lanorg-';

	// Store the content for custom page
	public $page_content = '';
	public $page_title = '';

	// Setup the LAN Party Organization plugin
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	private function setup_globals() {

		// Main plugin file
		$this->file	= __FILE__;

		// Gets the plugin name based from the file path (e.g., lan-org/lan-org.php)
		$this->basename = plugin_basename($this->file);

		// Plugin directory (e.g., /wwwroot/wp/wp-content/plugins/lan-org/)
		$this->plugin_dir = plugin_dir_path($this->file);

		// Absolute plugin URL (e.g., http://127.0.0.1/wp/wp-content/plugins/lan-org/)
		$this->plugin_url = plugin_dir_url($this->file);

		// path to templates directory
		$this->template_dir = $this->plugin_dir . LANORG_TEMPLATE_DIR_NAME;
	}

	// Includes plugin files
	private function includes() {
	}

	// Set-up plugin actions
	private function setup_actions() {
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		add_action('init', array($this, 'setup_rewrite_tags'));
		add_action('init', array($this, 'setup_post_types'));

		// Adds Translation support
		load_plugin_textdomain( 'lanorg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action('template_redirect', array($this, 'redirect_template'));

		add_shortcode('lanorg-register', 'lanorg_shortcode_registration_form');

		add_action('generate_rewrite_rules', array($this, 'add_rewrite_rules'));
		add_action('admin_init', 'lanorg_add_admin_settings');
		add_action('admin_menu', array($this, 'add_admin_menus'));
		add_filter('query_vars', array($this, 'get_query_vars'));
	}

	function load_static_files() {
		wp_register_style('lanorg-form', plugins_url('css/form.css', __FILE__));
		wp_register_style('lanorg-profile', plugins_url('css/profile.css', __FILE__));
		wp_register_style('lanorg-style', plugins_url('css/style.css', __FILE__));

		wp_register_script('lanorg-history', plugins_url('js/jquery.history.js', __FILE__),
			array('jquery'), FALSE, TRUE);
		wp_register_script('lanorg-ajax', plugins_url('js/ajax.js', __FILE__),
			array('lanorg-history', 'jquery-form', 'jquery'), FALSE, TRUE);
	}

	function setup_rewrite_tags() {
		add_rewrite_tag('%lanorg%','([^&]+)');
	}

	function add_admin_menus() {
		// Main menu option
		add_menu_page('LAN Organization', 'LAN Organization', 'manage_options',
			'lanorg');

		add_submenu_page('lanorg', __('Configuration', 'lanorg'), __('Configuration', 'lanorg'), 'manage_options',
			'lanorg', 'lanorg_admin_settings');

		add_submenu_page('lanorg', __('Events', 'lanorg'), __('Events', 'lanorg'), 'manage_options',
			'lanorg-events', 'lanorg_admin_events');

		add_submenu_page('lanorg', __('Tournaments', 'lanorg'), __('Tournaments', 'lanorg'), 'manage_options',
			'lanorg-tournaments', 'lanorg_admin_tournaments');

		add_submenu_page('lanorg', __('Teams', 'lanorg'), __('Teams', 'lanorg'), 'manage_options',
			'lanorg-teams', 'lanorg_admin_team_page');

		add_submenu_page('lanorg', __('Profile', 'lanorg'), __('Profile', 'lanorg'), 'manage_options',
			'lanorg-profile', 'lanorg_admin_profile');
	}

	function setup_post_types() {
	}

	function get_query_vars($query_vars) {
		$query_vars[] = 'lanorg_page';
		$query_vars[] = 'user_id';
		$query_vars[] = 'tournament_id';
		$query_vars[] = 'lanorg_ajax';
		$query_vars[] = 'event';
		return $query_vars;
	}

	public function add_rewrite_rules($wp_rewrite) {
		$new_rules = array(
			'login/?$' => 'index.php?lanorg_page=login',
			'registration/?$' => 'index.php?lanorg_page=registration',
			'tournament/?([0-9]{1,})/?([0-9]{1,})/?$' => 'index.php?lanorg_page=tournament' .
				'&event=' . $wp_rewrite->preg_index(1) .
				'&tournament_id=' . $wp_rewrite->preg_index(2),
			'tournament/?([0-9]{1,})/?$' => 'index.php?lanorg_page=tournament' .
				'&event=' . $wp_rewrite->preg_index(1),
			'team/?([0-9]{1,})/?$' => 'index.php?lanorg_page=team' .
				'&event=' . $wp_rewrite->preg_index(1),
			'live/?$' => 'index.php?lanorg_page=live',
			'profile/?$' => 'index.php?lanorg_page=profile',
			'profile/?([0-9]{1,})/?$' => 'index.php?lanorg_page=profile&user_id=' . $wp_rewrite->preg_index(1),
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	// Plugin activation code
	public function activate() {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		global $wpdb;

		$table_name = $wpdb->prefix . 'lanorg_events';
		$stmt =
"CREATE TABLE $table_name (
  id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
	title TINYTEXT NOT NULL,
	date DATE NOT NULL,
	location TINYTEXT NOT NULL,
  entry_cost DECIMAL(10, 2) UNSIGNED NOT NULL,
  max_participants BIGINT(20) UNSIGNED NOT NULL,
	UNIQUE KEY id (id)
);";
		dbDelta($stmt);

		$table_name = $wpdb->prefix . 'lanorg_tournaments';
		$stmt =
"CREATE TABLE $table_name (
  id SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id SMALLINT(5) UNSIGNED NOT NULL,
	game TINYTEXT NOT NULL,
	publisher TINYTEXT NOT NULL,
	platform TINYTEXT NOT NULL,
	allow_teams TINYINT(1) NOT NULL,
  max_team MEDIUMINT(5) UNSIGNED NOT NULL,
  team_size MEDIUMINT(5) UNSIGNED NOT NULL,
  entry_cost DECIMAL(10, 2) UNSIGNED NOT NULL,
	UNIQUE KEY id (id)
);";
		dbDelta($stmt);

		$table_name = $wpdb->prefix . 'lanorg_matches';
		$stmt =
"CREATE TABLE $table_name (
  id MEDIUMINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  tournament_id SMALLINT(5) UNSIGNED NOT NULL,
  round SMALLINT(5) UNSIGNED NOT NULL,
  team1_id MEDIUMINT(5) UNSIGNED NOT NULL,
  team2_id MEDIUMINT(5) UNSIGNED NOT NULL,
  winner SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
	UNIQUE KEY id (id),
	UNIQUE KEY id1 (round,team2_id, team1_id,tournament_id),
	UNIQUE KEY id1 (round,team1_id, team2_id,tournament_id)
);";
	dbDelta($stmt);

		$table_name = $wpdb->prefix . 'lanorg_events_users';
		$stmt =
"CREATE TABLE $table_name (
  user_id BIGINT(20) UNSIGNED NOT NULL,
  event_id SMALLINT(5) UNSIGNED NOT NULL,
	UNIQUE KEY event_user_id (user_id,event_id)
);";
		dbDelta($stmt);

		$table_name = $wpdb->prefix . 'lanorg_teams';
		$stmt =
"CREATE TABLE $table_name (
  id MEDIUMINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id BIGINT(20) UNSIGNED NOT NULL,
  tournament_id SMALLINT(5) UNSIGNED NOT NULL,
	name TINYTEXT NOT NULL,
  position MEDIUMINT(5) UNSIGNED NOT NULL,
	UNIQUE KEY id (id)
);";
		dbDelta($stmt);

		$table_name = $wpdb->prefix . 'lanorg_teams_users';
		$stmt =
"CREATE TABLE $table_name (
  team_id MEDIUMINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT(20) UNSIGNED NOT NULL,
	user_accept TINYINT(1) NOT NULL DEFAULT 1,
	team_accept TINYINT(1) NOT NULL DEFAULT 1,
	UNIQUE KEY id (team_id,user_id)
);";
		dbDelta($stmt);

		flush_rewrite_rules();
	}

	// Called when the plugin is desactivated
	public function deactivate() {
	}

	public function redirect_template() {
		global $wp_query;

		$this->load_static_files();

		$event_id = (int) get_query_var('event');

		if (isset($wp_query->query_vars['lanorg_page']))
		{
			switch ($wp_query->query_vars['lanorg_page']) {
			case 'registration':
				lanorg_registration_page();
				break ;
			case 'team':
				lanorg_render_team_page($event_id);
				break ;
			case 'tournament':
				$tournament_id = NULL;
				if (isset($wp_query->query_vars['tournament_id'])) {
					$tournament_id = (int) get_query_var('tournament_id');
				}
				lanorg_render_tournament_page($event_id, $tournament_id);
				break ;
			case 'live':
				$this->render_two_column_page('lanorg-live.php');
				break ;
			case 'login':
				$this->render_custom_page('lanorg-login.php');
				break ;
			case 'profile':
				$user_id = isset($wp_query->query_vars['user_id']) ? $wp_query->query_vars['user_id'] : 0;
				lanorg_profile_page($user_id);
				break ;
			}
		}

		lanorg_process_registration_form();
	}

	// Lookup first in the theme directory for $template_file, if not found then
	// fallback to the template directory in plugin directory.
	public function resolve_template_file($template_file) {
		$template_file_path = TEMPLATEPATH . '/' . $template_file;

		if (!file_exists($template_file_path)) {
			$template_file_path = $this->template_dir . '/' . $template_file;
		}
		return $template_file_path;
	}

	// Render a given template, overwriting current template
	public function render_template($template_file) {
		$template_file_path = $this->resolve_template_file($template_file);
		include($template_file_path);
	}

	public function get_custom_page_title($title, $sep=' | ', $seplocation='') {
		return $this->page_title . $sep;
	}

	public function get_custom_page_content() {
		return $this->page_content;
	}

	// Render a given page with a two column template
	public function render_two_column_page($page_file) {
		$GLOBALS['content_template'] = $this->resolve_template_file($page_file);

		wp_enqueue_style('lanorg-style');
		wp_enqueue_script('lanorg-ajax');

		$this->render_custom_page('lanorg-twocolumn.php');
	}

	function get_body_class($classes='') {
		$classes[] = 'singular';
		return $classes;
	}

	// Render a given page
	public function render_custom_page($page_file) {
		global $wp_query;
		$wp_query->is_home = FALSE; // Not homepage
		$wp_query->is_single = TRUE; // Single page

		// Add our custom class to force single column
		add_filter('body_class', array($this, 'get_body_class'));

		//add_filter('the_title', array($this, 'get_custom_page_title'));
		add_filter('wp_title', array($this, get_custom_page_title));
		add_filter('the_content', array($this, 'get_custom_page_content'));

		$GLOBALS['page_title'] = ''; // This variable is set by the template

		// Turn on output buffering to capture page content
		ob_start();

		$this->render_template($page_file);

		// Get buffered content
		$this->page_content = ob_get_clean();

		$this->page_title = $GLOBALS['page_title'];

		$ajax = isset($wp_query->query_vars['lanorg_ajax']) || isset($_POST['lanorg_ajax']);
		$template_page =	$ajax ?
											'lanorg-ajax.php' : 'lanorg-page.php';
		$this->render_template($template_page);
		exit ;
	}

	// Require the user to be logged in or redirects to the login page
	public function require_login($url = '') {
		global $wp_rewrite;

		if (!is_user_logged_in()) {
			$redirect_to = !empty($url) ? $url : $wp_rewrite->root . 'login/';
			wp_safe_redirect(home_url($redirect_to));
			exit ;
		}
	}
}

$GLOBALS['lanOrg'] = new LanOrg();

endif;

?>