<?php
/**
 * @author      Peter Mach <peter@kukiventures.com>
 * @category    Core
 * @package     WC\AffiliateTracking\Export\Writer
 * @copyright   Kukiventures Ltd 2016
 */

class WC_AffiliateTracking_Export_Writer_Csv implements WC_AffiliateTracking_Export_Writer_Interface
{
	/**
	 * CSV handle
	 *
	 * @var Mixed|SplFileObject
	 */
	protected $handle;

	/**
	 * CSV file path
	 *
	 * @var
	 */
	protected $file_path;

	/**
	 * Gets the file path
	 *
	 * @return mixed
	 */
	public function get_file_path()
	{
		return $this->file_path;
	}

	/**
	 * Sets the file path
	 *
	 * @param mixed $file_path
	 *
	 * @return $this
	 */
	public function set_file_path( $file_path )
	{
		$this->file_path = $file_path;

		return $this;
	}

	/**
	 * Saves data CSV
	 *
	 * @param array $data Array of values with keys as headers
	 *
	 * @return string|WC_AffiliateTracking_Export_Writer_Csv
	 * @throws Exception
	 */
	public function save( array $data )
	{
		if ( $this->file_path ) {

			if ( ! dirname( $this->file_path ) ) {
				if ( mkdir( $this->file_path ) ) {
					throw new WC_AffiliateTracking_Export_Writer_Exception(
						'Could not create CSV save directory: ' . $this->file_path );
				}
			}

			$this->handle = file_exists( $this->file_path )
				? new SplFileObject( $this->file_path, 'a' )
				: new SplFileObject( $this->file_path, 'w' );
		} else {
			$this->handle = fopen( 'php://output', 'w' );
			ob_start();
		}

		if ( ! $this->handle ) {
			throw new WC_AffiliateTracking_Export_Writer_Exception( 'Could not get CSV handle' );
		}

		$count      = 0;

		foreach ( $data as $data_row ) {
			if ( is_array( $data_row ) ) {
				$count++;

				if ( $count == 1 ) {
					$headers    = array();
					foreach ( array_keys( $data_row ) as $header ) {
						$headers[]  = ucfirst( str_replace( '-', ' ', $header ) );
					}
					$this->add( $headers );
				}

				$values = array_values( $data_row );
				$this->add( $values );
			}
		}

		return $this->file_path ? $this : ob_get_clean();
	}

	/**
	 * Adds a line to the CSV
	 *
	 * @param array $data_row
	 *
	 * @return
	 */
	public function add( array $values )
	{
		$handle     = $this->handle;

		if ( $handle instanceof SplFileObject ) {
			$handle->fputcsv( $values );
		} else {
			fputcsv( $handle, $values );
		}

		return $this;
	}

	/**
	 * Deletes the CSV
	 *
	 * @return $this
	 */
	public function delete()
	{
		if ( $this->handle instanceof SplFileObject ) {
			if ( file_exists( $this->file_path ) ) {
				unlink( $this->file_path );
			}
		}

		return $this;
	}

}