<?php
/**
 * @brief		Community Enhancement: Google Maps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		19 Apr 2013
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement: Google Maps
 */
class _GoogleMaps
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public $ips	= FALSE;

	/**
	 * @brief	Enhancement is enabled?
	 */
	public $enabled	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public $icon	= "google_maps.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( \IPS\Settings::i()->google_maps_api_key and ( \IPS\Settings::i()->googlemaps or \IPS\Settings::i()->googleplacesautocomplete ) );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit()
	{
		$validation = function( $val ) {
			if ( $val and !\IPS\Request::i()->google_maps_api_key )
			{
				throw new \DomainException('google_maps_api_key_req');
			}
		};
		
		$form = new \IPS\Helpers\Form;		
		$form->add( new \IPS\Helpers\Form\Text( 'google_maps_api_key', \IPS\Settings::i()->google_maps_api_key, FALSE, array(), function( $val ) {
			if ( $val )
			{			
				/* Check Geocoding API */
				try
				{
					$response = \IPS\Http\Url::external( "https://maps.googleapis.com/maps/api/geocode/json" )->setQueryString( array(
						'latlng'	=> '40.714224,-73.961452',
						'sensor'	=> 'false',
						'key'		=> $val
					) )->request()->get()->decodeJson();
				}
				catch ( \Exception $e )
				{
					throw new \DomainException('google_maps_api_error');
				}
				if ( !isset( $response['status'] ) or $response['status'] !== 'OK' )
				{
					throw new \DomainException( ( isset( $response['error_message'] ) ) ? $response['error_message'] : 'google_maps_api_key_invalid' );
				}
				
				/* Check Static Maps API */
				try
				{
					$response = \IPS\Http\Url::external( 'https://maps.googleapis.com/maps/api/staticmap' )->setQueryString( array(
						'center'	=> '40.714224,-73.961452',
						'zoom'		=> NULL,
						'size'		=> "100x100",
						'sensor'	=> 'false',
						'markers'	=> '40.714224,-73.961452',
						'key'		=> $val,
					) )->request()->get();
				}
				catch ( \Exception $e )
				{
					throw new \DomainException('google_maps_api_error');
				}
				if ( $response->httpResponseCode != 200 )
				{
					throw new \DomainException('google_maps_api_key_invalid');
				}
			}
		} ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'googlemaps', \IPS\Settings::i()->googlemaps, FALSE, array(), $validation ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'googleplacesautocomplete', \IPS\Settings::i()->googleplacesautocomplete, FALSE, array(), $validation ) );

		if ( $values = $form->values() )
		{
			if( $values['googlemaps'] > 0 )
			{
				$values['mapbox'] = 0;
			}

			$form->saveAsSettings( $values );
			\IPS\Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_GoogleMaps' => TRUE ) );
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
		}
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> \IPS\Http\Url::ips( 'docs/googlemaps' ),
				'target'	=> '_blank'
			),
		);
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GoogleMaps', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( $enabled )
	{
		/* If we're disabling, just disable */
		if( !$enabled )
		{
			\IPS\Settings::i()->changeValues( array( 'googlemaps' => 0, 'googleplacesautocomplete' => 0 ) );
		}

		/* Otherwise if we already have an API key, just toggle on */
		if( $enabled && \IPS\Settings::i()->google_maps_api_key )
		{
			\IPS\Settings::i()->changeValues( array( 'googlemaps' => 1, 'googleplacesautocomplete' => 1, 'mapbox' => 0 ) );
		}
		else
		{
			/* Otherwise we need to let them enter an API key before we can enable.  Throwing an exception causes you to be redirected to the settings page. */
			throw new \DomainException;
		}
	}
}