<?php
/**
 * Plugin Name: Gallery For Google Photos
 * Description: Embed stunning Google Photos galleries directly into your WordPress site with the Google Photos Block plugin.
 * Version: 1.0.9
 * Author: bPlugins
 * Author URI: http://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: embed-google-photos
 */

// ABS PATH
if (!defined('ABSPATH')) {exit;}

class bpgpb_Embed_Google_Photos {
    public static $instance;

    private function __construct()
    {
        $this->load_classes();
        $this->constants_defined();

        add_action('enqueue_block_assets', [$this, 'enqueueBlockAssets']);
        add_action('init', [$this, 'onInit']);
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
        define( 'BPGPB_PLUGIN_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.9' );
        define('BPGPB_ASSETS_DIR', plugin_dir_url(__FILE__) . 'assets/');
    }

    public function load_classes () {
        require_once plugin_dir_path(__FILE__) . '/GoogleAPI/google-api.php';
        require_once plugin_dir_path(__FILE__) . '/GoogleAPI/GooglePhotos.php';
    }

    public function enqueueBlockAssets()
    {
        wp_register_style('fancyapps', BPGPB_ASSETS_DIR . 'css/fancyapps.min.css', [], '5.0');
        wp_register_style('bpgpb-google-photos-style', plugins_url('dist/style.css', __FILE__), ['fancyapps'], BPGPB_PLUGIN_VERSION);

        wp_register_script('fancyapps', BPGPB_ASSETS_DIR . 'js/fancyapps.min.js', [], '5.0', true);
        wp_register_script('bpgpb-google-photos-script', plugins_url('dist/script.js', __FILE__), ['react', 'react-dom', 'wp-util', 'fancyapps'], BPGPB_PLUGIN_VERSION, true);
    }

    public function onInit()
    {
        wp_register_style('bpgpb-block-directory-editor-style', plugins_url('dist/editor.css', __FILE__), ['wp-edit-blocks', 'bpgpb-google-photos-style'], BPGPB_PLUGIN_VERSION); // Backend Style

        register_block_type(__DIR__, [
            'editor_style' => 'bpgpb-block-directory-editor-style',
            'render_callback' => [$this, 'render'],
        ]); // Register Block

        wp_set_script_translations('BPGPB-block-directory-editor-script', 'embed-google-photos', plugin_dir_path(__FILE__) . 'languages'); // Translate
    }

    public function render($attributes)
    {
        extract($attributes);

        $className = $className ?? '';
        $BPGPBBlockClassName = 'wp-block-bpgpb-google-photos ' . $className . ' align' . $align;

        wp_enqueue_style('bpgpb-google-photos-style');
        wp_enqueue_script('bpgpb-google-photos-script');

        $google_photos = new GooglePhotos();

        $token = $google_photos->get_client_token();

        ob_start();?>
		<div class='<?php echo esc_attr($BPGPBBlockClassName); ?>' id='BPGPBBlockDirectory-<?php echo esc_attr($cId) ?>' data-attributes='<?php echo esc_attr(wp_json_encode($attributes)); ?>' data-info='<?php echo esc_attr(wp_json_encode(['nonce' => wp_create_nonce('wp_rest'), 'token' => $token])); ?>'></div>

		<?php return ob_get_clean();
    } // Render
}
bpgpb_Embed_Google_Photos::get_instance();