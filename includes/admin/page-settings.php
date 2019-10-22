<?php
/**
 * @author      Peter Mach <peter@kukiventures.com>
 * @category    Admin
 * @package     WC\AffiliateTracking\Admin
 * @copyright   Kukiventures Ltd 2016
 */


class WC_AffiliateTracking_Admin
{
	/**
	 * Stores the option values used in callbacks
	 */
	private $options;

	/**
	 * Admin page config
	 *
	 * @var array
	 */
	private static $config = array(
		'page_title'    => 'MoreNiche Affiliate Tracking Product Export',
		'menu_title'    => 'MN Affiliate Tracking Product Export',
		'menu_slug'     => 'moreniche-affiliate-tracking',
		'option_name'   => 'mn_affiliate_tracking_export'
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Get admin config
	 *
	 * @param $config_option
	 *
	 * @return mixed
	 */
	static public function get_config( $config_option )
	{
		return self::$config[$config_option];
	}

	/**
	 * Add options page
	 *
	 * Page will appear under "Settings"
	 */
	public function add_plugin_page()
	{
		add_options_page(
			self::$config['page_title'],
			self::$config['menu_title'],
			'manage_options',
			self::$config['menu_slug'],
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page()
	{
		$this->options = get_option( self::$config[ 'option_name' ] );
		require_once MN_AFFILIATE_TRACKING_ROOT
		             . 'view' . DIRECTORY_SEPARATOR
		             . 'admin' . DIRECTORY_SEPARATOR
		             . 'settings.php';
	}

	/**
	 * Registers and adds settings
	 */
	public function page_init()
	{
		register_setting(
			self::$config['option_name'],
			self::$config['option_name'],
			array( $this, 'sanitise' )
		);

		add_settings_section(
			'default',
			null,
			null,
			self::$config['menu_slug']
		);

		add_settings_section(
			'export_settings',
			'Export Settings',
			array( $this, 'print_export_section_info' ),
			self::$config['menu_slug']
		);

		add_settings_field(
			'slug_name',
			'Slug Name',
			array( $this, 'settings_slug_name_field' ),
			self::$config['menu_slug']
		);

		add_settings_field(
			'token',
			'Token',
			array( $this, 'settings_token_field' ),
			self::$config['menu_slug']
		);

		add_settings_field(
			'brand_key',
			'Brand Key',
			array( $this, 'settings_brand_key_field' ),
			self::$config['menu_slug'],
			'export_settings'
		);

		add_settings_field(
			'brand_name',
			'Brand Name',
			array( $this, 'settings_brand_name_field' ),
			self::$config['menu_slug'],
			'export_settings'
		);
	}

	/**
	 * Sanitise settings
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitise( $input )
	{
		$new_input = array();

		if( isset( $input['slug_name'] ) )
			$new_input['slug_name'] = sanitize_text_field( htmlentities2( $input['slug_name'] ) );

		if( isset( $input['token'] ) )
			$new_input['token'] = sanitize_text_field( $input['token'] );

		if( isset( $input['brand_key'] ) )
			$new_input['brand_key'] = sanitize_text_field( $input['brand_key'] );

		if( isset( $input['brand_name'] ) )
			$new_input['brand_name'] = sanitize_text_field( $input['brand_name'] );

		return $new_input;
	}

	/**
	 * Prints the export section title
	 */
	public function print_export_section_info()
	{
		print 'Default settings for export data.';
	}

	/**
	 * Prints the export_route field
	 */
	public function settings_slug_name_field()
	{
		printf(
			'<input type="text" id="slug_name" name="%s[slug_name]" class="regular-text" value="%s" />',
			self::$config['option_name'], isset( $this->options['slug_name'] ) ? esc_attr( $this->options['slug_name']) : ''
		);
	}

	/**
	 * Prints the token field
	 */
	public function settings_token_field()
	{
		$token_value    = isset( $this->options['token'] ) ? esc_attr( $this->options['token']) : '';
		$export_url     = get_site_url() . '/'
		                  . ( isset( $this->options['slug_name'] ) ? esc_attr( $this->options['slug_name']) : '{slug_name}' )
		                  . '/' . ( $token_value ? $token_value : '{token}' );

		printf(
			'<input type="text" id="token" name="%s[token]" class="regular-text" value="%s" />',
			self::$config['option_name'], $token_value
		);

		print '
		<tr>
			<th scope="row">Export URL</th>
			<td> ' . $export_url . '</td>
		</tr>';
	}

	/**
	 * Prints brand key field
	 */
	public function settings_brand_key_field()
	{
		printf(
			'<input type="text" id="brand_key" name="%s[brand_key]" class="regular-text" value="%s" />',
			self::$config['option_name'], isset( $this->options['brand_key'] ) ? esc_attr( $this->options['brand_key']) : ''
		);
	}

	/**
	 * Prints the brand name field
	 */
	public function settings_brand_name_field()
	{
		printf(
			'<input type="text" id="brand_name" name="%s[brand_name]" class="regular-text" value="%s" />',
			self::$config['option_name'], isset( $this->options['brand_name'] ) ? esc_attr( $this->options['brand_name']) : ''
		);
	}
}