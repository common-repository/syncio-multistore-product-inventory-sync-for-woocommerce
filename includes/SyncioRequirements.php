<?php


/**
 * Handles requirements checks
 */
class SyncioRequirements
{

    public static $x = 10;

    /**
     * Checks if cURL is activated on php installation.
     *
     * @return array cURL requirements check results
     */
    public static function checkCurlForSyncio()
    {
        if (!in_array('curl', get_loaded_extensions())) {
            return array(
                'title' => __('PHP cURL', 'syncio'),
                'pass' => false,
                'reason' => __('PHP cURL seems to be disabled or not installed on your server.', 'syncio'),
                'solution' => __('Please activate cURL to use the syncio plugin. <br>', 'syncio'),
            );
        }

        return array(
            'title' => __('PHP cURL', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Checks if the connection is SSL.
     *
     * @return array SSL requirements check results
     */
    public static function checkSSLConnectionForSyncio()
    {

        if (!getenv('IS_DEV') && (!isset($_SERVER['HTTPS']) || empty(sanitize_key($_SERVER['HTTPS'])) || 'on' !== strtolower(sanitize_key($_SERVER['HTTPS'])))) {
            return array(
                'title' => __('SSL Connection', 'syncio'),
                'pass' => false,
                'reason' => __('You are not using a SSL connection', 'syncio'),
                'solution' => __(
                    'Please set up a HTTPS certificate to use syncio.<br>'
                    , 'syncio'),
            );
        }

        return array(
            'title' => __('SSL Connection', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Checks Store name.
     * checks if the store has a name to use on syncio.
     *
     * @return array Store name requirements check results
     */
    public static function getStoreNameRequirementsStatusForSyncio()
    {

        if (get_option('blogname') === '') {
            return array(
                'title' => __('Store name', 'syncio'),
                'pass' => false,
                'reason' => __('Seems like your store does not have a name.', 'syncio'),
                'solution' => __(
                    'Please set a name for your store. <br> You can set one in <b>Settings > General > Site Title<b>',
                    'syncio'
                ),
            );
        }

        return array(
            'title' => __('Store name', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Checks Current User requirements.
     *
     * @return array Current User requirements check results
     */
    public static function getUserRequirementsStatusForSyncio()
    {
        $currentUser = wp_get_current_user();
        if ($currentUser->get('user_email') === '') {
            return array(
                'title' => __('Current User', 'syncio'),
                'pass' => false,
                'reason' => __('Your user does not have an e-mail account.', 'syncio'),
                'solution' => __(
                    'Please you need to assign an e-mail to your user.',
                    'syncio'
                ),
            );
        }

        return array(
            'title' => __('Current User', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Checks WooCommerce requirements.
     *
     * @return array WooCommerce requirements check results
     */
    public static function getWooCommerceRequirementsStatusForSyncio()
    {
        if (!function_exists('wc')) {
            return array(
                'title' => __('WooCommerce', 'syncio'),
                'pass' => false,
                'reason' => __('WooCommerce plugin is not activated', 'syncio'),
                'solution' => __(
                    'Please install and activate the WooCommerce plugin',
                    'syncio'
                ),
            );
        }

        if (!version_compare(wc()->version, '2.6', '>=')) {
            return array(
                'title' => __('WooCommerce', 'syncio'),
                'pass' => false,
                'reason' => __(
                    'WooCommerce plugin is out of date, version >= 2.6 is required',
                    'syncio'
                ),
                'solution' => __(
                    'Please update your WooCommerce plugin',
                    'syncio'
                ),
            );
        }

        return array(
            'title' => __('WooCommerce', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Checks WordPress requirements.
     *
     * @return array WordPress requirements check results
     */
    public static function getWordPressRequirementsStatusForSyncio()
    {
        if (!version_compare(get_bloginfo('version'), '4.4', '>=')) {
            return array(
                'title' => __('WordPress', 'syncio'),
                'pass' => false,
                'reason' => __(
                    'WordPress is out of date, version >= 4.4 is required',
                    'syncio'
                ),
                'solution' => __(
                    'Please update your WordPress installation',
                    'syncio'
                ),
            );
        }

        return array(
            'title' => __('WordPress', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Checks permalinks requirements.
     *
     * @return array Permalinks requirements check results
     */
    public static function getPermalinksRequirementsStatusForSyncio()
    {
        if (get_option('permalink_structure', '') === '') {
            return array(
                'title' => __('Permalinks', 'syncio'),
                'pass' => false,
                'reason' => __(
                    'Permalinks set to "Plain"',
                    'syncio'
                ),
                'solution' => __(
                    'Set permalinks to anything other than "Plain" in <b>Settings > Permalinks</b>',
                    'syncio'
                ),
            );
        }

        return array(
            'title' => __('Permalinks', 'syncio'),
            'pass' => true,
        );
    }

    /**
     * Retrieves collective requirements status.
     *
     * @return array Requirements check results
     */
    public static function getRequirementsStatusForSyncio()
    {
        $requirementsStatus = array(
            'woocommerce' => self::getWooCommerceRequirementsStatusForSyncio(),
            'wordpress' => self::getWordPressRequirementsStatusForSyncio(),
            'permalinks' => self::getPermalinksRequirementsStatusForSyncio(),
            'user' => self::getUserRequirementsStatusForSyncio(),
            'store_name' => self::getStoreNameRequirementsStatusForSyncio(),
            'check_ssl' => self::checkSSLConnectionForSyncio(),
            'check_curl' => self::checkCurlForSyncio(),
        );
        return $requirementsStatus;
    }


}
