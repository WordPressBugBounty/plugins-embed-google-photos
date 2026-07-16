<?php
/**
 * Gallery custom post type + [google_photos id=..] shortcode.
 *
 * Each gallery is a locked single-block post (bpgpb/google-photos). The
 * shortcode renders that post's block anywhere — pages, widgets, Elementor,
 * or theme templates via do_shortcode().
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('bpgpb_Custom_Post_Type')) {

    class bpgpb_Custom_Post_Type {

        public $post_type = 'bpgpb_gallery';

        public function __construct() {
            add_action('init', [$this, 'onInit'], 20);
            add_shortcode('google_photos', [$this, 'onAddShortcode']);
            add_filter("manage_{$this->post_type}_posts_columns", [$this, 'manageColumns']);
            add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'manageCustomColumns'], 10, 2);
            add_action('use_block_editor_for_post', [$this, 'useBlockEditorForPost'], 999, 2);
            add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
        }

        public function onInit() {
            $menuIcon = '<svg width="24px" height="24px" viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg" fill="black"><path d="M12.678 16.672c0 2.175.002 4.565-.001 6.494-.001.576-.244.814-.817.833-7.045.078-8.927-7.871-4.468-11.334-1.95.016-4.019.007-5.986.007-1.351 0-1.414-.01-1.405-1.351.258-6.583 7.946-8.275 11.323-3.936L11.308.928c-.001-.695.212-.906.906-.925 6.409-.187 9.16 7.308 4.426 11.326l6.131.002c1.097 0 1.241.105 1.228 1.217-.223 6.723-7.802 8.376-11.321 4.124zm.002-15.284-.003 9.972c6.56-.465 6.598-9.532.003-9.972zm-1.36 21.224-.001-9.97c-6.927.598-6.29 9.726.002 9.97zM1.4 11.315l9.95.008c-.527-6.829-9.762-6.367-9.95-.008zm11.238 1.365c.682 6.875 9.67 6.284 9.977.01z"/></svg>';

            register_post_type($this->post_type, [
                'labels' => [
                    'name'          => __('Galleries', 'embed-google-photos'),
                    'singular_name' => __('Gallery', 'embed-google-photos'),
                    'menu_name'     => __('Google Photos', 'embed-google-photos'),
                    'all_items'     => __('All Galleries', 'embed-google-photos'),
                    'add_new'       => __('Add New', 'embed-google-photos'),
                    'add_new_item'  => __('Add New Gallery', 'embed-google-photos'),
                    'edit_item'     => __('Edit Gallery', 'embed-google-photos'),
                    'new_item'      => __('New Gallery', 'embed-google-photos'),
                    'view_item'     => __('View Gallery', 'embed-google-photos'),
                    'search_items'  => __('Search Galleries', 'embed-google-photos'),
                    'not_found'     => __('No galleries found.', 'embed-google-photos'),
                ],
                'public'              => false,
                'show_ui'             => true,
                'show_in_rest'        => true,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'menu_position'       => 20,
                'menu_icon'           => 'data:image/svg+xml;base64,' . base64_encode($menuIcon),
                'has_archive'         => false,
                'hierarchical'        => false,
                'capability_type'     => 'page',
                'rewrite'             => false,
                'supports'            => ['title', 'editor'],
                'template'            => [['bpgpb/google-photos']],
                'template_lock'       => 'all',
            ]);
        }

        /**
         * [google_photos id=X] — render gallery post X's block.
         */
        public function onAddShortcode($atts) {
            $atts    = shortcode_atts(['id' => 0], $atts, 'google_photos');
            $post_id = absint($atts['id']);
            if (!$post_id) {
                return '';
            }

            $post = get_post($post_id);
            // Only render our own gallery posts, never arbitrary content by ID.
            if (!$post || $this->post_type !== $post->post_type) {
                return '';
            }

            if (post_password_required($post)) {
                return get_the_password_form($post);
            }

            switch ($post->post_status) {
                case 'publish':
                    return $this->displayContent($post);
                case 'private':
                    return current_user_can('read_private_posts') ? $this->displayContent($post) : '';
                case 'draft':
                case 'pending':
                case 'future':
                    return current_user_can('edit_post', $post_id) ? $this->displayContent($post) : '';
                default:
                    return '';
            }
        }

        private function displayContent($post) {
            $blocks = parse_blocks($post->post_content);
            if (empty($blocks)) {
                return '';
            }

            $output = '';
            foreach ($blocks as $block) {
                $output .= render_block($block);
            }
            return $output;
        }

        public function manageColumns($defaults) {
            unset($defaults['date']);
            $defaults['shortcode'] = __('ShortCode', 'embed-google-photos');
            $defaults['date']      = __('Date', 'embed-google-photos');
            return $defaults;
        }

        public function manageCustomColumns($column_name, $post_id) {
            if ('shortcode' === $column_name) {
                $post_id = absint($post_id);
                printf(
                    '<div class="bpgpbFrontShortcode" id="bpgpbFrontShortcode-%1$s">
                        <input value="[google_photos id=%1$s]" onclick="bpgpbHandleShortcode( %2$s )" readonly>
                        <span class="tooltip">%3$s</span>
                    </div>',
                    esc_attr($post_id),
                    esc_js($post_id),
                    esc_html__('Copy To Clipboard', 'embed-google-photos')
                );
            }
        }

        public function adminEnqueueScripts($hook) {
            // Only on the gallery list / edit screens.
            $screen = get_current_screen();
            if (!$screen || $this->post_type !== $screen->post_type) {
                return;
            }
            wp_enqueue_style('bpgpb-admin', BPGPB_ASSETS_DIR . 'css/admin.css', [], BPGPB_PLUGIN_VERSION);
            wp_enqueue_script('bpgpb-admin', BPGPB_ASSETS_DIR . 'js/admin.js', ['wp-i18n'], BPGPB_PLUGIN_VERSION, true);
        }

        public function useBlockEditorForPost($use, $post) {
            if ($this->post_type === $post->post_type) {
                return true;
            }
            return $use;
        }
    }

    new bpgpb_Custom_Post_Type();
}
