<?php
/**
 * Plugin Name:         Add-On for Discord and Gravity Forms
 * Plugin URI:          https://github.com/apos37/gf-discord
 * Description:         Send Gravity Form entries to a Discord channel
 * Version:             1.2.1
 * Requires at least:   5.9
 * Tested up to:        6.8
 * Requires PHP:        7.4
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Support URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         gf-discord
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          March 16, 2023
 */

 
/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Defines
 */
$plugin_data = get_file_data( __FILE__, [
    'name'         => 'Plugin Name',
    'version'      => 'Version',
    'textdomain'   => 'Text Domain',
    'support_uri'  => 'Support URI',
] );


/**
 * Defines
 */
define( 'GFDISC_NAME', $plugin_data[ 'name' ] );
define( 'GFDISC_TEXTDOMAIN', $plugin_data[ 'textdomain' ] );
define( 'GFDISC_VERSION', $plugin_data[ 'version' ] );
define( 'GFDISC_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );                                                  // /home/.../public_html/wp-content/plugins/gf-discord/
define( 'GFDISC_PLUGIN_DIR', plugins_url( '/'.GFDISC_TEXTDOMAIN.'/' ) );                                      // https://domain.com/wp-content/plugins/gf-discord/
define( 'GFDISC_SETTINGS_URL', admin_url( 'admin.php?page=gf_settings&subview='.GFDISC_TEXTDOMAIN ) );        // https://domain.com/wp-admin/admin.php?page=gf_settings&subview=gf-discord/
define( 'GFDISC_DISCORD_SUPPORT_URL', $plugin_data[ 'support_uri' ] );


/**
 * Load the Bootstrap
 */
add_action( 'gform_loaded', [ 'GF_Discord_Bootstrap', 'load' ], 5 );


/**
 * GF_Discord_Bootstrap Class
 */
class GF_Discord_Bootstrap {

    // Load
    public static function load() {
        // print_r( 'load bootstrap bak' );

        // Make sure the framework exists
        if ( !method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
            return;
        }

        // Load main plugin class.
        require_once 'class-gf-discord.php';

        // Register the addon
        GFAddOn::register( 'GF_Discord' );
    }
}


/**
 * Filter plugin action links
 */
add_filter( 'plugin_row_meta', 'gfdisc_plugin_row_meta' , 10, 2 );


/**
 * Add links to our website and Discord support
 *
 * @param array $links
 * @return array
 */
function gfdisc_plugin_row_meta( $links, $file ) {
    // Only apply to this plugin
    if ( GFDISC_TEXTDOMAIN.'/'.GFDISC_TEXTDOMAIN.'.php' == $file ) {

        // Add the link
        $row_meta = [
            // 'docs'    => '<a href="'.esc_url( 'https://apos37.com/wordpress-addon-for-discord-gravity-forms/' ).'" target="_blank" aria-label="'.esc_attr__( 'Plugin Website Link', 'gf-discord' ).'">'.esc_html__( 'Website', 'gf-discord' ).'</a>',
            'discord' => '<a href="'.esc_url( GFDISC_DISCORD_SUPPORT_URL ).'" target="_blank" aria-label="'.esc_attr__( 'Plugin Support on Discord', 'gf-discord' ).'">'.esc_html__( 'Discord Support', 'gf-discord' ).'</a>'
        ];

        // Require Gravity Forms Notice
        if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            echo '<div class="gravity-forms-required-notice" style="margin: 5px 0 15px; border-left-color: #d63638 !important; background: #FCF9E8; border: 1px solid #c3c4c7; border-left-width: 4px; box-shadow: 0 1px 1px rgba(0, 0, 0, .04); padding: 10px 12px;">';
            /* translators: 1: Plugin name, 2: Gravity Forms link */
            printf( __( 'This plugin requires the %s plugin to be activated!', 'gf-discord' ),
                '<a href="https://www.gravityforms.com/" target="_blank">Gravity Forms</a>'
            );
            echo '</div>';
        }
        
        // Merge the links
        return array_merge( $links, $row_meta );
    }

    // Return the links
    return (array) $links;
} // End gfdisc_plugin_row_meta()


/**
 * Add string comparison function to earlier versions of PHP
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_starts_with' ) ) {
    function str_starts_with ( $haystack, $needle ) {
        return strpos( $haystack , $needle ) === 0;
    }
}
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_ends_with' ) ) {
    function str_ends_with( $haystack, $needle ) {
        return $needle !== '' && substr( $haystack, -strlen( $needle ) ) === (string)$needle;
    }
}