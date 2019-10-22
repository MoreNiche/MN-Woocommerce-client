<?php
/**
 * @author      Peter Mach <peter@kukiventures.com>
 * @category    Core
 * @package     WC\AffiliateTracking\Export
 * @copyright   Kukiventures Ltd 2016
 */

class WC_AffiliateTracking_Export
{
	/**
	 * Export slug ID
	 *
	 * @var string
	 */
	protected $slug_id = 'mn_affiliate_tracking_export';

	/**
	 * Writer
	 *
	 * @var WC_AffiliateTracking_Export_Writer_Interface
	 */
	protected $writer;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if ( $this->has_slug() ) {
			add_action( 'init', array( $this, 'add_slug_endpoint' ) );
			add_action( 'template_redirect', array( $this, 'get_csv_export' ) );
		}
	}

	/**
	 * Gets the writer
	 *
	 * @return WC_AffiliateTracking_Export_Writer_Interface
	 */
	public function get_writer()
	{
		if ( null == $this->writer ) {
			$this->writer = new WC_AffiliateTracking_Export_Writer_Csv();
		}

		return $this->writer;
	}

	/**
	 * Sets the writer
	 *
	 * @param WC_AffiliateTracking_Export_Writer_Interface $writer
	 *
	 * @return $this
	 */
	public function set_writer( WC_AffiliateTracking_Export_Writer_Interface $writer )
	{
		$this->writer = $writer;

		return $this;
	}

	/**
	 * Get plugin option
	 *
	 * @param $option_key
	 *
	 * @return mixed|false
	 */
	protected function get_option( $option_key )
	{
		$options = get_option( WC_AffiliateTracking_Admin::get_config( 'option_name' ), array() );

		return array_key_exists( $option_key, $options ) ? $options[ $option_key ] : false;
	}

	/**
	 * Checks for slug
	 *
	 * @return bool
	 */
	protected function has_slug()
	{
		return $this->get_option( 'slug_name' ) ? true : false;
	}

	/**
	 *  Adds slug endpoint for CSV export
	 */
	public function add_slug_endpoint()
	{
		add_rewrite_tag( '%' . $this->slug_id . '%', '([^&]+)' );
		add_rewrite_rule( $this->get_option( 'slug_name' ) . '/([^&]+)/?', 'index.php?' . $this->slug_id . '=$matches[1]', 'top' );
        flush_rewrite_rules();
	}

	/**
	 * Callback that gets the CSV
	 */
	public function get_csv_export()
	{
		global $wp_query;

		$export_token = $wp_query->get( $this->slug_id );

		if ( ! $export_token ) {
			return;
		}

		if ( $export_token <> $this->get_option( 'token' ) ) {
			$wp_query->set_404();
			status_header( 404 );
			return;
		}

		do_action( 'woocommerce_init' );
		$csv = $this->run();
		header( 'Content-Type: application/csv' );
		header( 'Content-Disposition: attachment; filename=data.csv' );
		header( 'Pragma: no-cache' );
		exit( $csv );
	}

	/**
	 * Runs the export
	 *
	 * @todo Log write errors
	 * @param bool $save_to_file
	 *
	 * @return $this|string
	 */
	public function run( $save_to_file=false )
	{
		$writer     = $this->get_writer();
		$products   = $this->get_products();

		if ( $writer instanceof WC_AffiliateTracking_Export_Writer_Csv ) {
			if ( $save_to_file ) {
				$csv_name   = 'product-export.csv';
				$products   = $this->get_products();
				$file_path  = MN_AFFILIATE_TRACKING_DATA_PATH . DIRECTORY_SEPARATOR . $csv_name;

				try {
					$writer->set_data_path( $file_path )
					       ->delete()
					       ->save( $products );
				} catch ( Exception $e ) {
					// log write error
				}

				return MN_AFFILIATE_TRACKING_DATA_URL . '/' . $csv_name;
			} else {
				try {
					$return = $writer->save( $products );
				} catch ( Exception $e ) {
					// log write error
				}
			}
		}

		return $return ? $return : $this;
	}

	/**
	 * Gets the products for export
	 *
	 * @return array
	 */
	public function get_products()
	{
		$args = array(
			'post_type'      => 'product',
			'orderby'        => 'name',
			'order'          => 'ASC',
			'posts_per_page' => -1
		);

		$wp_query      = new WP_Query( $args );
		$wc_pf         = new WC_Product_Factory();
		$products      = array();
		$product_skus  = array();
		$product_names = array();

		if( $wp_query->have_posts() ) {
			foreach ( $wp_query->get_posts() as $post ) {

				$exclude = false;
				if( isset( $post->post_type ) && $post->post_type == 'product_variation' ) {
					if( $post->post_parent ) {
						if ( ! $wc_pf->get_product( $post->post_parent ) ) {
							continue;
						}
					}
				}

				$product_obj = $wc_pf->get_product( $post );
				$sku         = str_replace( '-', 'X', $product_obj->get_sku() );

				if ( '' == $product_obj->get_sku() ) {
					$exclude = true;
				}

				if ( in_array( $sku, $product_skus ) ) {
					$exclude = true;
				}

				if ( $exclude == false ) {

					$title = strip_tags( $product_obj->get_title() );
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_obj->id ), 'thumbnail' );

					$product         = array(
						'Brand Name'    => $this->get_option( 'brand_name' ),
						'Brand Key'     => $this->get_option( 'brand_key' ),
						'Product Code'  => $sku,
						'Product Name'  => $title,
						'Sku ID'        => $post->ID,
						'Sku Name'      => $title,
						'Sku Code'      => $sku,
						'Price'         => $product_obj->get_price(),
						'Product Image' => $image[0],
						'Upsale'        => 'false'
					);
					$products[]      = $product;
					$product_skus[]  = $sku;
					$product_names[] = $title;

					$upsell_sku                     = $sku . 'UP';
					$upsell_name                    = $title . ' Upsell';
					$upsell_product                 = $product;
					$upsell_product['Product Code'] = $upsell_product['Sku Code'] = $upsell_sku;
					$upsell_product['Product Name'] = $upsell_product['Sku Name'] = $upsell_name;
					$upsell_product['Upsale']       = 'true';
					$products[]                     = $upsell_product;
					$product_skus[]                 = $upsell_sku;
					$product_names[]                = $upsell_name;
				}
			}
		}

		if ( ! $products ) {
			$products[] = array (
				'Brand Name'    => 'No Products Found!',
				'Brand Key'     => '',
				'Product Code'  => '',
				'Product Name'  => '',
				'Sku ID'        => '',
				'Sku Name'      => '',
				'Sku Code'      => '',
				'Price'         => '',
				'Product Image'  => '',
				'Upsale'  => '',
			);
		}

		wp_reset_query();

		return $products;
	}
}