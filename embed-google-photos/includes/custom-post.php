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
            add_action('current_screen', [$this, 'addHelpTab']);
            add_action('admin_notices', [$this, 'whatsNew']);
            add_action('admin_post_bpgpb_whats_new_seen', [$this, 'whatsNewSeen']);
        }

        /**
         * "What's new" notice after an update, shown once per version to admins
         * on the plugin's own "Google Photos" menu screens (not the editor),
         * until dismissed.
         */
        const WHATS_NEW = '1.3.1';

        public function whatsNew() {
            $screen = get_current_screen();
            if (!$screen || !current_user_can('manage_options') || $this->post_type !== $screen->post_type || 'post' === $screen->base) {
                return;
            }
            if (self::WHATS_NEW === get_user_meta(get_current_user_id(), 'bpgpb_whats_new_seen', true)) {
                return;
            }
            printf(
                '<div class="notice notice-info"><p><strong>%1$s</strong> %2$s</p><p><a href="%3$s">%4$s</a> &nbsp;|&nbsp; <a href="%5$s" target="_blank" rel="noopener noreferrer">%6$s</a> &nbsp;|&nbsp; <a href="%7$s">%8$s</a></p></div>',
                /* translators: %s: plugin version */
                esc_html(sprintf(__('New in Gallery for Google Photos %s:', 'embed-google-photos'), self::WHATS_NEW)),
                esc_html__('add a shared Google Photos album, no Client ID or Client Secret needed. Paste the album link in the gallery and click Save & Sync. Photos can stay on Google Photos, so they use no space on your server.', 'embed-google-photos'),
                esc_url(admin_url('post-new.php?post_type=' . $this->post_type)),
                esc_html__('Add an album', 'embed-google-photos'),
                esc_url('https://www.youtube.com/watch?v=bCVATx0aCN4'),
                esc_html__('▶ Watch (35 sec)', 'embed-google-photos'),
                esc_url(wp_nonce_url(admin_url('admin-post.php?action=bpgpb_whats_new_seen'), 'bpgpb_whats_new_seen')),
                esc_html__('Dismiss', 'embed-google-photos')
            );
        }

        public function whatsNewSeen() {
            check_admin_referer('bpgpb_whats_new_seen');
            update_user_meta(get_current_user_id(), 'bpgpb_whats_new_seen', self::WHATS_NEW);
            wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url());
            exit;
        }

        /**
         * WordPress' own "Help" tab on the gallery screens — the place users
         * already know to look when they get stuck.
         */
        public function addHelpTab($screen) {
            if (!$screen || $this->post_type !== $screen->post_type) {
                return;
            }

            $screen->add_help_tab([
                'id'      => 'bpgpb_help',
                'title'   => __('Gallery for Google Photos', 'embed-google-photos'),
                'content' =>
                    '<p>' . esc_html__('Create a gallery, pick your photos from Google Photos, then copy the shortcode from the ShortCode column and paste it anywhere.', 'embed-google-photos') . '</p>' .
                    '<p><strong>' . esc_html__('Connecting your Google account', 'embed-google-photos') . '</strong><br>' .
                    esc_html__('The plugin uses your own Google API credentials. The guide below walks through creating a Google Cloud project, enabling the Photos Picker API and generating a Client ID, Client Secret and Refresh Token.', 'embed-google-photos') . '</p>' .
                    '<p><a href="' . esc_url(BPGPB_AUTH_DOCS_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Google Authorization Guide', 'embed-google-photos') . '</a></p>',
            ]);

            // YouTube ids, kept in sync with videos in src/admin/utils/data.js.
            $videos = [
                'ufKMktJm1ho' => __('Connect Google Photos (quick start)', 'embed-google-photos'),
                'bCVATx0aCN4' => __('Show a shared album, kept on Google Photos', 'embed-google-photos'),
                '7HSCQyEcj4Q' => __('Gallery layouts', 'embed-google-photos'),
                'iqEEWkPLGhg' => __('Lightbox', 'embed-google-photos'),
                'Xhtj8BHlCis' => __('Live search & filters', 'embed-google-photos'),
                '66xcoyUd0-4' => __('Photos & videos', 'embed-google-photos'),
                'PdxgwOOt8jM' => __('Hover effects, filters & motion', 'embed-google-photos'),
                'j3qaTZW8BRo' => __('Pagination & sorting', 'embed-google-photos'),
                '7g4miYg2Ah8' => __('Carousels & sliders', 'embed-google-photos'),
                'hiF4acT4JnQ' => __('Responsive & SEO', 'embed-google-photos'),
            ];
            $links = '';
            foreach ($videos as $id => $label) {
                $links .= '<li><a href="' . esc_url('https://www.youtube.com/watch?v=' . $id) . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a></li>';
            }
            $screen->add_help_tab([
                'id'      => 'bpgpb_help_videos',
                'title'   => __('Video Tutorials', 'embed-google-photos'),
                'content' =>
                    '<ul>' . $links . '</ul>' .
                    '<p>' . esc_html__('Tip: click the ▶ icon next to a settings panel to watch its video.', 'embed-google-photos') . ' <a href="https://www.youtube.com/playlist?list=PLTY5b5svgjQs" target="_blank" rel="noopener noreferrer">' . esc_html__('All videos on YouTube', 'embed-google-photos') . '</a></p>',
            ]);

            $screen->set_help_sidebar(
                '<p><strong>' . esc_html__('For more information:', 'embed-google-photos') . '</strong></p>' .
                '<p><a href="' . esc_url(BPGPB_DOCS_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Documentation', 'embed-google-photos') . '</a></p>' .
                '<p><a href="https://wordpress.org/support/plugin/embed-google-photos/" target="_blank" rel="noopener noreferrer">' . esc_html__('Support Forum', 'embed-google-photos') . '</a></p>'
            );
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
                $post_id   = absint($post_id);
                $shortcode = '[google_photos id=' . $post_id . ']';
                // size= fits the box to the shortcode so long IDs aren't cut off.
                printf(
                    '<div class="bpgpbFrontShortcode" id="bpgpbFrontShortcode-%1$s">
                        <input value="%2$s" size="%3$d" onclick="bpgpbHandleShortcode( %4$s )" readonly>
                        <span class="tooltip">%5$s</span>
                    </div>',
                    esc_attr($post_id),
                    esc_attr($shortcode),
                    strlen($shortcode),
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
