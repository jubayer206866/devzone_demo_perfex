<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check if IP address matches whitelist (including CIDR)
 */
function securelogin_guard_ip_matches($ip, $whitelist_ip)
{
    // Exact match
    if ($ip === $whitelist_ip) {
        return true;
    }
    
    // CIDR notation check
    if (strpos($whitelist_ip, '/') !== false) {
        list($subnet, $mask) = explode('/', $whitelist_ip);
        
        if (strpos($ip, ':') !== false) {
            // IPv6
            return securelogin_guard_ipv6_in_range($ip, $subnet, $mask);
        } else {
            // IPv4
            return securelogin_guard_ipv4_in_range($ip, $subnet, $mask);
        }
    }
    
    return false;
}

/**
 * Check if IPv4 is in CIDR range
 */
function securelogin_guard_ipv4_in_range($ip, $subnet, $mask)
{
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    $mask_long = -1 << (32 - $mask);
    
    return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
}

/**
 * Check if IPv6 is in CIDR range
 */
function securelogin_guard_ipv6_in_range($ip, $subnet, $mask)
{
    $ip_bin = securelogin_guard_ipv6_to_bin($ip);
    $subnet_bin = securelogin_guard_ipv6_to_bin($subnet);
    
    $prefix = substr($ip_bin, 0, $mask);
    $subnet_prefix = substr($subnet_bin, 0, $mask);
    
    return $prefix === $subnet_prefix;
}

/**
 * Convert IPv6 to binary
 */
function securelogin_guard_ipv6_to_bin($ip)
{
    $bin = '';
    if (strpos($ip, '::') !== false) {
        $ip = securelogin_guard_expand_ipv6($ip);
    }
    $parts = explode(':', $ip);
    foreach ($parts as $part) {
        $bin .= str_pad(decbin(hexdec($part)), 16, '0', STR_PAD_LEFT);
    }
    return $bin;
}

/**
 * Expand IPv6 address
 */
function securelogin_guard_expand_ipv6($ip)
{
    $parts = explode('::', $ip);
    $left = explode(':', $parts[0]);
    $right = isset($parts[1]) ? explode(':', $parts[1]) : [];
    
    $missing = 8 - count($left) - count($right);
    $expanded = array_merge($left, array_fill(0, $missing, '0'), $right);
    
    return implode(':', array_map(function($part) {
        return str_pad($part, 4, '0', STR_PAD_LEFT);
    }, $expanded));
}

