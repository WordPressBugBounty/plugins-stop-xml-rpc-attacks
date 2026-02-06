<?php
/*
  Plugin Name: Stop XML-RPC Attacks
  Description: Blocks dangerous XML-RPC methods while preserving Jetpack, WooCommerce, and mobile apps. Choose between full disable, guest-only disable, or selective blocking.
  Author: Pascal CESCATO
  Version: 2.0.0
  Author URI: https://zone-test.ovh
  License: GPLv3
  Text Domain: stop-xml-rpc-attacks
  Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('SXRA_VERSION', '2.0.0');

class Stop_XMLRPC_Attacks {
    private static $instance = null;
    private $option_name = 'sxra_settings';
    private $default_blocked_methods = [
        'system.multicall',
        'system.listMethods',
        'system.getCapabilities',
        'pingback.ping',
        'pingback.extensions.getPingbacks',
    ];
    
    private $enumeration_methods = [
        'wp.getUsersBlogs',
        'wp.getCategories',
        'wp.getTags',
    ];

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin interface
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        }

        // Get settings
        $settings = $this->get_settings();
        $mode = $settings['security_mode'];

        // Apply security mode
        switch ($mode) {
            case 'full_disable':
                add_filter('xmlrpc_enabled', '__return_false');
                break;

            case 'guest_disable':
                add_filter('xmlrpc_enabled', [$this, 'disable_for_guests']);
                add_filter('xmlrpc_methods', [$this, 'block_dangerous_methods']);
                break;

            case 'selective':
            default:
                add_filter('xmlrpc_methods', [$this, 'block_dangerous_methods']);
                break;
        }

        // Always remove headers and links
        add_action('init', [$this, 'remove_headers_and_links'], 1);
        add_action('send_headers', [$this, 'remove_pingback_header']);

        // Optional logging
        if ($settings['enable_logging']) {
            add_filter('xmlrpc_call', [$this, 'log_blocked_attempts']);
        }
    }

    private function get_settings() {
        $defaults = [
            'security_mode' => 'selective',
            'block_enumeration' => false,
            'enable_logging' => false,
        ];
        return wp_parse_args(get_option($this->option_name, []), $defaults);
    }

    public function add_admin_menu() {
        add_options_page(
            __('Stop XML-RPC Attacks', 'stop-xml-rpc-attacks'),
            __('XML-RPC Security', 'stop-xml-rpc-attacks'),
            'manage_options',
            'stop-xmlrpc-attacks',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input) {
        $sanitized = [];
        $sanitized['security_mode'] = in_array($input['security_mode'], ['full_disable', 'guest_disable', 'selective']) 
            ? $input['security_mode'] 
            : 'selective';
        $sanitized['block_enumeration'] = isset($input['block_enumeration']);
        $sanitized['enable_logging'] = isset($input['enable_logging']);
        return $sanitized;
    }

    public function enqueue_admin_styles($hook) {
        if ($hook !== 'settings_page_stop-xmlrpc-attacks') {
            return;
        }
        
        wp_add_inline_style('wp-admin', '
            .sxra-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .sxra-card h3 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .sxra-option {
                margin: 15px 0;
                padding: 15px;
                background: #f9f9f9;
                border-left: 4px solid #2271b1;
                border-radius: 3px;
            }
            .sxra-option label {
                font-weight: 600;
                display: block;
                margin-bottom: 5px;
            }
            .sxra-option p {
                margin: 5px 0 0 0;
                color: #646970;
                font-size: 13px;
            }
            .sxra-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            .sxra-stat-box {
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
                padding: 15px;
                border-radius: 3px;
            }
            .sxra-stat-box strong {
                display: block;
                font-size: 24px;
                color: #2271b1;
                margin-bottom: 5px;
            }
            .sxra-warning {
                background: #fcf3cd;
                border-left-color: #dba617;
            }
            .sxra-success {
                background: #d5f4e6;
                border-left-color: #00a32a;
            }
        ');
    }

    private function get_security_mode_label($mode) {
        $labels = [
            'full_disable' => __('Full Disable', 'stop-xml-rpc-attacks'),
            'guest_disable' => __('Guest Disable', 'stop-xml-rpc-attacks'),
            'selective' => __('Selective Blocking', 'stop-xml-rpc-attacks'),
        ];
        return isset($labels[$mode]) ? $labels[$mode] : $labels['selective'];
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        $blocked_count = count($this->default_blocked_methods);
        if ($settings['block_enumeration']) {
            $blocked_count += count($this->enumeration_methods);
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php esc_html_e('Protect your WordPress site from XML-RPC attacks while maintaining compatibility with essential services.', 'stop-xml-rpc-attacks'); ?></p>

            <div class="sxra-card">
                <h3><?php esc_html_e('Current Status', 'stop-xml-rpc-attacks'); ?></h3>
                <div class="sxra-stats">
                    <div class="sxra-stat-box sxra-success">
                        <strong><?php echo esc_html($blocked_count); ?></strong>
                        <span><?php esc_html_e('Methods Blocked', 'stop-xml-rpc-attacks'); ?></span>
                    </div>
                    <div class="sxra-stat-box">
                        <strong><?php echo esc_html($this->get_security_mode_label($settings['security_mode'])); ?></strong>
                        <span><?php esc_html_e('Security Mode', 'stop-xml-rpc-attacks'); ?></span>
                    </div>
                    <div class="sxra-stat-box <?php echo $settings['enable_logging'] ? 'sxra-success' : ''; ?>">
                        <strong><?php echo $settings['enable_logging'] ? esc_html__('ON', 'stop-xml-rpc-attacks') : esc_html__('OFF', 'stop-xml-rpc-attacks'); ?></strong>
                        <span><?php esc_html_e('Attack Logging', 'stop-xml-rpc-attacks'); ?></span>
                    </div>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>

                <div class="sxra-card">
                    <h3><?php esc_html_e('Security Mode', 'stop-xml-rpc-attacks'); ?></h3>
                    
                    <div class="sxra-option <?php echo $settings['security_mode'] === 'full_disable' ? 'sxra-success' : ''; ?>">
                        <label>
                            <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[security_mode]" 
                                   value="full_disable" <?php checked($settings['security_mode'], 'full_disable'); ?>>
                            <strong><?php esc_html_e('Full Disable', 'stop-xml-rpc-attacks'); ?></strong> <?php esc_html_e('(Maximum Security)', 'stop-xml-rpc-attacks'); ?>
                        </label>
                        <p><?php esc_html_e('Completely disables XML-RPC. Use this if you don\'t need Jetpack, WooCommerce mobile apps, or WordPress mobile app.', 'stop-xml-rpc-attacks'); ?></p>
                    </div>

                    <div class="sxra-option <?php echo $settings['security_mode'] === 'guest_disable' ? 'sxra-warning' : ''; ?>">
                        <label>
                            <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[security_mode]" 
                                   value="guest_disable" <?php checked($settings['security_mode'], 'guest_disable'); ?>>
                            <strong><?php esc_html_e('Guest Disable', 'stop-xml-rpc-attacks'); ?></strong> <?php esc_html_e('(Balanced)', 'stop-xml-rpc-attacks'); ?>
                        </label>
                        <p><?php esc_html_e('Disables XML-RPC for non-logged-in users, but allows it for administrators. Blocks dangerous methods for logged-in users.', 'stop-xml-rpc-attacks'); ?></p>
                    </div>

                    <div class="sxra-option <?php echo $settings['security_mode'] === 'selective' ? 'sxra-warning' : ''; ?>">
                        <label>
                            <input type="radio" name="<?php echo esc_attr($this->option_name); ?>[security_mode]" 
                                   value="selective" <?php checked($settings['security_mode'], 'selective'); ?>>
                            <strong><?php esc_html_e('Selective Blocking', 'stop-xml-rpc-attacks'); ?></strong> <?php esc_html_e('(Best Compatibility)', 'stop-xml-rpc-attacks'); ?>
                        </label>
                        <p><?php esc_html_e('Only blocks known dangerous methods. Use this if you need full Jetpack or WooCommerce functionality.', 'stop-xml-rpc-attacks'); ?></p>
                    </div>
                </div>

                <div class="sxra-card">
                    <h3><?php esc_html_e('Advanced Options', 'stop-xml-rpc-attacks'); ?></h3>
                    
                    <div class="sxra-option">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[block_enumeration]" 
                                   value="1" <?php checked($settings['block_enumeration']); ?>>
                            <strong><?php esc_html_e('Block User Enumeration Methods', 'stop-xml-rpc-attacks'); ?></strong>
                        </label>
                        <p><?php esc_html_e('Blocks methods that can reveal usernames. Warning: This will break WordPress mobile apps and some third-party tools.', 'stop-xml-rpc-attacks'); ?></p>
                    </div>

                    <div class="sxra-option">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($this->option_name); ?>[enable_logging]" 
                                   value="1" <?php checked($settings['enable_logging']); ?>>
                            <strong><?php esc_html_e('Enable Attack Logging', 'stop-xml-rpc-attacks'); ?></strong>
                        </label>
                        <p><?php esc_html_e('Logs blocked XML-RPC attempts to your debug.log file. Useful for monitoring attacks.', 'stop-xml-rpc-attacks'); ?></p>
                    </div>
                </div>

                <div class="sxra-card">
                    <h3><?php esc_html_e('Blocked Methods', 'stop-xml-rpc-attacks'); ?></h3>
                    <p><strong>
                        <?php 
                        /* translators: %d: number of blocked methods */
                        echo esc_html(sprintf(__('Always blocked (%d):', 'stop-xml-rpc-attacks'), count($this->default_blocked_methods))); 
                        ?>
                    </strong></p>
                    <ul>
                        <?php foreach ($this->default_blocked_methods as $method): ?>
                            <li><code><?php echo esc_html($method); ?></code></li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($settings['block_enumeration']): ?>
                        <p><strong>
                            <?php 
                            /* translators: %d: number of additionally blocked methods */
                            echo esc_html(sprintf(__('Additionally blocked (%d):', 'stop-xml-rpc-attacks'), count($this->enumeration_methods))); 
                            ?>
                        </strong></p>
                        <ul>
                            <?php foreach ($this->enumeration_methods as $method): ?>
                                <li><code><?php echo esc_html($method); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php submit_button(__('Save Security Settings', 'stop-xml-rpc-attacks'), 'primary large'); ?>
            </form>

            <div class="sxra-card">
                <h3><?php esc_html_e('About This Plugin', 'stop-xml-rpc-attacks'); ?></h3>
                <p><strong><?php esc_html_e('Version:', 'stop-xml-rpc-attacks'); ?></strong> <?php echo esc_html(SXRA_VERSION); ?></p>
                <p><strong><?php esc_html_e('Author:', 'stop-xml-rpc-attacks'); ?></strong> <a href="https://tsw.ovh" target="_blank" rel="noopener noreferrer">Pascal CESCATO</a></p>
                <p><?php esc_html_e('This plugin protects your WordPress site from XML-RPC brute force attacks, DDoS attempts, and reconnaissance probes while maintaining compatibility with essential services like Jetpack and WooCommerce.', 'stop-xml-rpc-attacks'); ?></p>
            </div>
        </div>
        <?php
    }

    public function disable_for_guests($enabled) {
        return is_user_logged_in() ? $enabled : false;
    }

    public function block_dangerous_methods($methods) {
        if (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST) {
            return $methods;
        }

        $settings = $this->get_settings();
        $blocked = $this->default_blocked_methods;

        if ($settings['block_enumeration']) {
            $blocked = array_merge($blocked, $this->enumeration_methods);
        }

        $blocked = apply_filters('sxra_blocked_xmlrpc_methods', $blocked);

        foreach ($blocked as $method) {
            unset($methods[$method]);
        }

        return $methods;
    }

    public function log_blocked_attempts($method) {
        $settings = $this->get_settings();
        $blocked = $this->default_blocked_methods;
        
        if ($settings['block_enumeration']) {
            $blocked = array_merge($blocked, $this->enumeration_methods);
        }
        
        if (in_array($method, $blocked)) {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : ('CLI' === php_sapi_name() ? 'CLI' : 'unknown');
            
            // Only log in development/debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Only runs when WP_DEBUG is enabled
                error_log(sprintf(
                    '[Stop XML-RPC v%s] Blocked dangerous method "%s" from IP: %s',
                    SXRA_VERSION,
                    $method,
                    $ip
                ));
            }
        }
        return $method;
    }

    public function remove_headers_and_links() {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        
        add_filter('wp_headers', function($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });
    }

    public function remove_pingback_header() {
        if (function_exists('header_remove')) {
            header_remove('X-Pingback');
        }
    }
}

// Initialize plugin
Stop_XMLRPC_Attacks::init();