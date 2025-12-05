<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WPGP_Custom_Login')) {
    class WPGP_Custom_Login {
        public function __construct() {
            add_action('init', [$this, 'init']);
            add_filter('site_url', [$this, 'filter_site_url'], 10, 4);
            add_filter('network_site_url', [$this, 'filter_site_url'], 10, 3);
            add_filter('wp_redirect', [$this, 'filter_wp_redirect'], 10, 2);
            add_action('wp_loaded', [$this, 'handle_redirects']);
        }

        public function init() {
            $slug = $this->get_slug();
            if ($slug) {
                add_rewrite_rule($slug . '/?$', 'wp-login.php', 'top');
                add_rewrite_rule($slug . '/([^/]+)/?$', 'wp-login.php?action=$matches[1]', 'top');
            }
        }

        public function get_slug() {
            $opts = get_option('wpgp_options', []);
            return !empty($opts['custom_login_slug']) ? trim($opts['custom_login_slug']) : false;
        }

        public function filter_site_url($url, $path, $scheme, $blog_id = null) {
            return $this->replace_login_url($url, $scheme);
        }

        public function filter_wp_redirect($location, $status) {
            return $this->replace_login_url($location);
        }

        private function replace_login_url($url, $scheme = null) {
            $slug = $this->get_slug();
            if (!$slug) return $url;

            if (strpos($url, 'wp-login.php') !== false) {
                $url = str_replace('wp-login.php', $slug, $url);
            }
            return $url;
        }

        public function handle_redirects() {
            $slug = $this->get_slug();
            if (!$slug) return;

            // Block direct access to wp-login.php
            $pagenow = $GLOBALS['pagenow'] ?? '';
            if ($pagenow === 'wp-login.php' && !isset($_GET['action'])) {
                // If we are NOT on the custom slug (which maps to wp-login.php internally), redirect
                // But wait, internal rewrite maps to wp-login.php, so pagenow IS wp-login.php.
                // We need to check the REQUEST_URI.
                if (strpos($_SERVER['REQUEST_URI'], $slug) === false) {
                    // This is a direct hit to wp-login.php
                    wp_safe_redirect(home_url());
                    exit;
                }
            }
        }
        
        public function activate() {
            $this->init();
            flush_rewrite_rules();
        }
    }
}
