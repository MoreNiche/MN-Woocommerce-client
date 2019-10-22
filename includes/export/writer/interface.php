<?php
/**
 * @author      Peter Mach <peter@kukiventures.com>
 * @category    Core
 * @package     WC\AffiliateTracking\Export\Writer
 * @copyright   Kukiventures Ltd 2016
 */

interface WC_AffiliateTracking_Export_Writer_Interface
{
	/**
	 * Adds a data row
	 *
	 * @param array $data_row
	 *
	 * @return mixed
	 */
	public function add( array $data_row );

	/**
	 * Saves data
	 *
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function save( array $data );

	/**
	 * Deletes data
	 *
	 * @return mixed
	 */
	public function delete();
}