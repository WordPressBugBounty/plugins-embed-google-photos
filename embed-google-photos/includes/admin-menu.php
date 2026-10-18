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
            add_action('admin_init', [$this, 'redirectOldMenuSlugs']);
        }

        public function redirectOldMenuSlugs() {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only redirect of an old menu slug; nothing is saved.
            $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
            if (is_admin() && $page) {
                global $pagenow;
                if ($pagenow === 'admin.php' && in_array($page, ['wp-google-photos', 'wp-google-photos-pro', 'wp-google-photos-account'], true)) {
                    wp_safe_redirect(admin_url('edit.php?post_type=bpgpb_gallery&page=wp-google-photos'));
                    exit;
                }
                if ($pagenow === 'edit.php' && $page === 'bpgpb-dashboard') {
                    wp_safe_redirect(admin_url('edit.php?post_type=bpgpb_gallery&page=wp-google-photos'));
                    exit;
                }
            }
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
                __('Help & Demos', 'embed-google-photos'),
                // Highlighted in the submenu so it stands out from the CPT items.
                '<span style="color: #f18500; font-weight: 600;">' . __('Help & Demos', 'embed-google-photos') . '</span>',
                'manage_options',
                'wp-google-photos',
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
