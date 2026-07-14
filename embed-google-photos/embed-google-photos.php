<?php
/**
 * Plugin Name: Gallery for Google Photos – Import and Display Photo Albums
 * Plugin URI: https://bplugins.com/plugins/gallery-for-google-photos/
 * Description: Embed stunning Google Photos galleries directly into your WordPress site with the Google Photos Block plugin.
 * Version: 1.2.1
 * Author: bPlugins
 * Author URI: http://bplugins.com
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: embed-google-photos
 * Requires at least: 6.5
 * Requires PHP: 7.1
 */

// ABS PATH
if (!defined('ABSPATH')) {exit;}

if (function_exists('bpgpb_fs')) {
    bpgpb_fs()->set_basename( true, __FILE__ );
} else {

    if ( ! function_exists( 'bpgpb_fs' ) ) {
        // Create a helper function for easy SDK access.
        function bpgpb_fs() {
            global $bpgpb_fs;

            if ( ! isset( $bpgpb_fs ) ) {
                // Include Freemius Lite SDK.
                require_once dirname(__FILE__) . '/vendor/freemius-lite/start.php';

                $bpgpbConfig = array(
                    'id'         => '34444',
                    'slug'       => 'embed-google-photos',
                    'type'       => 'plugin',
                    'public_key' => 'pk_cdef836737c97094c8ea2d152212b',
                    'is_premium' => false,
                    'menu'       => array(
                        'slug'       => 'edit.php?post_type=bpgpb_gallery',
                        'first-path' => 'edit.php?post_type=bpgpb_gallery&page=bpgpb-dashboard',
                        'support'    => false,
                    ),
                );
                $bpgpb_fs = fs_lite_dynamic_init( $bpgpbConfig );
            }
            return $bpgpb_fs;
        }
        // Init Freemius.
        bpgpb_fs();
        // Signal that SDK was initiated.
        do_action( 'bpgpb_fs_loaded' );
    }

class bpgpb_Embed_Google_Photos {
    public static $instance;

    private function __construct()
    {
        $this->load_classes();
        $this->constants_defined();

        add_action('init', [$this, 'onInit']);

        // On activation, flag a one-time redirect to the Galleries (shortcode) screen.
        register_activation_hook(__FILE__, [$this, 'onActivate']);
        add_action('admin_init', [$this, 'activationRedirect']);
    }

    public static function get_instance() {
        if(self::$instance) {
            return self::$instance;
        }

        self::$instance = new self();
        return self::$instance;
    }

    public function constants_defined() {
        // Constant
        define( 'BPGPB_PLUGIN_VERSION', '1.2.1' );
        define('BPGPB_ASSETS_DIR', plugin_dir_url(__FILE__) . 'assets/');
        define('BPGPB_DIR_URL', plugin_dir_url(__FILE__));
        define('BPGPB_DIR_PATH', plugin_dir_path(__FILE__));
    }

    public function load_classes () {
        require_once plugin_dir_path(__FILE__) . 'includes/google-api.php';
        require_once plugin_dir_path(__FILE__) . 'includes/GooglePhotos.php';
        require_once plugin_dir_path(__FILE__) . 'includes/custom-post.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
    }

    /**
     * Runs once when the plugin is activated. Set a flag so the next admin page
     * load redirects the user to the Galleries screen (where the shortcodes live).
     */
    public function onActivate() {
        add_option('bpgpb_activation_redirect', true);
    }

    /**
     * Consume the activation flag and redirect to the Galleries (shortcode) list.
     * Skipped during bulk plugin activation so we don't hijack that flow.
     */
    public function activationRedirect() {
        if (!get_option('bpgpb_activation_redirect', false)) {
            return;
        }

        delete_option('bpgpb_activation_redirect');

        // Presence-only check on a WordPress-core-set flag; no request data is
        // used, and the redirect is gated by the one-time option above.
        if (isset($_GET['activate-multi']) || wp_doing_ajax()) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        wp_safe_redirect(admin_url('edit.php?post_type=bpgpb_gallery'));
        exit;
    }

    public function onInit()
    {
        // All scripts, styles and the render.php are declared in build/block.json.
        register_block_type(__DIR__ . '/build'); // Register Block

        wp_set_script_translations('bpgpb-google-photos-editor-script', 'embed-google-photos', plugin_dir_path(__FILE__) . 'languages'); // Translate
    }
}
bpgpb_Embed_Google_Photos::get_instance();

}