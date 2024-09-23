=== stop XML-RPC Attacks ===
Contributors: pcescato
Tags: xml-rpc, pingback, ddos, multicall
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

Secure your site's XML-RPC by removing some methods, while you can still use XML-RPC.

== Description ==

Secure your site's XML-RPC by removing some methods, instead of disabling totally XML-RPC, which is needed by some plugins (eg. Jetpack) and some mobile apps.

= Features =

Removes the following methods from XML-RPC interface.

* system.multicall
* system.listMethods
* system.getCapabilities
* pingback.extensions.getPingbacks
* pingback.ping
* X-Pingback from HTTP headers

This is not perfect, but it will help prerventing attacks

= Requirements =

* WordPress 5.0 or higher.
    	
== Installation ==

* Extract the zip file and just drop the contents in the <code>wp-content/plugins/</code> directory of your WordPress installation or install it directly from your dashboard and then activate the plugin from Plugins page.
* There's not options page, simply install and activate.

== Frequently Asked Questions ==

= Is there something to do after install? =

Yes, just activate it!

= I already have a security plugin, do I need this plugin too? =

It depends on your security plugin. Some secure XML-RPC, some just allow you to enable or disable it, some can stop attacks as *Stop XML-RPC Attacks* does. So you may have to read your security plugin FAQ / doc.

  
== Changelog ==

= 1.0 =

* Initial release