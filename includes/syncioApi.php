<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once(ABSPATH . 'wp-load.php'); // Ensure WordPress functions are available

class syncioApi
{
    private $namespace = 'syncio/retailer/v1';
    private $endpoints =
        [
            'POST' => [
                'callbackFromWoocommerce' => false,
                'saveAccessTokenFromSyncio' => false,
                //'uninstallEcom' => false,
                'resetData' => false,
            ],
            'GET' => [
                'getCallbackData' => false,
                'getDataForFrontend' => true,
                'getRequirements' => false,
                'wooValidation' => false,
                'getShopData' => false,
            ],
        ];

    function __construct()
    {
        add_action('rest_api_init', array($this, 'registerEndpointsForSyncio'));
    }


    function registerEndpointsForSyncio()
    {
        foreach ($this->endpoints as $requestType => $endpoints) {
            foreach ($endpoints as $rest => $protected)
                register_rest_route(
                    $this->namespace,
                    $rest,
                    [
                        'methods' => $requestType,
                        'callback' => array($this, $rest),
                        'permission_callback' => $protected ? function () {
                            return current_user_can('edit_others_posts');
                        } : '__return_true',
                    ]
                );
        }
    }

    function callbackFromWoocommerce($request)
    {
        $postData = $request->get_body();
        if (!is_array($request->get_body()))
            $postData = json_decode($postData);

        $consumer_key = sanitize_text_field($postData->consumer_key);
        $consumer_secret = sanitize_text_field($postData->consumer_secret);
        $key_permissions = sanitize_text_field($postData->key_permissions);
        $user_id = sanitize_text_field($postData->user_id);

        if (is_string($consumer_key) && is_string($consumer_secret) && is_string($key_permissions)) {
            $registerData = [
                'domain' => esc_url_raw(get_option('siteurl')),
                'currency' => sanitize_text_field(get_option('woocommerce_currency')),
                'weightUnit' => sanitize_text_field(get_option('woocommerce_weight_unit')),
                'consumerKey' => sanitize_text_field($consumer_key),
                'consumerSecret' => sanitize_text_field($consumer_secret),
                'keyPermissions' => sanitize_text_field($key_permissions),
                'userId' => intval($user_id)
            ];

            update_option('syncio_installer_data', $registerData);
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }

    }


    function getCallbackData()
    {
        $dataToInstaller = esc_html(get_option('syncio_installer_data'));
        wp_send_json_success($dataToInstaller);
    }

    function getShopData()
    {
        $shopData = esc_sql([
            'domain' => get_option('siteurl'),
            'currency' => get_option('woocommerce_currency'),
            'weightUnit' => get_option('woocommerce_weight_unit')
        ]);
        wp_send_json_success($shopData);
    }

    function getRequirements()
    {
        include 'SyncioRequirements.php';
        $requirementsStatus = SyncioRequirements::getRequirementsStatusForSyncio();
        $rStatus = esc_sql($requirementsStatus);
        wp_send_json_success($rStatus);
    }

    function wooValidation()
    {
        $data = get_option('syncio_installer_data');
        $consumer_key = $data['consumerKey'];
        $consumer_secret = $data['consumerSecret'];
        // Get WooCommerce REST API URL
        $url = rest_url('wc/v3/system_status');

        // Prepare the authentication headers
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
            ),
        );

        // Make the request
        $response = wp_remote_get($url, $args);

        $status_code = wp_remote_retrieve_response_code($response);

        if($status_code === 200) {
            return wp_remote_retrieve_response_message($response);
        }else{
           return  wp_remote_retrieve_response_message($response);
        }
    }

    function resetData()
    {
        $data = get_option('syncio_installer_data');
        $consumer_key = $data['consumerKey'];
        $consumer_secret = $data['consumerSecret'];
        // Get WooCommerce REST API URL
        $url = rest_url('wc/v3/system_status');

        // Prepare the authentication headers
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
            ),
        );

        // Make the request
        $response = wp_remote_get($url, $args);

        $status_code = wp_remote_retrieve_response_code($response);

        if($status_code === 401) {
            delete_option('syncio_installer_data');
            delete_option('syncio_access_token');
            //wp_send_json_success();
            return $response;
            return wp_remote_retrieve_response_message($response);
        }else{
            return wp_remote_retrieve_response_message($response);
        }


    }




    //function uninstallEcom()
    //{
    //    $uninstallUrl = SYNCIO_INSTALLER_URL . '/api/woocommerce_auth/uninstall';
    //    $uninstallData = [
    //        'domain' => get_option('siteurl'),
    //        'access_token' => get_option('syncio_access_token')
    //    ];
    //    $response = wp_safe_remote_post(
    //        $uninstallUrl,
    //        array(
    //            'headers' => array(),
    //            'body' => $uninstallData
    //        )
    //    );
    //
    //    if ($response['response']['code'] === 200) {
    //        delete_option('syncio_access_token');
    //        wp_send_json_success('Successfully uninstalled store in Syncio!', 200);
    //    } else {
    //        wp_send_json_error(esc_html($response['response']['message']), esc_html($response['response']['code']));
    //    }
    //}

    function saveAccessTokenFromSyncio()
    {
        //if ( isset($_POST['accessToken'])  && isset($_POST['saveAccessTokenNonce']) && wp_verify_nonce(sanitize_key($_POST['saveAccessTokenNonce'])) ) {
        if ( isset($_POST['accessToken'])  ) {  
            $accessToken = sanitize_text_field(sanitize_key($_POST['accessToken']));
           update_option('syncio_access_token', $accessToken);
           wp_send_json_success(esc_html(get_option('syncio_access_token')));
        }else{
           return wp_send_json_success('invalid nonce');
        }
        
        // this was original code
        // if (isset($_POST['accessToken'])) {
        //     $accessToken = sanitize_text_field(sanitize_key($_POST['accessToken']));
        //     update_option('syncio_access_token', $accessToken);
        //     wp_send_json_success(esc_html(get_option('syncio_access_token')));
        // } else {
        //     wp_send_json_error('No access token provided');
        // }


        //$accessToken = sanitize_text_field(wp_unslash($_POST['accessToken']));
        //update_option('syncio_access_token', $accessToken);
        //wp_send_json_success(esc_html(get_option('syncio_access_token')));
    }


    function getDataForFrontend()
    {
        $response = [
            'rest_url' => get_option('siteurl') . SYNCIO_RETAILER_REST_PATH,
            'site_url' => get_option('siteurl'),
            'syncio_access_token' => get_option('syncio_access_token', false),
            'syncio_installer_data' => get_option('syncio_installer_data', false),
        ];

        wp_send_json_success(esc_sql($response));

    }
}

new syncioApi();




