<?php
/**
 * Modern React dashboard, mounted on a submenu of the gallery post type.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('bpgpb_Admin_Menu')) {

    class bpgpb_Admin_Menu {

        private $page_hook = '';

        public function __construct() {
            add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
            add_action('admin_menu', [$this, 'adminMenu']);
        }

        public function adminEnqueueScripts($hook) {
            // Only load the dashboard bundle on this plugin's own admin page.
            if ($hook !== $this->page_hook) {
                return;
            }

            $asset_path = BPGPB_DIR_PATH . 'build/admin-dashboard.asset.php';
            $asset = file_exists($asset_path)
                ? require $asset_path
                : ['dependencies' => [], 'version' => BPGPB_PLUGIN_VERSION];

            wp_enqueue_style(
                'bpgpb-admin-dashboard',
                BPGPB_DIR_URL . 'build/admin-dashboard.css',
                [],
                $asset['version']
            );
            wp_enqueue_script(
                'bpgpb-admin-dashboard',
                BPGPB_DIR_URL . 'build/admin-dashboard.js',
                $asset['dependencies'],
                $asset['version'],
                true
            );
            wp_set_script_translations('bpgpb-admin-dashboard', 'embed-google-photos', BPGPB_DIR_PATH . 'languages');
        }

        public function adminMenu() {
            $this->page_hook = add_submenu_page(
                'edit.php?post_type=bpgpb_gallery',
                __('Demo & Help', 'embed-google-photos'),
                __('Demo & Help', 'embed-google-photos'),
                'manage_options',
                'bpgpb-dashboard',
                [$this, 'dashboardPage']
            );
        }

        public function dashboardPage() {
            printf(
                '<div id="bpgpbDashboard" data-info="%s"></div>',
                esc_attr(wp_json_encode([
                    'version'  => BPGPB_PLUGIN_VERSION,
                    'adminUrl' => admin_url(),
                ]))
            );
        }
    }

    new bpgpb_Admin_Menu();
}
