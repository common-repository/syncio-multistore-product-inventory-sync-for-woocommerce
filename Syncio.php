<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Syncio
{
    private static $instance = null;
    private $plugin_path;
    private $plugin_url;
    private $text_domain = 'syncio-multistore-product-inventory-sync-for-woocommerce';

    /**
     * Creates or returns an instance of this class.
     */
    public static function get_instance()
    {
        // If an instance hasn't been created and set to $instance create an instance and set it to $instance.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Initializes the plugin by setting localization, hooks, filters, and administrative functions.
     */
    private function __construct()
    {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        load_plugin_textdomain($this->text_domain, false, $this->plugin_path . '\lang');

        $this->init_rests();

        add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));

        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'register_styles'));

        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));

        add_action('admin_menu', array($this, 'registerMenu'));
        add_action('wp_footer', array($this,'add_intercom_chatbot'));
    }

    public function get_plugin_url()
    {
        return $this->plugin_url;
    }

    public function get_plugin_path()
    {
        return $this->plugin_path;
    }

    /**
     * Place code that runs at plugin activation here.
     */
    public function activation() {}

    /**
     * Place code that runs at plugin deactivation here.
     */
    public function deactivation() {}

    /**
     * Enqueue and register JavaScript files here.
     */
    public function register_scripts()
    {
        if (! isset($_GET['page']) && strpos(sanitize_key($_GET['page']), 'syncio') !== true) {
            return;
        }

        wp_enqueue_script(
            'syncio-frontend-js',
            plugins_url('/JS/index.js', __FILE__),
            ['jquery'],
            time(),
            true
        );

        wp_enqueue_script(
            'syncio-frontend-js-sweetalert',
            plugins_url('/JS/sweetalert.js', __FILE__),
            [],
            time(),
            true
        );

        //Data for frontend
        wp_localize_script(
            'syncio-frontend-js',
            'syncio_globals',
            [
                'rest_url' => get_option('siteurl') . SYNCIO_RETAILER_REST_PATH,
                'site_url' => get_option('siteurl'),
                'syncio_access_token' => get_option('syncio_access_token', false),
                'syncio_installer_data' => get_option('syncio_installer_data', false),
                'syncio_url' => SYNCIO_URL,
                'syncio_installer_url' => SYNCIO_INSTALLER_URL,
                'img_dir_url' => plugins_url('/img/', __FILE__),
                'syncio_retailer_nonce' => wp_create_nonce('wp_rest'),
                'save_access_token_nonce' => wp_create_nonce('save_access_token_nonce'),
            ]
        );
    }

    /**
     * Enqueue and register CSS files here.
     */
    public function register_styles()
    {
        if (isset($_GET['page']) && strpos(sanitize_key($_GET['page']), 'syncio') !== false) {
            wp_enqueue_style(
                'bootstrap',
                plugins_url('/View/bootstrap.css', __FILE__),
                [],
                time(),
                'all'
            );
            wp_enqueue_style(
                'index',
                plugins_url('/View/index.css', __FILE__),
                [],
                time(),
                'all'
            );
        }
    }

    public function displayInterface()
    {
        echo '<div class="wrap js-syncio-admin-interface">';
        esc_html_e('Loading, please wait...', 'syncio');
        echo '</div>';
    }

    function registerMenu()
    {
        //$icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwLjA1NjUgMy45OTc4NlYyLjI0NjQ0QzEwLjA1NjUgMi4wNTcgOS44NTE0NiAxLjkzODY3IDkuNjg3MzUgMi4wMzMzTDQuMjY1MDQgNS4xNjM5M0M0LjEwMTAyIDUuMjU4NjYgNC4xMDEwMiA1LjQ5NTQyIDQuMjY1MDQgNS41OTAxNEw3LjA3NjExIDcuMjEzMDlDNy44MTExOCA2LjQ0ODYxIDguODQzNjMgNS45NzIxMSA5Ljk4Nzg5IDUuOTcyMTFDMTIuMjE5MyA1Ljk3MjExIDE0LjAyOCA3Ljc4MDg3IDE0LjAyOCAxMC4wMTIyQzE0LjAyOCAxMC40MTcyIDEzLjk2NzggMTAuODA3OSAxMy44NTY5IDExLjE3NjhMMTUuNTE0NSAxMi4xNDgyQzE1Ljc4ODkgMTEuNDY0NyAxNS45NDAzIDEwLjcxODMgMTUuOTQwMyA5LjkzNjUyQzE1Ljk0MDMgNi42NzQ3OSAxMy4zMTExIDQuMDI4MjYgMTAuMDU2NSAzLjk5Nzg2WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTE1LjU2MjkgMTQuNDA5OUwxMi44NjIyIDEyLjg1MDdDMTIuMTI5OCAxMy41OTI0IDExLjExMjggMTQuMDUyNCA5Ljk4Nzk4IDE0LjA1MjRDNy43NTY2MiAxNC4wNTI0IDUuOTQ3ODIgMTIuMjQzNiA1Ljk0NzgyIDEwLjAxMjJDNS45NDc4MiA5Ljc2MjcxIDUuOTcxNiA5LjUxODggNi4wMTQ5MyA5LjI4MTc3TDQuMjk2MTcgOC4yNzQzOEM0LjE0MjY1IDguODAxOTEgNC4wNjAwNiA5LjM1OTU4IDQuMDYwMDYgOS45MzY2QzQuMDYwMDYgMTMuMTQwNSA2LjU5NzAxIDE1Ljc1MDUgOS43NzE0IDE1Ljg3MDlWMTcuNzUzNkM5Ljc3MTQgMTcuOTQzIDkuOTc2NTggMTguMDYxNCAxMC4xNDA1IDE3Ljk2NjdMMTUuNTYyOSAxNC44MzYxQzE1LjcyNjkgMTQuNzQxNCAxNS43MjY5IDE0LjUwNDYgMTUuNTYyOSAxNC40MDk5WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTcuNTMgMTAuMDEyMkM3LjUzIDExLjM2OTcgOC42MzAzOSAxMi40NzAyIDkuOTg3OTggMTIuNDcwMkMxMS4zNDU1IDEyLjQ3MDIgMTIuNDQ2IDExLjM2OTcgMTIuNDQ2IDEwLjAxMjJDMTIuNDQ2IDguNjU0NzMgMTEuMzQ1NSA3LjU1NDI1IDkuOTg3OTggNy41NTQyNUM4LjYzMDM5IDcuNTU0MjUgNy41MyA4LjY1NDczIDcuNTMgMTAuMDEyMloiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik03LjUzIDEwLjAxMjJDNy41MyAxMS4zNjk3IDguNjMwMzkgMTIuNDcwMiA5Ljk4Nzk4IDEyLjQ3MDJDMTEuMzQ1NSAxMi40NzAyIDEyLjQ0NiAxMS4zNjk3IDEyLjQ0NiAxMC4wMTIyQzEyLjQ0NiA4LjY1NDczIDExLjM0NTUgNy41NTQyNSA5Ljk4Nzk4IDcuNTU0MjVDOC42MzAzOSA3LjU1NDI1IDcuNTMgOC42NTQ3MyA3LjUzIDEwLjAxMjJaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K';
        $icon = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwLjA2NjYgMi45MjcwM1YwLjg2MzE2QzEwLjA2NjYgMC42Mzk5MTkgOS44MjUwNiAwLjUwMDQ3NyA5LjYzMTY3IDAuNjExOTkxTDMuMjQyMDQgNC4zMDExMkMzLjA0ODc2IDQuNDEyNzQgMy4wNDg3NiA0LjY5MTc1IDMuMjQyMDQgNC44MDMzN0w2LjU1NDU5IDYuNzE1ODRDNy40MjA4IDUuODE0OTkgOC42Mzc0MyA1LjI1MzQ3IDkuOTg1ODMgNS4yNTM0N0MxMi42MTUzIDUuMjUzNDcgMTQuNzQ2NyA3LjM4NDkyIDE0Ljc0NjcgMTAuMDE0M0MxNC43NDY3IDEwLjQ5MTUgMTQuNjc1NyAxMC45NTIgMTQuNTQ1MSAxMS4zODY2TDE2LjQ5ODQgMTIuNTMxNEMxNi44MjE4IDExLjcyNTkgMTcuMDAwMSAxMC44NDYzIDE3LjAwMDEgOS45MjUxM0MxNy4wMDAxIDYuMDgxNTIgMTMuOTAxOCAyLjk2Mjg1IDEwLjA2NjYgMi45MjcwM1oiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0xNi41NTU1IDE1LjE5NjVMMTMuMzcyOSAxMy4zNTkyQzEyLjUwOTggMTQuMjMzMiAxMS4zMTE0IDE0Ljc3NTIgOS45ODU5NCAxNC43NzUyQzcuMzU2NTEgMTQuNzc1MiA1LjIyNTAyIDEyLjY0MzggNS4yMjUwMiAxMC4wMTQzQzUuMjI1MDIgOS43MjAzIDUuMjUzMDQgOS40MzI4OSA1LjMwNDExIDkuMTUzNTdMMy4yNzg3MiA3Ljk2NjQ2QzMuMDk3ODEgOC41ODgxMSAzLjAwMDQ5IDkuMjQ1MjYgMy4wMDA0OSA5LjkyNTIxQzMuMDAwNDkgMTMuNzAwNyA1Ljk5MDAyIDE2Ljc3NjMgOS43MzA3MSAxNi45MTgyVjE5LjEzNjdDOS43MzA3MSAxOS4zNiA5Ljk3MjUxIDE5LjQ5OTQgMTAuMTY1NyAxOS4zODc4TDE2LjU1NTUgMTUuNjk4OEMxNi43NDg2IDE1LjU4NzIgMTYuNzQ4NiAxNS4zMDgyIDE2LjU1NTUgMTUuMTk2NVoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik03LjA4OTQ1IDEwLjAxNDRDNy4wODk0NSAxMS42MTQgOC4zODYxNSAxMi45MTA4IDkuOTg1OTQgMTIuOTEwOEMxMS41ODU2IDEyLjkxMDggMTIuODgyNCAxMS42MTQgMTIuODgyNCAxMC4wMTQ0QzEyLjg4MjQgOC40MTQ2NyAxMS41ODU2IDcuMTE3ODcgOS45ODU5NCA3LjExNzg3QzguMzg2MTUgNy4xMTc4NyA3LjA4OTQ1IDguNDE0NjcgNy4wODk0NSAxMC4wMTQ0WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTcuMDg5NDUgMTAuMDE0NEM3LjA4OTQ1IDExLjYxNCA4LjM4NjE1IDEyLjkxMDggOS45ODU5NCAxMi45MTA4QzExLjU4NTYgMTIuOTEwOCAxMi44ODI0IDExLjYxNCAxMi44ODI0IDEwLjAxNDRDMTIuODgyNCA4LjQxNDY3IDExLjU4NTYgNy4xMTc4NyA5Ljk4NTk0IDcuMTE3ODdDOC4zODYxNSA3LjExNzg3IDcuMDg5NDUgOC40MTQ2NyA3LjA4OTQ1IDEwLjAxNDRaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K';
        add_menu_page(
            "Syncio",
            "Syncio",
            "manage_options",
            "syncio",
            array($this, "menu"),
            $icon,
            '55.5'
        );
    }

    function menu()
    {
        ?>
        <div class="wrap">
            <?php
            // Include the file directly
            include(__DIR__ . '/View/index.php');
            ?>
        </div>
        <?php
    }

    function add_intercom_chatbot() {

         if( ! isset($_GET['page'])) {
            return;
        }
        
        if (! isset($_GET['page']) && strpos(sanitize_key($_GET['page']), 'syncio') !== true) {
            return;
        }
        ?>
        <script>
            window.intercomSettings = {
                api_base: "https://api-iam.intercom.io",
                app_id: "<?php echo SYNCIO_INTERCOM_APP_ID; ?>",
                //user_id: user.id, // IMPORTANT: Replace "user.id" with the variable you use to capture the user's ID
                name: "<?php echo get_option('siteurl'); ?>", // IMPORTANT: Replace "user.name" with the variable you use to capture the user's name
                email: "<?php echo get_option('admin_email'); ?>", // IMPORTANT: Replace "syncio_globals.admin_email" with the variable you use to capture the admin's email address
                'Platform': 'woo',
                'URL': "<?php echo get_option('siteurl'); ?>",
            };
        </script>

        <script>
            (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/z6dvdx17';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
        </script>
        <?php
        if(get_option('syncio_plugin_just_activated')) {
            ?>
            <script>
                console.log('plugin activated- intercom event fired');
                window.Intercom('trackEvent', 'plugin-installed', {
                    plugin_name: 'Syncio-wp-plugin',
                    plugin_version: '<?php echo SYNCIO_PLUGIN_VERSION; ?>',
                });
            </script>
            <?php
            delete_option('syncio_plugin_just_activated');
        }

    }

    private function init_rests()
    {
        include_once SYNCIO_PLUGIN_DIR . '/includes/syncioApi.php';
    }
}

Syncio::get_instance();
