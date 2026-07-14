<?php
// ABS PATH
if (!defined('ABSPATH')) {exit;}

/**
 * Google Photos Picker API integration.
 *
 * The legacy Library API (photoslibrary.googleapis.com) was retired by Google on
 * 2025-03-31, so this plugin uses the Picker API: the admin picks photos in
 * Google's own UI, we import the picked images into the WordPress Media Library,
 * and the block renders those local images. All handlers are admin-only — the
 * OAuth token never reaches the browser.
 */
if (!class_exists('Bpgpb_Google_Photos_API')) {

class Bpgpb_Google_Photos_API
{
    const PICKER_BASE = 'https://photospicker.googleapis.com/v1';

    public function __construct()
    {
        // OAuth connect (admin enters their own Google credentials).
        add_action('wp_ajax_bpgpb_retrieve_refresh_token', [$this, 'retrieve_refresh_token']);
        // Editor connection check.
        add_action('wp_ajax_bpgpb_retrieve_access_token', [$this, 'bpgpb_retrieve_access_token']);
        // Picker flow: create session -> poll -> import.
        add_action('wp_ajax_bpgpb_create_picker_session', [$this, 'create_picker_session']);
        add_action('wp_ajax_bpgpb_poll_picker_session', [$this, 'poll_picker_session']);
        add_action('wp_ajax_bpgpb_import_picked_photos', [$this, 'import_picked_photos']);
    }

    /**
     * Guard: every handler requires an authenticated admin + a valid nonce.
     * Sends a JSON error and stops when the request is not allowed.
     */
    private function verify_admin_request()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('unauthorized');
        }
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wp_rest')) {
            wp_send_json_error('invalid request');
        }
    }

    /** Get a fresh, valid access token from the server-side stored credentials. */
    private function get_access_token()
    {
        $token = (new Bpgpb_Google_Photos())->get_client_token();
        return isset($token['access_token']) ? $token['access_token'] : '';
    }

    /**
     * Exchange the admin-supplied refresh token for an access token and store it.
     */
    public function retrieve_refresh_token()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('unauthorized');
        }
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wp_rest')) {
            wp_send_json_error('invalid request');
        }

        $existing     = get_option('bpgpb_auth_info');
        $is_connected = is_array($existing) && !empty($existing['client_secret']) && !empty($existing['refresh_token']);

        $should_i_save = isset($_POST['save']) ? sanitize_text_field(wp_unslash($_POST['save'])) : '';

        // On load (no save) return ONLY the connection status. The Client ID,
        // Client Secret and Refresh Token are secrets — they are stored and used
        // strictly server-side and are never sent back to the browser.
        if (!$should_i_save) {
            wp_send_json_success(['connected' => $is_connected]);
        }

        // Explicit disconnect: wipe every stored credential + token.
        $disconnect = isset($_POST['disconnect']) && sanitize_text_field(wp_unslash($_POST['disconnect']));
        if ($disconnect) {
            delete_option('bpgpb_auth_info');
            delete_option('bpgpb-google-photos');
            delete_transient('bpgpb_expireTime');
            wp_send_json_success(['connected' => false]);
        }

        $client_id     = isset($_POST['client_id']) ? sanitize_text_field(wp_unslash($_POST['client_id'])) : '';
        $client_secret = isset($_POST['client_secret']) ? sanitize_text_field(wp_unslash($_POST['client_secret'])) : '';
        $refresh_token = isset($_POST['refresh_token']) ? sanitize_text_field(wp_unslash($_POST['refresh_token'])) : '';

        // Write-only fields: because the browser never receives the saved secrets,
        // a blank field on save means "keep the value already stored".
        if ($is_connected) {
            if ('' === $client_id) {
                $client_id = isset($existing['client_id']) ? $existing['client_id'] : '';
            }
            if ('' === $client_secret) {
                $client_secret = $existing['client_secret'];
            }
            if ('' === $refresh_token) {
                $refresh_token = $existing['refresh_token'];
            }
        }

        if (!$client_id || !$client_secret || !$refresh_token) {
            wp_send_json_error(__('Please provide the Client ID, Client Secret and Refresh Token.', 'embed-google-photos'));
        }

        $data = compact('client_id', 'client_secret', 'refresh_token');

        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token',
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $token = json_decode(wp_remote_retrieve_body($response), true);

        // Verify the credentials actually work before saving them, so the editor
        // can show a clear message instead of silently storing bad values.
        if (empty($token['access_token'])) {
            $reason = '';
            if (isset($token['error_description'])) {
                $reason = $token['error_description'];
            } elseif (isset($token['error'])) {
                $reason = $token['error'];
            }
            $message = __('Invalid credentials. Please re-check your Client ID, Client Secret and Refresh Token.', 'embed-google-photos');
            if ($reason) {
                $message .= ' (' . $reason . ')';
            }
            wp_send_json_error($message);
        }

        $token['refresh_token'] = $refresh_token;

        // autoload => false: secrets are only needed during admin actions, so keep
        // them out of the options loaded on every front-end request.
        update_option('bpgpb_auth_info', $data, false);
        update_option('bpgpb-google-photos', wp_json_encode($token), false);
        set_transient('bpgpb_expireTime', 3500, 3500);

        // Return ONLY the connection status — never echo the credentials back.
        wp_send_json_success(['connected' => true]);
    }

    /**
     * Report whether the site is connected to Google Photos (editor uses this).
     * Returns only a boolean — no access token or credential ever leaves the server.
     */
    public function bpgpb_retrieve_access_token()
    {
        $this->verify_admin_request();

        $access_token = $this->get_access_token();
        wp_send_json_success(['connected' => (bool) $access_token]);
    }

    /**
     * Create a Picker session. Returns the pickerUri the admin opens to select
     * photos, plus polling hints.
     */
    public function create_picker_session()
    {
        $this->verify_admin_request();

        $access_token = $this->get_access_token();
        if (!$access_token) {
            wp_send_json_error('not_connected');
        }

        $response = wp_remote_post(self::PICKER_BASE . '/sessions', [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => '{}',
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (200 !== (int) $code || empty($body['pickerUri'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('bpgpb create_picker_session failed: ' . wp_json_encode(['status' => $code, 'body' => $body])); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            wp_send_json_error(__('Could not start a Google Photos picker session. Please reconnect your account and try again.', 'embed-google-photos'));
        }

        // pollingConfig.pollInterval looks like "5s"; (int) cast yields 5.
        $poll_seconds = isset($body['pollingConfig']['pollInterval']) ? (int) $body['pollingConfig']['pollInterval'] : 3;
        if ($poll_seconds < 2) {
            $poll_seconds = 2;
        }

        wp_send_json_success([
            'sessionId'      => isset($body['id']) ? $body['id'] : '',
            'pickerUri'      => $body['pickerUri'],
            'pollIntervalMs' => $poll_seconds * 1000,
            'timeoutMs'      => 5 * 60 * 1000,
        ]);
    }

    /** Poll a session; true once the user has finished picking. */
    public function poll_picker_session()
    {
        $this->verify_admin_request();

        // Nonce + capability already verified in verify_admin_request() above.
        $session_id = isset($_POST['sessionId']) ? sanitize_text_field(wp_unslash($_POST['sessionId'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!$session_id) {
            wp_send_json_error('missing_session');
        }

        $access_token = $this->get_access_token();
        $response = wp_remote_get(self::PICKER_BASE . '/sessions/' . rawurlencode($session_id), [
            'timeout' => 30,
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        wp_send_json_success(['mediaItemsSet' => !empty($body['mediaItemsSet'])]);
    }

    /**
     * Import the picked photos: list the session's media items, download each
     * (authenticated) into the Media Library, and return local attachment data.
     */
    public function import_picked_photos()
    {
        $this->verify_admin_request();

        // Nonce + capability already verified in verify_admin_request() above.
        $session_id = isset($_POST['sessionId']) ? sanitize_text_field(wp_unslash($_POST['sessionId'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (!$session_id) {
            wp_send_json_error('missing_session');
        }

        $access_token = $this->get_access_token();
        if (!$access_token) {
            wp_send_json_error('not_connected');
        }

        // List every picked media item (paginated).
        $items      = [];
        $page_token = '';
        do {
            $url = add_query_arg(
                array_filter([
                    'sessionId' => $session_id,
                    'pageSize'  => 100,
                    'pageToken' => $page_token,
                ]),
                self::PICKER_BASE . '/mediaItems'
            );

            $response = wp_remote_get($url, [
                'timeout' => 30,
                'headers' => ['Authorization' => 'Bearer ' . $access_token],
            ]);
            if (is_wp_error($response)) {
                break;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['mediaItems'])) {
                $items = array_merge($items, $body['mediaItems']);
            }
            $page_token = isset($body['nextPageToken']) ? $body['nextPageToken'] : '';
            // Safety ceiling so a runaway session can't loop forever; well above
            // any realistic single-gallery pick.
        } while ($page_token && count($items) < 2000);

        // Download + sideload each picked item into the Media Library.
        $photos = [];
        foreach ($items as $item) {
            $type       = isset($item['type']) ? $item['type'] : '';
            $media_file = isset($item['mediaFile']) ? $item['mediaFile'] : [];
            $base_url   = isset($media_file['baseUrl']) ? $media_file['baseUrl'] : '';
            if (!$base_url) {
                continue;
            }
            $filename  = isset($media_file['filename']) ? $media_file['filename'] : '';
            $mime_type = isset($media_file['mimeType']) ? $media_file['mimeType'] : '';

            // Caption data: title (from filename) + original creation date.
            // The Picker API exposes the creation time on the media item itself
            // (createTime), not inside mediaFileMetadata.
            $created = isset($item['createTime']) ? $item['createTime'] : '';
            $title   = pathinfo($filename, PATHINFO_FILENAME);

            if ('VIDEO' === $type) {
                // Picker API: append "=dv" to download the actual video bytes,
                // and "=w2048" to grab a still frame we can use as a poster.
                $video_ext = $this->ext_from_mime($mime_type, 'mp4');
                $video_id  = $this->sideload_media($base_url . '=dv', $this->safe_name($filename, $base_url, $video_ext), $access_token, 120);
                if (!$video_id) {
                    continue;
                }

                $poster_id  = $this->sideload_media($base_url . '=w2048', $this->safe_name($filename, $base_url, 'jpg'), $access_token);
                $video_url  = wp_get_attachment_url($video_id);
                $poster_url = $poster_id ? wp_get_attachment_image_url($poster_id, 'large') : '';
                $poster_sm  = $poster_id ? wp_get_attachment_image_url($poster_id, 'thumbnail') : '';

                $photos[] = [
                    'id'          => $video_id,
                    'type'        => 'video',
                    'url'         => $video_url ? $video_url : '',
                    'thumb'       => $poster_url ? $poster_url : '',
                    'placeholder' => $poster_sm ? $poster_sm : ($poster_url ? $poster_url : ''),
                    'title'       => $title,
                    'date'        => $created,
                    'width'       => 0,
                    'height'      => 0,
                ];
                continue;
            }

            if ('PHOTO' !== $type) {
                continue;
            }

            $attachment_id = $this->sideload_media($base_url . '=w2048', $this->safe_name($filename, $base_url, 'jpg'), $access_token);
            if (!$attachment_id) {
                continue;
            }

            $full   = wp_get_attachment_image_url($attachment_id, 'full');
            $large  = wp_get_attachment_image_url($attachment_id, 'large');
            $small  = wp_get_attachment_image_url($attachment_id, 'thumbnail');
            $srcset = wp_get_attachment_image_srcset($attachment_id, 'full');
            $meta   = wp_get_attachment_metadata($attachment_id);
            // Seed the alt text from any alt already set on the Media Library
            // attachment, so images that were described once carry it over.
            $alt    = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

            $photos[] = [
                'id'          => $attachment_id,
                'type'        => 'image',
                'url'         => $full ? $full : '',
                'thumb'       => $large ? $large : ($full ? $full : ''),
                // Tiny image used as the blurred "blur-up" placeholder.
                'placeholder' => $small ? $small : '',
                // Responsive candidates so the browser picks the right size.
                'srcset'      => $srcset ? $srcset : '',
                // Alt text (editable per image in the block; SEO + accessibility).
                'alt'         => $alt ? $alt : '',
                'title'       => $title,
                'date'        => $created,
                'width'       => isset($meta['width']) ? (int) $meta['width'] : 0,
                'height'      => isset($meta['height']) ? (int) $meta['height'] : 0,
            ];
        }

        // Best-effort session cleanup.
        wp_remote_request(self::PICKER_BASE . '/sessions/' . rawurlencode($session_id), [
            'method'  => 'DELETE',
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);

        wp_send_json_success(['photos' => $photos]);
    }

    /**
     * Download an authenticated media file (photo or video) from the Picker API
     * and add it to the Media Library. Returns an attachment ID, or 0 on failure.
     *
     * @param string $download_url Full baseUrl including the download param (=w2048 / =dv).
     * @param string $safe_name    Target filename with the correct extension.
     * @param string $access_token OAuth bearer token.
     * @param int    $timeout      Request timeout in seconds (videos need more).
     */
    private function sideload_media($download_url, $safe_name, $access_token, $timeout = 30)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $response = wp_remote_get($download_url, [
            'timeout' => $timeout,
            'headers' => ['Authorization' => 'Bearer ' . $access_token],
        ]);
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return 0;
        }
        $bytes = wp_remote_retrieve_body($response);
        if ('' === $bytes) {
            return 0;
        }

        $tmp = wp_tempnam($safe_name);
        if (!$tmp) {
            return 0;
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            WP_Filesystem();
        }
        $wp_filesystem->put_contents($tmp, $bytes, FS_CHMOD_FILE);

        $file_array    = ['name' => $safe_name, 'tmp_name' => $tmp];
        $attachment_id = media_handle_sideload($file_array, 0);

        if (is_wp_error($attachment_id)) {
            wp_delete_file($tmp);
            return 0;
        }
        return (int) $attachment_id;
    }

    /**
     * Build a safe filename with the given extension, falling back to a unique
     * name derived from the download URL when Google gives us no filename.
     */
    private function safe_name($filename, $base_url, $ext)
    {
        $base = pathinfo(sanitize_file_name($filename), PATHINFO_FILENAME);
        if ('' === $base) {
            $base = 'google-media-' . substr(md5($base_url), 0, 8);
        }
        return $base . '.' . $ext;
    }

    /** Map a Google video MIME type to a file extension. */
    private function ext_from_mime($mime, $default = 'mp4')
    {
        $map = [
            'video/mp4'       => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm'      => 'webm',
            'video/x-m4v'     => 'm4v',
            'video/3gpp'      => '3gp',
        ];
        return isset($map[$mime]) ? $map[$mime] : $default;
    }
}

new Bpgpb_Google_Photos_API();

}
