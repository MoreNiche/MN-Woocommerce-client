<?php

/**
 * Affiliate tracking integration class
 *
 */
class Twist_WC_Affiliate_Integration extends WC_Integration {

    CONST MN_AFFILIATE_TRACKED_META_KEY = '_MN_AFF_TRACKED';
    CONST AUTHPLUS_UTM_SOURCE = 'authplus';
    const AUTHPLUS_PIXEL_PARAMS = "&ap={AP}&aa={AA}&am={AM}";
    const FREEZE_PIXEL_STATUS_ID = "ST998";

    CONST AFF_RECOVERY_MAP = [
        'email'    => 1,
        'sms'      => 2,
        'phone'    => 3,
        'facebook' => 4
    ];

    function __construct() {
        $this->id                 = 'wc_aff_tracking';
        $this->method_title       = __( 'Affiliate Tracking Pixel', 'wc-affiliate-tracking' );
        $this->method_description = __( 'Affiliate tracking pixel. Insert your affiliate codes here:', 'wc-affiliate-tracking' );


        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // $this->cakeTrackEndPoint    = "https://sandbox.mn/"; #sandbox

        $this->cakeSaltKey = $this->settings['cakeSaltKey'];

        $this->cakeTrackEndPoint = $this->settings['cakeTrackEndPoint'];

        $this->cakePixel = $this->settings['cakePixel'];

        $this->defaultCurrency = $this->settings['cakeDefaultCurrency'];

        $this->acceptedCurrencies = isset( $this->settings['cakeAcceptedCurrencies'] ) ? $this->settings['cakeAcceptedCurrencies'] : '';

        $this->c = isset( $this->settings['affiliateProgrammeId'] ) ? $this->settings['affiliateProgrammeId'] : '';

        $this->cakeSubSite = $this->settings['cakeSubSite'];

        $this->reportUpsells = isset( $this->settings['reportUpsells'] ) ? $this->settings['reportUpsells'] : '';

        $this->excludeTax = isset( $this->settings['excludeTax'] ) ? $this->settings['excludeTax'] : 'no';

        $this->firePixel = true;


        /**
         * Setup the array for the accepted currencies
         */
        if ( isset ( $this->settings['cakeAcceptedCurrencies'] ) && '' !== $this->settings['cakeAcceptedCurrencies'] ) {
            $this->acceptedCurrencies = explode( ',', $this->acceptedCurrencies );
        } else {
            $this->acceptedCurrencies = array( $this->defaultCurrency );
        }

        /**
         * Set the offerID depending on if subsite exists
         */
        if ( $this->cakeSubSite == 'yes' ) {
            $this->cakeOfferID = [
                "GBP" => $this->settings['offerIDGBP'],
                "EUR" => $this->settings['offerIDEUR'],
                "USD" => $this->settings['offerIDUSD'],
            ];

        } else {
            $this->cakeOfferID = [
                "GBP" => $this->get_option( "offerID{$this->defaultCurrency}" ),
                "EUR" => $this->get_option( "offerID{$this->defaultCurrency}" ),
                "USD" => $this->get_option( "offerID{$this->defaultCurrency}" ),
            ];

        }

        if (
            isset( $_GET['utm_medium'] ) &&
            isset( $_GET['utm_campaign'] ) &&
            isset( $_GET['utm_source'] ) &&
            $_GET['utm_source'] == self::AUTHPLUS_UTM_SOURCE
        ) {
            $_SESSION['am'] = self::AFF_RECOVERY_MAP[ $_GET['utm_medium'] ];
            $_SESSION['ap'] = $_GET['utm_campaign'];
        }


        //Save Changes action for admin settings
        add_action( "woocommerce_update_options_integration_" . $this->id, array( $this, "process_admin_options" ) );

        add_action( 'woocommerce_thankyou', array( $this, 'code_handler' ) );

        add_action( 'mnupsell_upsell_order_tracking', array( $this, 'code_handler' ), 10 );

        //Fire this in checkout
        add_action( 'woocommerce_before_checkout_form', array( $this, 'print_upsale_code' ) );

        // Run domain ref tracking
//		add_action( 'wp_footer', array($this, 'mn_direct_link_track') );
    }

    /**
     * WooCommerce settings API fields for storing our codes
     *
     * @return void
     */
    function init_form_fields() {
        $this->form_fields = array(
            "cakeSaltKey"            => array(
                'title'       => __( 'CAKE Security Saltkey', 'wc-affiliate-tracking' ),
                'description' => __( 'Write something here to add an hash key to secure the sale pixel', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'cakeSaltKey',
                'type'        => 'text',
            ),
            "cakeTrackEndPoint"      => array(
                'title'       => __( 'CAKE Tracking EndPoint', 'wc-affiliate-tracking' ),
                'description' => __( 'Add the Tracking endpoint e.g. https://sandbox.mixi.mn/ ', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'cakeTrackEndPoint',
                'type'        => 'text',
            ),
            "cakePixel"              => array(
                'title'       => __( 'CAKE Pixel Mask', 'wc-affiliate-tracking' ),
                'description' => __( 'Add the mask for the pixel ', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'cakePixel',
                'type'        => 'text',
            ),
            "cakeDefaultCurrency"    => array(
                'title'       => __( 'Default Currency', 'wc-affiliate-tracking' ),
                'description' => __( 'Select the default currency.', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'cakeDefaultCurrency',
                'type'        => 'select',
                'options'     => array(
                    'USD' => 'USD',
                    'GBP' => 'GBP',
                    'EUR' => 'EUR'
                )
            ),
            "cakeAcceptedCurrencies" => array(
                'title'       => __( 'CAKE Currencies Accepted', 'wc-affiliate-tracking' ),
                'description' => __( 'Enter comma separated currencies accepted i.e USD,GBP,EUR', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'cakeAcceptedCurrencies',
                'type'        => 'text',
            ),
            "cakeSubSite"            => array(
                'title'       => __( 'Sub Site exists', 'wc-affiliate-tracking' ),
                'description' => __( 'Select if Sub Sites exists', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'cakeSubSite',
                'type'        => 'select',
                'options'     => array(
                    'yes' => 'Yes',
                    'no'  => 'No'
                )
            ),
            "reportUpsells"          => array(
                'title'       => __( 'Report Upsells', 'wc-affiliate-tracking' ),
                'description' => __( 'Whether to report upsells or not', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'reportUpsells',
                'type'        => 'checkbox',
            ),
            "excludeTax"             => array(
                'title'       => __( 'Exclude Tax', 'wc-affiliate-tracking' ),
                'description' => __( 'Whether to exclude tax for UK and EU sales or not', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'excludeTax',
                'type'        => 'checkbox',
            ),
            "offerIDUSD"             => array(
                'title'       => __( 'USD Offer ID', 'wc-affiliate-tracking' ),
                'description' => __( 'Add your USD Offer ID e.g. 6', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'offerIDUSD',
                'type'        => 'text',
            ),
            "offerIDGBP"             => array(
                'title'       => __( 'GBP Offer ID', 'wc-affiliate-tracking' ),
                'description' => __( 'Add your GBP Offer ID e.g. 7', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'offerIDGBP',
                'type'        => 'text',
            ),
            "offerIDEUR"             => array(
                'title'       => __( 'EUR Offer ID', 'wc-affiliate-tracking' ),
                'description' => __( 'Add your EUR Offer ID e.g. 8', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'offerIDEUR',
                'type'        => 'text',
            ),
            "upsellLinkUSD"          => array(
                'title'       => __( 'USD Upsell Link', 'wc-affiliate-tracking' ),
                'description' => __( 'Add your USD Upsell Link', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'upsellLinkUSD',
                'type'        => 'text',
            ),
            "upsellLinkGBP"          => array(
                'title'       => __( 'GBP Upsell Link', 'wc-affiliate-tracking' ),
                'description' => __( 'Add your GBP Upsell Link', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'upsellLinkGBP',
                'type'        => 'text',
            ),
            "upsellLinkEUR"          => array(
                'title'       => __( 'EUR Upsell Link', 'wc-affiliate-tracking' ),
                'description' => __( 'Add your EUR Upsell Link', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'upsellLinkEUR',
                'type'        => 'text',
            ),
            "affiliateProgrammeId"   => array(
                'title'       => __( 'Affiliate Programme Id', 'wc-affiliate-tracking' ),
                'description' => __( 'Enter the affiliate programme ID', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'affiliateProgrammeId',
                'type'        => 'textarea',
            ),
            "directAffiliates"       => array(
                'title'       => __( 'Direct Affiliates', 'wc-affiliate-tracking' ),
                'description' => __( 'Add direct affiliates ( One on each line - no spaces)', 'wc-affiliate-tracking' ),
                'desc_tip'    => true,
                'id'          => 'directAffiliates',
                'type'        => 'textarea',
            )
        );
    }

    /**
     * Save callback of Woo integration code settings API
     *
     * @param string $key
     *
     * @return string
     */
    function validate_textarea_field( $key, $value ) {
        $text = trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );

        return $text;
    }


    function code_handler( $orderID = 0 ) {
        if ( is_order_received_page() || $orderID > 0 ) {
            if ( WP_ENV != 'development' &&
                 get_post_meta( $orderID, self::MN_AFFILIATE_TRACKED_META_KEY, true ) ) {
                return;
            }

            if ( $orderID <= 0 ) {
                $orderID = wc_get_order_id_by_order_key( $_GET["key"] );
            }

            $order = new WC_Order( $orderID );

            $status = false;

            if ( ! $order->is_paid() ) {
                if (
                    $order->get_payment_method() === 'cod' ||
                    $order->get_payment_method() === 'bt' ) {
                    $status = self::FREEZE_PIXEL_STATUS_ID;
                } else {
                    return;
                }

            }

            //Check if the order currency is accepted by CAKE
            if ( in_array( $order->order_currency, $this->acceptedCurrencies ) ) {
                //USD || GBP || EUR ORDER
                $acceptsOrderCurrency = true;
                $currencyCode         = $order->order_currency; // use transaction code

            } else {
                $acceptsOrderCurrency = false;
                $currencyCode         = $this->defaultCurrency; //default to store currency set in plugin
            }

            // get country for tax purposes (billing one)
            $trackingCountry = $order->billing_country;
            if ( is_null( $trackingCountry ) ) {
                $trackingCountry = "UNKNOWN";
            }

            $eutaxrate = $this->_getVATrate( $trackingCountry );


            // cake order data
            $cakeData      = [
                'SKUS'       => [],
                'QTYS'       => [],
                'SKU_PRICES' => [],
                'DISCOUNTS'  => [],
            ];
            $orderSubtotal = 0;
            $orderTotal    = 0;
            $upsellorder   = 0;


            // Format Items
            // is this a common order or a renewal?
            if ( sizeof( $order->get_items() ) > 0 ) {
                foreach ( $order->get_items() as $item ) {
                    // common orders have items
                    $product_variation_id = $item['variation_id'];

                    // Check if product has variation.
                    if ( $product_variation_id ) {
                        $product = new  WC_Product_Variation( $item['variation_id'] );
                    } else {
                        $product = new  WC_Product( $item['product_id'] );
                    }

                    $sku = str_replace( '-', 'X', $product->get_sku() );

                    if ( get_post_meta( $order->get_id(), '_luup_order_type', true ) ) {
                        $sku         = $sku . 'UP';
                        $upsellorder = 1;
                        if ( $this->reportUpsells == 'no' ) {
                            $this->firePixel = false;
                        }
                    }

                    /**
                     * If order is accepted currency send the "line_total"
                     * otherwise send the "line_total_base_currency"
                     */
                    if ( $acceptsOrderCurrency ) { //USD/EUR/GBP
                        $_value = $item['line_total'];
                    } else {
                        $_value = $item['line_total_base_currency'];
                    }

                    $_quantity  = $item['qty'];
                    $_itemValue = round( ( $_value / $_quantity ) * $eutaxrate, 2 );

                    // updating cake data
                    array_push( $cakeData['SKUS'], $sku );
                    array_push( $cakeData['QTYS'], $_quantity );
                    array_push( $cakeData['SKU_PRICES'], $_itemValue );
                    array_push( $cakeData['DISCOUNTS'], 0 );
                    $orderSubtotal += $_itemValue * $_quantity;
                    $orderTotal    += $_value;

                }
            } else {
                // renewals don't have items
                if ( $acceptsOrderCurrency ) { // USD/EUR/GBP
                    $totalDiscounted = $order->order_total * $eutaxrate;
                } else {
                    $totalDiscounted = $order->get_total() * $eutaxrate;
                }

                // updating cake data
                array_push( $cakeData['SKUS'], 'default' );
                array_push( $cakeData['QTYS'], 1 );
                array_push( $cakeData['SKU_PRICES'], round( $totalDiscounted, 2 ) );
                array_push( $cakeData['DISCOUNTS'], 0 );
                $orderSubtotal = round( $totalDiscounted, 2 );
            }

            /* cake conversion pixel - START */
            if ( $this->firePixel ) {

                //Check coupons
                if ( $order->get_used_coupons() ) {
                    $coupons = implode( "^", $order->get_used_coupons() );
                }

                // creating an hash using a saltkey
                if ( empty( $this->cakeSaltKey ) ) {
                    // nothing to do
                    $hash = false; // empty array
                } else {
                    $hash = $this->createHash( $order->get_id(), $this->cakeOfferID[ $currencyCode ] );
                }

                $ap = isset( $_SESSION['ap'] ) ? $_SESSION['ap'] : '';
                $aa = isset( $_COOKIE['mn_aff'] ) ? $_COOKIE['mn_aff'] : '';
                $am = isset( $_SESSION['am'] ) ? $_SESSION['am'] : '';


                $cakePixel = $this->_getCakePixel(
                    [
                        $this->cakeTrackEndPoint,               //settings
                        $this->cakeOfferID[ $currencyCode ],    //array built from Currencies
                        $order->get_id(),
                        implode( "^", $cakeData['SKUS'] ),
                        implode( "^", $cakeData['QTYS'] ),
                        implode( "^", $cakeData['SKU_PRICES'] ),
                        implode( "^", $cakeData['DISCOUNTS'] ),
                        $orderSubtotal,
                        $orderTotal - $orderSubtotal,
                        $trackingCountry,
                        $orderSubtotal,
                        $orderTotal,
                        $ap,
                        $aa,
                        $am,
                        $order->get_payment_method(),
                        $upsellorder,
                    ], $hash, $coupons, $status
                );

                $this->print_conversion_code( $cakePixel );


                if ( ! add_post_meta( $orderID, self::MN_AFFILIATE_TRACKED_META_KEY, true, true ) ) {
                    update_post_meta( $orderID, self::MN_AFFILIATE_TRACKED_META_KEY, true );
                }

                $stringNote = 'MoreNiche Debug: ' . $cakePixel;
                $order->add_order_note( $stringNote );

            }
            /* cake conversion pixel - END */

        }
    }

    private function _getVATrate( $trackingCountry ) {
        // tax country list
        $taxCountries['GB'] = 1;
        $taxCountries['AL'] = 1;
        $taxCountries['AD'] = 1;
        $taxCountries['AT'] = 1;
        $taxCountries['BY'] = 1;
        $taxCountries['BE'] = 1;
        $taxCountries['BA'] = 1;
        $taxCountries['BG'] = 1;
        $taxCountries['HR'] = 1;
        $taxCountries['CY'] = 1;
        $taxCountries['CZ'] = 1;
        $taxCountries['DK'] = 1;
        $taxCountries['EE'] = 1;
        $taxCountries['FO'] = 1;
        $taxCountries['FI'] = 1;
        $taxCountries['FR'] = 1;
        $taxCountries['DE'] = 1;
        $taxCountries['GI'] = 1;
        $taxCountries['GR'] = 1;
        $taxCountries['HU'] = 1;
        $taxCountries['IS'] = 1;
        $taxCountries['IE'] = 1;
        $taxCountries['IT'] = 1;
        $taxCountries['LV'] = 1;
        $taxCountries['LB'] = 1;
        $taxCountries['LI'] = 1;
        $taxCountries['LT'] = 1;
        $taxCountries['LU'] = 1;
        $taxCountries['MT'] = 1;
        $taxCountries['MD'] = 1;
        $taxCountries['MC'] = 1;
        $taxCountries['ME'] = 1;
        $taxCountries['NL'] = 1;
        $taxCountries['NO'] = 1;
        $taxCountries['PL'] = 1;
        $taxCountries['PT'] = 1;
        $taxCountries['RO'] = 1;
        $taxCountries['RS'] = 1;
        $taxCountries['SK'] = 1;
        $taxCountries['SI'] = 1;
        $taxCountries['ES'] = 1;
        $taxCountries['SJ'] = 1;
        $taxCountries['SE'] = 1;
        $taxCountries['CH'] = 1;
        $taxCountries['TR'] = 1;
        $taxCountries['UA'] = 1;
        $taxCountries['VA'] = 1;

        // if tax is supposed to apply of 20% we will have to look at values at 0.8333333 or 83.33% of value
        if ( intval( $taxCountries[ $trackingCountry ] ) == 1 && 'no' == $this->excludeTax ) {
            $eutaxrate = 0.83333333;
        } else {
            $eutaxrate = 1;
        }

        return $eutaxrate;
    }

    /**
     * creating the hash array
     *
     * @param $orderID
     * @param $merchantID
     *
     * @return array
     */
    private function createHash( $orderID, $offerID ) {
        $time = time();

        return [
            'time' => $time,
            'hash' => md5( "@$orderID-$offerID-$time-" . $this->cakeSaltKey . "#" ),
        ];
    }

    private function _getCakePixel( $data, $hashArray = false, $coupons, $status = false ) {
        $options = [
            "{DOMAIN}",
            "{OFFER_ID}",
            "{TRANS_ID}",
            "{SKUS}",
            "{QTYS}",
            "{SKU_PRICES}",
            "{DISCOUNTS}",
            "{TOTAL_PRICE}",
            "{TAX}",
            "{COUNTRYCODE_ISO2}",
            "{ORDER_SUBTOTAL}",
            "{GROSS}",
            "{AP}",
            "{AA}",
            "{AM}",
            "{PAYMENT_METHOD}",
            "{UPSELLORDER}",
        ];
        $return  = str_replace( $options, $data, $this->cakePixel . SELF::AUTHPLUS_PIXEL_PARAMS );

        if ( $hashArray ) {
            $return = str_replace( [ "{TIME}", "{HASH}" ], $hashArray, $return . "&time={TIME}&hash={HASH}" );
        }

        $return = $return . "&coupon=$coupons";

        if ( $status ) {
            $return .= "&status=$status";
        }


        return $return;
    }

    /**
     * Prints the code
     *
     * @param string $code
     *
     * @return void
     */
    function print_conversion_code( $cakePixel ) {
        if ( $cakePixel == '' ) {
            return;
        }

        if ( isset( $_COOKIE['mn_ref'] ) ) {
            $cakePixel .= "&ref=" . urlencode( $_COOKIE['mn_ref'] );
        }

        echo "<!-- Affiliate Tracking -->\n";
        echo '<iframe src="' . $cakePixel . '" width="1" height="1" frameborder="0"></iframe>';
        echo '<script type="text/javascript" src="' . $cakePixel . '"></script>';
        echo "\n<!-- Affiliate Tracking -->\n";
    }

    // this function looks for a valid referral and then use it for tracking

    function print_upsale_code() {

        // Check if this is a subsite
        if ( $this->cakeSubSite == 'yes' ) {
            //Get the current order currency
            $currentCurrency = get_woocommerce_currency();
            // Produce the upsell link from settings depending on currency
            switch ( $currentCurrency ) {
                case 'USD':
                    $upsalePixel = 'USD' != $this->defaultCurrency ? $this->settings['upsellLinkUSD'] : false;
                    break;
                case 'EUR':
                    $upsalePixel = 'EUR' != $this->defaultCurrency ? $this->settings['upsellLinkEUR'] : false;
                    break;
                case 'GBP':
                    $upsalePixel = 'GBP' != $this->defaultCurrency ? $this->settings['upsellLinkGBP'] : false;
                default:
                    $upsell = "<!-- NO conversions -->";
                    break;
            }
        } else {
            // If there is no subsite there is no upsell pixel
            $upsalePixel = false;
        }

        if ( false != $upsalePixel ) {
            echo '<iframe src="' . $upsalePixel . '" width="1" height="1" frameborder="0"></iframe>';
            echo "<!-- $currentCurrency -->\n";
        }
    }

    /**
     * Finds an Order ID based on an order key.
     *
     * @access public
     *
     * @param string $order_key An order key has generated by
     *
     * @return int The ID of an order, or 0 if the order could not be found
     */

    function wc_get_order_id_by_order_key( $order_key ) {
        global $wpdb;
        // Faster than get_posts()
        $order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_key' AND meta_value = %s", $order_key ) );

        return $order_id;
    }

    public function mn_direct_link_track() {

        //TODO Optimize performance
        // domains and affiliate ID's we allow to track this way
        if ( isset( $this->settings['directAffiliates'] ) && $this->settings['directAffiliates'] != '' ) {
            $affdomains = explode( "\r\n", $this->settings['directAffiliates'] );
            $affs       = array();
            foreach ( $affdomains as $affdomain ) {
                $aff             = explode( "=", $affdomain );
                $aff[0]          = str_replace( array( '\n', '&nbsp;' ), '', $aff[0] );
                $affs[ $aff[0] ] = $aff[1];
            }
        } else {
            return;
        }

        // check if referred is present
        if ( ! isset( $_SERVER["HTTP_REFERER"] ) ) {
            return;
        }

        // if is, move to variable and parse
        $ref        = $_SERVER["HTTP_REFERER"];
        $urldetails = parse_url( $ref );

        // if parsed successfully, going to check if host is present
        if ( ! is_null( $urldetails ) && isset( $urldetails['host'] ) ) {
            // move extracted host to variable
            $host = $urldetails['host'];

            // check if host is not self referral, if is - exit;
            if ( $host === $_SERVER["HTTP_HOST"] ) {
                return;
            }

            foreach ( $affs as $domain => $aff ) {

                // checking if referring domain matches our allowed affiliate ID's
                if ( $domain == $host ) {

                    // load settings from affiliate plugin
                    $c = $this->c;

                    // construct tracking pixel's call
                    $url = "{$this->cakeTrackEndPoint}?a=$aff&c=$c&p=r&cp=jsr&s1=SUB_ID";

                    //<script type="text/javascript" src="http://sandbox.mn/?a=&c=53&p=r&cp=jsr&s1=SUB_ID"></script>
                    echo "\r\n<!-- Affiliate Tracking -->";
                    echo "\r\n" . '<script type="text/javascript" src="' . $url . '"></script>' . "\r\n";
                    echo "<!-- Affiliate Tracking -->\n";

                    return;
                }
            }
        }

        return;
    }

}