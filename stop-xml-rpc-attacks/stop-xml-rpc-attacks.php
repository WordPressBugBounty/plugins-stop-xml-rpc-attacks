<?php

/*
  Plugin Name: Stop XML-RPC Attacks
  Description: Secure your site's XML-RPC by removing some methods, while you can still use XML-RPC.
  Author: Pascal CESCATO
  Version: 1.0.1
  Author URI: https://tsw.ovh
  License: GPLv3
 */

/*
  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as published by
  the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <https://www.gnu.org/licenses/gpl.html>.
 */

add_filter('xmlrpc_methods', function ($methods) {
    unset($methods['system.multicall']);
    unset($methods['system.listMethods']);
    unset($methods['system.getCapabilities']);
    unset($methods['pingback.extensions.getPingbacks']);
    unset($methods['pingback.ping']);
    return $methods;
});

add_action('wp', function () {
    header_remove('X-Pingback');
}, 9999);