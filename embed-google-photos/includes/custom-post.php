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
            $menuIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm0 2v7.59l3.3-3.3a1 1 0 0 1 1.4 0L12 13.6l2.3-2.3a1 1 0 0 1 1.4 0L20 15.6V7H4zm5 1.5A1.5 1.5 0 1 1 7.5 10 1.5 1.5 0 0 1 9 8.5z"/></svg>';

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
                'menu_icon'           => 'data:image/svg+xml;utf8,' . rawurlencode($menuIcon),
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
