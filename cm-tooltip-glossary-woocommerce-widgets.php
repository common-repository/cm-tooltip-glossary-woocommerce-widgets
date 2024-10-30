<?php
/*
  Plugin Name: CM Tooltip Glossary Woocommerce Widgets
  Plugin URI: http://tooltip.cminds.com/
  Description: Adds an Woocommerce Widgets to CM Tooltip Glossary
  Version: 1.0.0
  Author: CreativeMindsSolutions
  Author URI: http://plugins.cminds.com/
 */

// Exit if accessed directly
if( !defined('ABSPATH') )
{
    exit;
}

/**
 * Main plugin class file.
 * What it does:
 * - checks which part of the plugin should be affected by the query frontend or backend and passes the control to the right controller
 * - manages installation
 * - manages uninstallation
 * - defines the things that should be global in the plugin scope (settings etc.)
 * @author CreativeMindsSolutions - Marcin Dudek
 */
class CMTooltipGlossaryWoocommerceSupport
{
    public static $calledClassName;
    protected static $instance = NULL;

    /**
     * Main Instance
     *
     * Insures that only one instance of class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 1.0
     * @static
     * @staticvar array $instance
     * @return The one true AKRSubscribeNotifications
     */
    public static function instance()
    {
        $class = __CLASS__;
        if( !isset(self::$instance) && !( self::$instance instanceof $class ) )
        {
            self::$instance = new $class;
        }
        return self::$instance;
    }

    public function __construct()
    {
        if( empty(self::$calledClassName) )
        {
            self::$calledClassName = __CLASS__;
        }

        self::setupConstants();

        add_action('admin_init', array(self::$calledClassName, 'checkForBase'));

        require_once CMTTWS_PLUGIN_DIR . 'cm-woocommerce-api.php';
    }

    public static function checkForBase()
    {
        $isCmTooltipGlossaryActive = is_plugin_active('cm-tooltip-glossary/cm-tooltip-glossary.php');
        if(!$isCmTooltipGlossaryActive)
        {
            add_action('admin_notices', array(self::$calledClassName, '__showProMessage'));
        }
    }

    /**
     * Shows the message about Pro versions on activate
     */
    public static function __showProMessage()
    {
        /*
         * Only show to admins
         */
        if( current_user_can('manage_options') )
        {
            ?>
            <div id="message" class="updated fade">
                <p>
                    <strong>&quot;<?php echo CMTTWS_NAME ?>&quot;</strong> plugin requires <strong>&quot; CM Tooltip Glossary &quot;</strong> plugin to be activated! <br/>
                    <i>For more information about extending &quot; CM Tooltip Glossary &quot; please visit <a href="http://tooltip.cminds.com/"  target="_blank"> this page.</a></i>
                </p>
            </div>
            <?php
            delete_option('cmtt_afterActivation');
        }
    }

    /**
     * Setup plugin constants
     *
     * @access private
     * @since 1.1
     * @return void
     */
    private static function setupConstants()
    {
        /**
         * Define Plugin Version
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_VERSION') )
        {
            define('CMTTWS_VERSION', '1.0.0');
        }

        /**
         * Define Plugin Directory
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_PLUGIN_DIR') )
        {
            define('CMTTWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        }

        /**
         * Define Plugin URL
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_PLUGIN_URL') )
        {
            define('CMTTWS_PLUGIN_URL', plugin_dir_url(__FILE__));
        }

        /**
         * Define Plugin File Name
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_PLUGIN_FILE') )
        {
            define('CMTTWS_PLUGIN_FILE', __FILE__);
        }

        /**
         * Define Plugin Slug name
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_SLUG_NAME') )
        {
            define('CMTTWS_SLUG_NAME', 'cm-tooltip-glossary-woocommerce-support');
        }

        /**
         * Define Plugin name
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_NAME') )
        {
            define('CMTTWS_NAME', 'CM Tooltip Glossary Woocommerce Support');
        }

        /**
         * Define Plugin basename
         *
         * @since 1.0
         */
        if( !defined('CMTTWS_PLUGIN') )
        {
            define('CMTTWS_PLUGIN', plugin_basename(__FILE__));
        }
    }

    public static function _install($networkwide)
    {
        global $wpdb;

        if( function_exists('is_multisite') && is_multisite() )
        {
            /*
             * Check if it is a network activation - if so, run the activation function for each blog id
             */
            if( $networkwide )
            {
                /*
                 * Get all blog ids
                 */
                $blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs}"));
                foreach($blogids as $blog_id)
                {
                    switch_to_blog($blog_id);
                    self::__install();
                }
                restore_current_blog();
                return;
            }
        }

        self::__install();
    }

    private static function __install()
    {
        /*
         * Calling this function will setup the default option values
         */
        CMTooltipGlossaryWoocommerceSupportAPI::setupBasicOptions();
        return;
    }

    private static function __resetOptions()
    {
        return;
    }

    public static function _uninstall()
    {
        /*
         * Calling this function will delete the plugin options
         */
        CMTooltipGlossaryWoocommerceSupportAPI::deleteOptions();
        return;
    }

    public function registerAjaxFunctions()
    {
        return;
    }

}

/**
 * The main function responsible for returning the one true plugin class
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $marcinPluginPrototype = MarcinPluginPrototypePlugin(); ?>
 *
 * @since 1.0
 * @return object The one true CM_Micropayment_Platform Instance
 */
function CMTooltipGlossaryWoocommerceSupportInit()
{
    return CMTooltipGlossaryWoocommerceSupport::instance();
}

$CMTooltipGlossaryWoocommerceSupport = CMTooltipGlossaryWoocommerceSupportInit();

register_activation_hook(__FILE__, array('CMTooltipGlossaryWoocommerceSupport', '_install'));
register_deactivation_hook(__FILE__, array('CMTooltipGlossaryWoocommerceSupport', '_uninstall'));