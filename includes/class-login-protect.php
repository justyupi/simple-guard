<?php
if (!defined('ABSPATH')) exit;

class WPGP_Login_Protect {
    private $ban;
    private $turnstile;

    public function __construct($ban_manager, $turnstile) {
        $this->ban = $ban_manager;
        $this->turnstile = $turnstile;
    }

    public function handle_success_login($user_login, $user){
        $ip = wpgp_get_client_ip();
        $opts = get_option('wpgp_options');
        if (!empty($opts['reset_on_success'])){
            $this->ban->reset_fail_count($ip);
        }
    }


    public function block_if_banned(){
        // only block access to wp-login.php (and auth endpoints)
        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') === false) return;
        $ip = wpgp_get_client_ip();
        if (wpgp_is_ip_whitelisted($ip)) return;
        if ($this->ban->is_banned($ip)){
            wp_die(sprintf(__('Your IP (%s) is temporarily banned.'), esc_html($ip)), 403);
        }
    }


    // registration handler: run turnstile validation early
    public function validate_turnstile_on_register($user_login, $user_email, $errors){
        $opts = get_option('wpgp_options');
        if (empty($opts['turnstile_enabled'])) return;
        $token = $_POST['cf-turnstile-response'] ?? '';
        if (empty($token)){
            $errors->add('wpgp_captcha', __('Please complete the CAPTCHA (Turnstile).'));
            return;
        }
        if (!$this->turnstile->verify($token)){
            $errors->add('wpgp_captcha_failed', __('CAPTCHA verification failed.'));
            return;
        }
    }


    public function validate_turnstile_on_lostpassword(){
        $opts = get_option('wpgp_options');
        if (empty($opts['turnstile_enabled'])) return;
        $token = $_POST['cf-turnstile-response'] ?? '';
        if (empty($token)){
            wp_die(__('Please complete the CAPTCHA (Turnstile).'), 400);
        }
        if (!$this->turnstile->verify($token)){
            wp_die(__('CAPTCHA verification failed.'), 400);
        }
    }
}