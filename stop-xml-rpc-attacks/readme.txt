=== Stop XML-RPC Attacks ===
Contributors: pcescato
Tags: security, xmlrpc, brute force, ddos, jetpack
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Blocks dangerous XML-RPC methods while preserving Jetpack, WooCommerce, and mobile apps compatibility.

== Description ==

Stop XML-RPC Attacks protects your WordPress site from XML-RPC brute force attacks, DDoS attempts, and reconnaissance probes while maintaining compatibility with essential services like Jetpack and WooCommerce.

**Features:**

* Three security modes: Full Disable, Guest Disable, or Selective Blocking
* Blocks dangerous methods: system.multicall, pingback.ping, and more
* Compatible with Jetpack and WooCommerce
* Optional user enumeration blocking
* Attack logging for monitoring
* Zero configuration required - works out of the box
* Clean, intuitive admin interface

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/stop-xmlrpc-attacks/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > XML-RPC Security to configure (optional)

== Frequently Asked Questions ==

= Will this break Jetpack? =

No! The default "Selective Blocking" mode is fully compatible with Jetpack and WooCommerce.

= What's the difference between the security modes? =

* **Full Disable**: Maximum security, disables XML-RPC completely
* **Guest Disable**: Balanced approach, only allows XML-RPC for logged-in users
* **Selective Blocking**: Best compatibility, only blocks dangerous methods

= How do I enable logging? =

Go to Settings > XML-RPC Security and check "Enable Attack Logging". Logs will be written to your debug.log file when WP_DEBUG is enabled.

== Changelog ==

= 2.0.0 =
* Added admin interface with visual settings
* Three security modes to choose from
* Optional attack logging
* Improved code quality and security
* Full internationalization support

= 1.0.1 =
* Initial release
* Basic blocking of dangerous methods

== Upgrade Notice ==

= 2.0.0 =
Major update with admin interface.