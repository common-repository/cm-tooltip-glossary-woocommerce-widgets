<?php
CMTooltipGlossaryWoocommerceSupportAPI::init();
register_activation_hook(CMTTWS_PLUGIN_FILE, array('CMTooltipGlossaryWoocommerceSupportAPI', '_install'));

class CMTooltipGlossaryWoocommerceSupportAPI
{
    const WOOCOMMERCE_ENABLED_KEY = 'cmtt_tooltip3RD_WoocommerceEnabled';
    const WOOCOMMERCE_PRODUCT_META_KEY = '_cmtt_woocommerce_product_id';
    const WOOCOMMERCE_DISABLED_META_KEY = '_cmtt_woocommerce_disabled';

    public static $tableExists = false;
    public static $calledClassName;
    protected static $viewsPath = NULL;

    public static function _install()
    {
        return;
    }

    public static function init()
    {
        if( empty(self::$calledClassName) )
        {
            self::$calledClassName = __CLASS__;
        }

        self::$viewsPath = CMTTWS_PLUGIN_DIR . 'views/';

        add_action('add_meta_boxes', array(self::$calledClassName, 'registerBoxes'));
        add_action('save_post', array(self::$calledClassName, 'savePostdata'));
        add_action('update_post', array(self::$calledClassName, 'savePostdata'));

        add_filter('cmtt_thirdparty_option_names', array(self::$calledClassName, 'addOptionNames'));
//        add_filter('cmtt_add_properties_metabox', array(self::$calledClassName, 'addToExcludeMetabox'));
        add_filter('cmtt_tooltip_content_add', array(self::$calledClassName, 'addWidgetToTooltipContent'), 10, 2);

//        add_action('woocommerce_before_add_to_cart_button', array(self::$calledClassName, 'addBeforeAddToCart'));

        add_filter('cmtt-settings-tabs-array', array(self::$calledClassName, 'addSettingsTab'));

        add_filter('wp_enqueue_scripts', array(self::$calledClassName, 'addScriptsAndStyles'));

        /*
         * Tooltips have to be clickable for this extension to have sense
         */
        update_option('cmtt_tooltipIsClickable', 1);
    }

    /**
     * This function setups the basic options for the plugin
     */
    public static function addScriptsAndStyles()
    {
        wp_enqueue_style('cmttwc-style', CMTTWS_PLUGIN_URL . 'style.css');
    }

    /**
     * This function setups the basic options for the plugin
     */
    public static function setupBasicOptions()
    {
        update_option(self::WOOCOMMERCE_ENABLED_KEY, 1);
    }

    /**
     * This function setups the basic options for the plugin
     */
    public static function deleteOptions()
    {
        $options = self::addOptionNames(array());
        foreach($options as $optionName)
        {
            delete_option($optionName);
        }

        /*
         * We may change this option here - if there's any other extension requiring tooltips to be clickable
         * it will reenable it anyway
         */
        update_option('cmtt_tooltipIsClickable', 0);
    }

    /**
     * Returns the list of post types for which the custom settings may be applied
     * @return type
     */
    public static function addSettingsTab($tabs)
    {
        if( !in_array('API', $tabs) )
        {
            $tabs += array('5' => 'API');
        }
        add_filter('cmmt-custom-settings-tab-content-5', array(self::$calledClassName, 'addSettingsTabContent'));
        return $tabs;
    }

    /**
     * Adds the content to the appropriate settings tab
     * @return type
     */
    public static function addSettingsTabContent($content)
    {
        ob_start();
        require_once self::$viewsPath . 'backend/woocommerce_settings.phtml';
        $content .= ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public static function isWoocommerce()
    {
        return class_exists('Woocommerce');
    }

    /**
     * Returns the list of post types for which the custom settings may be applied
     * @return type
     */
    public static function getApplicablePostTypes()
    {
        return array('glossary');
    }

    /**
     * Returns the list of post types for which the custom settings may be applied
     * @return type
     */
    public static function addBeforeAddToCart()
    {
        global $product;

        /*
         * $product should be the Woocommerce product object, and Woocommerce must be activated
         */
        if( empty($product) || !is_object($product) || !self::isWoocommerce() )
        {
            return;
        }
        $tooltipText = str_replace('"', '\'', $product->get_attribute('tooltip'));

        if( !empty($tooltipText) )
        {
            $somePseudorandomId = 'wooButtonNavigator' . sha1(microtime());
            ob_start();
            ?>
            <i class="<?php echo $somePseudorandomId; ?>"></i>
            <script type="text/javascript">
                (function ($) {
                    $(document).ready(function () {

                        var pseudoRandomSelector = '.<?php echo $somePseudorandomId; ?>',
                                tooltipText = "<?php echo $tooltipText; ?>",
                                $pseudoRandomElement = $(pseudoRandomSelector),
                                $wooCommerceButton = $pseudoRandomElement.parent().find('.single_add_to_cart_button');

                        if ($wooCommerceButton.length)
                        {
                            $wooCommerceButton.attr('data-tooltip', tooltipText);
                        }
                    });
                })(jQuery);
            </script>
            <?php
            $script = ob_get_clean();
            echo $script;
        }
    }

    /**
     * Adds the Woocommerce options to the saved options
     * @return string
     */
    public static function addOptionNames($option_names)
    {
        $option_names = array_merge($option_names, array(
            self::WOOCOMMERCE_ENABLED_KEY,
                )
        );
        return $option_names;
    }

    /**
     * Register metaboxes
     */
    public static function registerBoxes()
    {
        if( self::isWoocommerce() )
        {
            foreach(self::getApplicablePostTypes() as $postType)
            {
                add_meta_box('cmtt-woocommerce-metabox', 'CM Tooltip - Woocommerce', array(self::$calledClassName, 'showMetaBox'), $postType, 'side', 'high');
            }
        }
    }

    /**
     * Shows metabox containing selectbox with woocommerce category ID which should be advertised in the Tooltips on this page
     * @global type $post
     */
    public static function showMetaBox()
    {
        global $post;
        $selectedProduct = get_post_meta($post->ID, self::WOOCOMMERCE_PRODUCT_META_KEY, true);
        echo self::getProductSelect(self::WOOCOMMERCE_PRODUCT_META_KEY, $selectedProduct);
    }

    public static function addToExcludeMetabox($excluded)
    {
        if( self::isWoocommerce() )
        {
            $excluded = array_merge($excluded, array(
                substr(self::WOOCOMMERCE_DISABLED_META_KEY, 1) => 'Don\'t show Woocommerce Widget for term')
            );
        }
        return $excluded;
    }

    /**
     * Returns the HTML of the Woocommerce size list select
     * @param string $selectedValue - selected value of the select
     * @return string (HTML)
     */
    public static function getAffiltiateWidget($productId = '')
    {
        $content = '';

        if( !empty($productId) )
        {
            ob_start();
            require 'views/frontend/woocommerce_widget.phtml';
            $content = ob_get_contents();
            ob_end_clean();
        }

        return $content;
    }

    public static function addWidgetToTooltipContent($glossaryItemContent, $glossary_item)
    {
        if( self::isWoocommerce() && self::woocommerce_enabled() && !get_post_meta($glossary_item->ID, self::WOOCOMMERCE_DISABLED_META_KEY, true) )
        {
            $productId = get_post_meta($glossary_item->ID, self::WOOCOMMERCE_PRODUCT_META_KEY, true);
            $glossaryItemContent .= self::getAffiltiateWidget($productId);
        }

        return $glossaryItemContent;
    }

    /**
     * Saves the information form the metabox in the post's meta
     * @param type $post_id
     */
    public static function savePostdata($post_id)
    {
        $postType = isset($_POST['post_type']) ? $_POST['post_type'] : '';

        if( in_array($postType, self::getApplicablePostTypes()) )
        {
            $woocommerceProduct = ( isset($_POST[self::WOOCOMMERCE_PRODUCT_META_KEY])) ? $_POST[self::WOOCOMMERCE_PRODUCT_META_KEY] : '0';
            update_post_meta($post_id, self::WOOCOMMERCE_PRODUCT_META_KEY, $woocommerceProduct);
        }
    }

    /**
     * Returns all the Woocommerce products
     * @return type
     */
    public static function getAllProducts()
    {
        $args = array('post_type' => 'product');
        $products = new WP_Query($args);
        return $products->get_posts();
    }

    /**
     * Returns the select containing all the Woocommerce products
     * @param type $selectName
     * @param string $selectedValue
     * @return type
     */
    public static function getProductSelect($selectName = self::WOOCOMMERCE_PRODUCT_META_KEY, $selectedValue = '')
    {
        ob_start();
        if( empty($selectedValue) )
        {
            $selectedValue = '0';
        }
        require 'views/backend/woocommerce_products.phtml';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Returns TRUE if the general setting is enabled
     * @return type
     */
    public static function woocommerce_enabled()
    {
        return $source_id = get_option(self::WOOCOMMERCE_ENABLED_KEY, 0);
    }

    public static function woocommerce_show_in_tooltip()
    {
        return $source_id = get_option('cmtt_tooltip3RD_WoocommerceTooltip', 0);
    }

    public static function woocommerce_show_in_term()
    {
        return $source_id = get_option('cmtt_tooltip3RD_WoocommerceTerm', 0);
    }

}