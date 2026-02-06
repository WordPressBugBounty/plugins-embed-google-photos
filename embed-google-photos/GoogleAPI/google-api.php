<?php
// ABS PATH
if (!defined('ABSPATH')) {exit;}
class GooglePhotosAPI
{

    /**
     * Initialize the class
     */

    public function __construct()
    {
        add_action('wp_ajax_bpgpb_retrieve_access_token', [$this, 'bpgpb_retrieve_access_token']);
        add_action('wp_ajax_nopriv_bpgpb_retrieve_access_token', [$this, 'bpgpb_retrieve_access_token']);
        add_action('wp_ajax_retrieve_refresh_token', [$this, 'retrieve_refresh_token']);
    }

    function retrieve_refresh_token(){

        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wp_rest')) {
            wp_send_json_error('invalid request');
        }

        $should_i_save = sanitize_text_field($_POST['save']);

        $data = get_option('bpgpb_auth_info');
        if(!$should_i_save && $data){
            wp_send_json_success($data);
        }

        $client_id = sanitize_text_field($_POST['client_id']);
        $client_secret = sanitize_text_field($_POST['client_secret']);
        $refresh_token = sanitize_text_field($_POST['refresh_token']);

        if(!$client_id || !$client_secret || !$refresh_token){
            wp_send_json_error('data missing');
        }

        try {
            $data = compact('client_id', 'client_secret', 'refresh_token');

            $response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'method' => 'POST',
                'body' => array(
                    "client_id" => $client_id,
                    "client_secret" => $client_secret,
                    "refresh_token" =>  $refresh_token,
                    "grant_type" => "refresh_token"
                ),
            ]);
        
            $response = json_decode(wp_remote_retrieve_body( $response ), true);

            $response['refresh_token'] = $refresh_token;

            update_option('bpgpb_auth_info', $data);
            update_option('bpgpb-google-photos', wp_json_encode($response));
            set_transient('bpgpb_expireTime', 3500, 3500);
            wp_send_json_success( $data );
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function bpgpb_retrieve_access_token(){

        if (!wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'wp_rest')) {
            wp_send_json_error('invalid request');
        }

        $google_photos = new GooglePhotos();

        $token = $google_photos->get_client_token();
        wp_send_json_success($token);
    }

}
new GooglePhotosAPI();