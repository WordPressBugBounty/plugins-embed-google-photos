<?php
// ABS PATH
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template Library: the editor modal's AJAX endpoints, proxied to
 * templates.bplugins.com (same API as bSlider). Templates are listed under
 * plugin = PLUGIN_SLUG; a "pro" category locks a card for free users. Demo
 * photos stay on Google, so import returns the markup unchanged.
 */
if (!class_exists('Bpgpb_Templates')) {

class Bpgpb_Templates
{
    const PLUGIN_SLUG = 'embed-google-photos';

    const API = 'https://templates.bplugins.com/wp-json/gutenberg-templates/v1';

    const TIMEOUT = 8;

    const NONCE = 'bpgpb_template';

    const FAVORITES_OPTION = 'bpgpbFavoritesTemplates';

    public function __construct()
    {
        add_action('wp_ajax_bpgpb_templates_main', [$this, 'templates_main']);
        add_action('wp_ajax_bpgpb_templates', [$this, 'templates']);
        add_action('wp_ajax_bpgpb_template_import', [$this, 'template_import']);
        add_action('wp_ajax_bpgpb_template_counts', [$this, 'template_counts']);
        add_action('wp_ajax_bpgpb_template_favorites', [$this, 'template_favorites']);
    }

    // Capability only; each handler checks the nonce itself so PHPCS can see it.
    private static function guard()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient Permissions');
        }
    }

    private static function type($type)
    {
        return 'pages' === $type ? 'pages' : 'patterns';
    }

    private static function get_json($url)
    {
        $response = wp_safe_remote_get($url, ['timeout' => self::TIMEOUT]);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response));
    }

    // Every template of this plugin, with only the requested fields.
    private static function all_templates($type, $fields)
    {
        return self::get_json(add_query_arg([
            'type'   => $type,
            'plugin' => self::PLUGIN_SLUG,
            'fields' => $fields,
            'limit'  => 10000,
            'start'  => 0,
            'end'    => 10000,
        ], self::API . '/blocks'));
    }

    // Category list, limited to the categories this plugin's templates use.
    public function templates_main()
    {
        if (!check_ajax_referer(self::NONCE, '_wpnonce', false)) {
            wp_send_json_error('Invalid Request');
        }

        self::guard();

        $type = self::type(sanitize_text_field(wp_unslash($_POST['type'] ?? 'patterns')));
        $typeFilter = 'patterns' === $type ? 'patterns-category' : 'pages-category';

        $body = self::get_json(self::API . "/taxonomy/taxonomies/plugin,type,{$typeFilter}");

        if (is_wp_error($body)) {
            wp_send_json_error($body->get_error_message());
        }

        if ($body && isset($body->{$typeFilter})) {
            $mine = $this->plugin_categories($type);

            $body->{$typeFilter} = array_values(array_filter($body->{$typeFilter}, function ($cat) use ($mine) {
                return isset($cat->name) && in_array($cat->name, $mine, true);
            }));
        }

        wp_send_json_success($body);
    }

    private function plugin_categories($type)
    {
        $body = self::all_templates($type, 'category');

        if (is_wp_error($body) || !$body || !isset($body->patterns)) {
            return [];
        }

        $categories = [];

        foreach ($body->patterns as $pattern) {
            if (isset($pattern->category) && is_array($pattern->category)) {
                $categories = array_merge($categories, $pattern->category);
            }
        }

        return array_unique($categories);
    }

    public function templates()
    {
        if (!check_ajax_referer(self::NONCE, '_wpnonce', false)) {
            wp_send_json_error('Invalid Request');
        }

        self::guard();

        $type = self::type(sanitize_text_field(wp_unslash($_POST['type'] ?? 'patterns')));
        $category = sanitize_text_field(wp_unslash($_POST['category'] ?? 'all'));
        $pageNumber = max(1, absint(wp_unslash($_POST['pageNumber'] ?? 1)));
        $perPage = min(100, max(1, absint(wp_unslash($_POST['perPage'] ?? 9))));
        $search = sanitize_text_field(wp_unslash($_POST['search'] ?? ''));
        // Fetch every match and page it here, free templates first: the modal's Free filter works on each page, so mixed pages came out half empty.
        $body = self::get_json(add_query_arg([
            'type'     => $type,
            'start'    => 0,
            'end'      => 10000,
            'limit'    => 10000,
            'plugin'   => self::PLUGIN_SLUG,
            'category' => $category,
            'keywords' => $search,
            'fields'   => 'ID,category,keywords,original_content,thumbnail,title,type,url,preview_url',
        ], self::API . '/blocks'));

        if (is_wp_error($body)) {
            wp_send_json_error($body->get_error_message());
        }

        if ($body && isset($body->patterns) && is_array($body->patterns)) {
            $free = [];
            $pro = [];
            foreach ($body->patterns as $item) {
                // Titles arrive HTML-encoded ("&amp;"); the modal prints them as text.
                if (isset($item->title)) {
                    $item->title = html_entity_decode($item->title, ENT_QUOTES, 'UTF-8');
                }

                if (isset($item->category) && is_array($item->category) && in_array('pro', $item->category, true)) {
                    $pro[] = $item;
                } else {
                    $free[] = $item;
                }
            }

            $body->count = count($body->patterns);
            $body->patterns = array_slice(array_merge($free, $pro), ($pageNumber - 1) * $perPage, $perPage);
        }

        wp_send_json_success($body);
    }

    // Photos in the templates load from Google albums, so there is nothing to download.
    public function template_import()
    {
        if (!check_ajax_referer(self::NONCE, '_wpnonce', false)) {
            wp_send_json_error('Invalid Request');
        }

        self::guard();

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- serialized block markup; kses would strip the block-delimiter comments. Nonce + capability checked above.
        $content = wp_unslash($_POST['original_content'] ?? '');

        wp_send_json_success($content);
    }

    public function template_counts()
    {
        if (!check_ajax_referer(self::NONCE, '_wpnonce', false)) {
            wp_send_json_error('Invalid Request');
        }

        self::guard();

        $type = self::type(sanitize_text_field(wp_unslash($_POST['type'] ?? 'patterns')));
        $counts = ['all' => 0, 'free' => 0, 'pro' => 0, 'categories' => []];

        $body = self::all_templates($type, 'ID,category');

        if (is_wp_error($body)) {
            wp_send_json_error('Failed to fetch templates');
        }

        if (!$body || !isset($body->patterns)) {
            wp_send_json_success($counts);
        }

        foreach ($body->patterns as $template) {
            $cats = isset($template->category) && is_array($template->category) ? $template->category : [];
            $tier = in_array('pro', $cats, true) ? 'pro' : 'free';

            $counts['all']++;
            $counts[$tier]++;

            foreach ($cats as $cat) {
                if ('free' === $cat || 'pro' === $cat) {
                    continue;
                }

                if (!isset($counts['categories'][$cat])) {
                    $counts['categories'][$cat] = ['total' => 0, 'free' => 0, 'pro' => 0];
                }

                $counts['categories'][$cat]['total']++;
                $counts['categories'][$cat][$tier]++;
            }
        }

        wp_send_json_success($counts);
    }

    // The option name is fixed here, never built from the request's prefix.
    public function template_favorites()
    {
        if (!check_ajax_referer(self::NONCE, '_wpnonce', false)) {
            wp_send_json_error('Invalid Request');
        }

        self::guard();

        if (isset($_POST['favorites'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string; every decoded value is cast through absint() below.
            $raw = json_decode(wp_unslash($_POST['favorites']), true);

            $favorites = [
                'patterns' => array_values(array_filter(array_unique(array_map('absint', (array) ($raw['patterns'] ?? []))))),
                'pages'    => array_values(array_filter(array_unique(array_map('absint', (array) ($raw['pages'] ?? []))))),
            ];

            update_option(self::FAVORITES_OPTION, $favorites, false);
        }

        wp_send_json_success(get_option(self::FAVORITES_OPTION, ['patterns' => [], 'pages' => []]));
    }
}

new Bpgpb_Templates();

}
