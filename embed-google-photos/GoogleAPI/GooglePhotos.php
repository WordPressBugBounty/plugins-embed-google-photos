<?php
// ABS PATH
if (!defined('ABSPATH')) {exit;}
class GooglePhotos {

    public function get_client_token()
    {
        $token = json_decode(get_option('bpgpb-google-photos'), true);
        $is_not_expired = get_transient('bpgpb_expireTime');
        if (!$is_not_expired && isset($token['refresh_token']) && $token['refresh_token'] != '') {
            $info = get_option('bpgpb_auth_info');

             $response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'method' => 'POST',
                'body' => array(
                    "client_id" => $info['client_id'],
                    "client_secret" => $info['client_secret'],
                    "refresh_token" =>  $info['refresh_token'],
                    "grant_type" => "refresh_token"
                ),
            ]);

            $new_token = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($new_token['access_token'])) {
                $token['access_token'] = $new_token['access_token'];
                // update_option('bpgpb_accessToken', $token);
                update_option('bpgpb-google-photos', wp_json_encode($token));
                set_transient('bpgpb_expireTime', 3500, 3500);
            }
        }
        return $token;
    }
}