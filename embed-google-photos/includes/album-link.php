<?php
// ABS PATH
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared album link (free).
 *
 * Adds the photos of a PUBLIC shared Google Photos album to a gallery when the
 * editor clicks "Save & Sync". Needs no Google Client ID/Secret: we read the
 * album's share page, the same way a browser does. Photos either stay on Google
 * (default) or are copied to the Media Library.
 *
 * Automatic background sync, multiple albums and Live Event Mode are Pro. The
 * photo format and the AJAX action match Pro, so galleries keep working after
 * an upgrade.
 */
if (!class_exists('Bpgpb_Album_Link')) {

class Bpgpb_Album_Link
{
    // Ids for Google-hosted photos start here (1e9..2e9, inside 32-bit range).
    const REMOTE_ID_BASE = 1000000000;

    // Browser UA for the share page and its paging call.
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    // 300 photos per page, so this allows albums of up to 15,000 photos.
    const MAX_ALBUM_PAGES = 50;

    // Seconds of downloading per request; the editor calls again to continue.
    const EDITOR_BUDGET = 15;

    public function __construct()
    {
        add_action('wp_ajax_bpgpb_sync_album', [$this, 'ajax_sync_album']);
    }

    /**
     * Editor "Save & Sync": read the shared album and return the photos that
     * aren't in the gallery yet, so the block can add them to selectedPhotos.
     */
    public function ajax_sync_album()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('unauthorized');
        }
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wp_rest')) {
            wp_send_json_error('invalid request');
        }

        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        if (!$this->is_valid_album_url($url)) {
            wp_send_json_error(__('Please enter a valid Google Photos shared album link.', 'embed-google-photos'));
        }

        // syncKeys already in the gallery, so we skip photos already added.
        $existing = [];
        if (isset($_POST['existing'])) {
            $decoded = json_decode(sanitize_text_field(wp_unslash($_POST['existing'])), true);
            if (is_array($decoded)) {
                $existing = array_map('strval', $decoded);
            }
        }

        // Album photos always stay on Google's servers (no Media Library copy).
        $storage = 'google';

        $more   = false;
        $photos = $this->sync_album($url, $existing, $storage, microtime(true) + self::EDITOR_BUDGET, $more);
        if (is_wp_error($photos)) {
            wp_send_json_error($photos->get_error_message());
        }

        // "more": downloads hit the time budget; the editor calls again to continue.
        wp_send_json_success(['photos' => $photos, 'added' => count($photos), 'more' => $more]);
    }

    private function sync_album($url, array $existing_keys, $storage, $deadline, &$more)
    {
        $items = $this->scrape_album($url);
        if (is_wp_error($items)) {
            return $items;
        }
        if (empty($items)) {
            return new WP_Error('empty', __('No photos were found at that link. Make sure the album is shared via link.', 'embed-google-photos'));
        }

        $existing   = array_flip($existing_keys);
        $photos     = [];
        $downloaded = 0;

        foreach ($items as $item) {
            $base_url = $item['url'];
            $sync_key = md5($base_url);
            if (isset($existing[$sync_key])) {
                continue; // already in the gallery
            }

            if ('google' === $storage) {
                $photos[] = $this->remote_photo($item, $sync_key);
                continue;
            }

            // Past the deadline: leave the rest for the next call, but only after
            // at least one download, so every call makes progress.
            if ($downloaded && microtime(true) > $deadline) {
                $more = true;
                break;
            }
            $downloaded++;

            $attachment_id = $this->album_attachment($base_url, $sync_key);
            if (!$attachment_id) {
                continue;
            }
            $photos[] = $this->local_photo($attachment_id, $item, $sync_key);
        }

        return $photos;
    }

    /** Build a photo served from a Media Library attachment. */
    private function local_photo($attachment_id, array $item, $sync_key)
    {
        $full   = wp_get_attachment_image_url($attachment_id, 'full');
        $large  = wp_get_attachment_image_url($attachment_id, 'large');
        $small  = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        $srcset = wp_get_attachment_image_srcset($attachment_id, 'full');
        $meta   = wp_get_attachment_metadata($attachment_id);

        return [
            'id'          => $attachment_id,
            'type'        => 'image',
            'url'         => $full ? $full : '',
            'thumb'       => $large ? $large : ($full ? $full : ''),
            'placeholder' => $small ? $small : '',
            'srcset'      => $srcset ? $srcset : '',
            'alt'         => '',
            'title'       => '',
            'date'        => '',
            'width'       => isset($meta['width']) ? (int) $meta['width'] : (isset($item['width']) ? (int) $item['width'] : 0),
            'height'      => isset($meta['height']) ? (int) $meta['height'] : (isset($item['height']) ? (int) $item['height'] : 0),
            // Marks the photo as coming from the album so the next sync skips it.
            'syncKey'     => $sync_key,
            'album'       => '',
        ];
    }

    /**
     * Build a photo that loads from Google instead of the Media Library.
     *
     * Google resizes on the fly via the "=w<width>" suffix, so the grid, srcset
     * and lightbox all come from the same base URL. The id is a stable number
     * derived from the syncKey, far above real attachment ids; the "remote" flag
     * tells every attachment lookup to skip it.
     */
    private function remote_photo(array $item, $sync_key)
    {
        $base   = $item['url'];
        $srcset = [];
        foreach ([400, 800, 1200, 1600, 2048] as $w) {
            $srcset[] = $base . '=w' . $w . ' ' . $w . 'w';
        }

        return [
            'id'          => self::REMOTE_ID_BASE + (crc32($sync_key) % self::REMOTE_ID_BASE),
            'remote'      => true,
            'type'        => 'image',
            'url'         => $base . '=w2048',
            'thumb'       => $base . '=w1024',
            'placeholder' => $base . '=w64',
            'srcset'      => implode(', ', $srcset),
            'alt'         => '',
            'title'       => '',
            'date'        => '',
            'width'       => isset($item['width']) ? (int) $item['width'] : 0,
            'height'      => isset($item['height']) ? (int) $item['height'] : 0,
            'syncKey'     => $sync_key,
            'album'       => '',
        ];
    }

    /**
     * Fetch a shared album and return every photo's base URL (+ size).
     *
     * The share page embeds the first 300 photos in its "ds:1" data block, along
     * with a page token; the rest come from Google's own "snAcKc" paging call,
     * 300 at a time. If the data block can't be read we fall back to scanning
     * the HTML for "/pw/" URLs (first batch only).
     *
     * @return array<int,array{url:string,width?:int,height?:int}>|WP_Error
     */
    private function scrape_album($url)
    {
        // wp_safe_remote_get validates the URL (and every redirect hop), rejecting
        // private/reserved IPs, so the link can't be used to reach internal hosts.
        $response = wp_safe_remote_get($url, [
            'timeout'     => 30,
            'redirection' => 5,
            'user-agent'  => self::USER_AGENT,
            'headers'     => ['Accept-Language' => 'en-US,en;q=0.9'],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }
        if (200 !== (int) wp_remote_retrieve_response_code($response)) {
            return new WP_Error('http', __('Could not open the album link.', 'embed-google-photos'));
        }

        $body = wp_remote_retrieve_body($response);
        if ('' === $body) {
            return [];
        }

        $data = $this->page_data($body);
        if (null === $data) {
            return $this->scan_album_html($body);
        }

        $items = $this->payload_items(isset($data[1]) ? $data[1] : []);
        $token = (isset($data[2]) && is_string($data[2])) ? $data[2] : '';

        // og:url carries the album id and share key the paging call needs.
        if ($token && preg_match('#property="og:url" content="https://photos\.google\.com/share/([A-Za-z0-9_-]+)\?key=([A-Za-z0-9_-]+)"#', $body, $m)) {
            $pages = 1;
            while ($token && $pages < self::MAX_ALBUM_PAGES) {
                $next = $this->fetch_album_page($m[1], $m[2], $token);
                if (null === $next) {
                    break; // keep what we have rather than failing the sync
                }
                $items = array_merge($items, $next['items']);
                $token = $next['token'];
                $pages++;
            }
        }

        // A photo can repeat across pages; keep the first.
        $seen = [];
        $out  = [];
        foreach ($items as $item) {
            if (!isset($seen[$item['url']])) {
                $seen[$item['url']] = true;
                $out[] = $item;
            }
        }
        return $out;
    }

    /** Decode the share page's "ds:1" data block. Null when it can't be read. */
    private function page_data($body)
    {
        $key = strpos($body, "key: 'ds:1'");
        if (false === $key) {
            return null;
        }
        $start = strpos($body, 'data:', $key);
        $end   = false === $start ? false : strpos($body, ', sideChannel', $start);
        if (false === $start || false === $end) {
            return null;
        }
        $data = json_decode(substr($body, $start + 5, $end - $start - 5), true);
        return (is_array($data) && isset($data[1]) && is_array($data[1])) ? $data : null;
    }

    /** Map payload photo rows ["<id>",["<url>",<w>,<h>,…],…] to items. */
    private function payload_items($rows)
    {
        $items = [];
        foreach ((array) $rows as $row) {
            $media = (is_array($row) && isset($row[1]) && is_array($row[1])) ? $row[1] : [];
            $img   = isset($media[0]) ? (string) $media[0] : '';
            if (!preg_match('#^https://lh3\.googleusercontent\.com/pw/[A-Za-z0-9_-]+$#', $img)) {
                continue;
            }
            $items[] = [
                'url'    => $img,
                'width'  => isset($media[1]) ? (int) $media[1] : 0,
                'height' => isset($media[2]) ? (int) $media[2] : 0,
            ];
        }
        return $items;
    }

    /**
     * Next page of a shared album via Google's "snAcKc" call (the one the share
     * page itself uses to load more). Returns null on any failure.
     *
     * @return array{items:array,token:string}|null
     */
    private function fetch_album_page($album_id, $auth_key, $token)
    {
        $request  = wp_json_encode([[['snAcKc', wp_json_encode([$album_id, $token, null, $auth_key]), null, 'generic']]]);
        $response = wp_safe_remote_post('https://photos.google.com/_/PhotosUi/data/batchexecute?rpcids=snAcKc&rt=c', [
            'timeout'    => 30,
            'user-agent' => self::USER_AGENT,
            'body'       => ['f.req' => $request],
        ]);
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return null;
        }

        // The reply is length-prefixed JSON lines; the page is a JSON string at
        // [0][2] of the ["wrb.fr","snAcKc",…] line. Parsed with json_decode, not a
        // regex: a regex over the ~140 KB line hits PCRE's backtrack limit.
        $data = null;
        foreach (explode("\n", wp_remote_retrieve_body($response)) as $line) {
            if (0 !== strpos($line, '[["wrb.fr","snAcKc"')) {
                continue;
            }
            $envelope = json_decode($line, true);
            if (isset($envelope[0][2]) && is_string($envelope[0][2])) {
                $data = json_decode($envelope[0][2], true);
            }
            break;
        }
        if (!is_array($data)) {
            return null;
        }

        return [
            'items' => $this->payload_items(isset($data[1]) ? $data[1] : []),
            'token' => (isset($data[2]) && is_string($data[2])) ? $data[2] : '',
        ];
    }

    /**
     * Fallback: scan the HTML for "/pw/<id>" photo URLs (first batch only).
     * The og:image cover is skipped since it repeats one of the album photos.
     */
    private function scan_album_html($body)
    {
        if (!preg_match_all('#https://lh3\.googleusercontent\.com/pw/[A-Za-z0-9_-]+#', $body, $matches)) {
            return [];
        }

        $cover = preg_match('#property="og:image" content="(https://lh3\.googleusercontent\.com/pw/[A-Za-z0-9_-]+)#', $body, $cm) ? $cm[1] : '';

        $dims = [];
        if (preg_match_all('#"(https://lh3\.googleusercontent\.com/pw/[A-Za-z0-9_-]+)",(\d+),(\d+)#', $body, $dim_matches, PREG_SET_ORDER)) {
            foreach ($dim_matches as $dm) {
                $dims[$dm[1]] = [(int) $dm[2], (int) $dm[3]];
            }
        }

        $seen  = [];
        $items = [];
        foreach ($matches[0] as $img_url) {
            if (isset($seen[$img_url]) || $img_url === $cover) {
                continue;
            }
            $seen[$img_url] = true;
            $item = ['url' => $img_url];
            if (isset($dims[$img_url])) {
                list($item['width'], $item['height']) = $dims[$img_url];
            }
            $items[] = $item;
        }
        return $items;
    }

    /** Whether the URL looks like a Google Photos shared-album link. */
    private function is_valid_album_url($url)
    {
        if (!$url || !wp_http_validate_url($url)) {
            return false;
        }
        $host = wp_parse_url($url, PHP_URL_HOST);
        // Only Google Photos' own share hosts. The generic goo.gl shortener is
        // deliberately excluded — it can redirect anywhere.
        return in_array($host, ['photos.app.goo.gl', 'photos.google.com'], true);
    }

    /**
     * Media Library copy of an album photo: reuses the one already downloaded
     * for this syncKey, so syncing again never piles up duplicate files.
     * Returns 0 on failure.
     */
    private function album_attachment($base_url, $sync_key)
    {
        $found = get_posts([
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_bpgpb_sync_key', // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value'     => $sync_key,         // phpcs:ignore WordPress.DB.SlowDBQuery
        ]);
        if ($found) {
            $path = get_attached_file((int) $found[0]);
            if ($path && file_exists($path)) {
                return (int) $found[0];
            }
        }

        $attachment_id = $this->sideload($base_url . '=w2048', 'google-album-' . substr($sync_key, 0, 10) . '.jpg');
        if ($attachment_id) {
            update_post_meta($attachment_id, '_bpgpb_sync_key', $sync_key);
        }
        return $attachment_id;
    }

    /** Download a public image into the Media Library. Returns 0 on failure. */
    private function sideload($download_url, $filename)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $response = wp_safe_remote_get($download_url, ['timeout' => 30]);
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return 0;
        }
        $bytes = wp_remote_retrieve_body($response);
        if ('' === $bytes) {
            return 0;
        }

        $tmp = wp_tempnam($filename);
        if (!$tmp) {
            return 0;
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            WP_Filesystem();
        }
        $wp_filesystem->put_contents($tmp, $bytes, FS_CHMOD_FILE);

        $attachment_id = media_handle_sideload(['name' => $filename, 'tmp_name' => $tmp], 0);
        if (is_wp_error($attachment_id)) {
            wp_delete_file($tmp);
            return 0;
        }
        // Tag as plugin-imported, like Pro, so Pro's Media Cleanup finds it later.
        update_post_meta((int) $attachment_id, '_bpgpb_imported', 1);
        return (int) $attachment_id;
    }
}

new Bpgpb_Album_Link();
}
