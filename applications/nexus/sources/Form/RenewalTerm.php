<?php
/**
 * @brief		Renewal Term input class for Form Builder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		25 Mar 2014
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Renewal Term input class for Form Builder
 */
class _RenewalTerm extends \IPS\Helpers\Form\FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'customer'		=> \IPS\nexus\Customer,	// Customer this is for (sets appropriate default currency)
	 		'currency'		=> 'USD',				// Alternatively to specifying customer, can manually specify currency (defaults to NULL)
	 		'allCurrencies'	=> FALSE,				// If TRUE, will ask for a price in all currencies (defaults to FALSE)
	 		'addToBase'		=> FALSE,				// If TRUE, a checkbox will be added asking if the price should be added to the base price
	 		'lockTerm'		=> FALSE,				// If TRUE, only the price (not the term) will be editable
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'customer'		=> NULL,
		'currency'		=> NULL,
		'allCurrencies'	=> FALSE,
		'addToBase'		=> NULL,
		'lockTerm'		=> FALSE,
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$value = $this->value;
		if ( !$this->value )
		{
			if ( $this->options['customer'] )
			{
				$defaultCurrency = $this->options['customer']->defaultCurrency();
			}
			elseif ( $this->options['currency'] )
			{
				$defaultCurrency = $this->options['currency'];
			}
			else
			{
				$currencies = \IPS\nexus\Money::currencies();
				$defaultCurrency = array_shift( $currencies );
			}
			
			if ( $this->options['allCurrencies'] )
			{
				$costs = array();
				foreach ( \IPS\nexus\Money::currencies() as $currency )
				{
					$costs[ $currency ] = new \IPS\nexus\Money( 0, $currency );
				}
			}
			else
			{
				$costs = new \IPS\nexus\Money( 0, $defaultCurrency );
			}
			$value = new \IPS\nexus\Purchase\RenewalTerm( $costs, new \DateInterval( 'P0M' ) );
		}
		
		return \IPS\Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->renewalTerm( $this->name, $value, $this->options );
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue()
	{
		if ( \is_array( $this->value ) )
		{
			if ( !isset( $this->value['term'] ) or !$this->value['term'] or !isset( $this->value['unit'] ) or !$this->value['unit'] )
			{
				return NULL;
			}
			else
			{
				if ( $this->options['allCurrencies'] )
				{
					$costs = array();
					foreach ( \IPS\nexus\Money::currencies() as $currency )
					{
						if ( isset( $this->value[ 'amount_' . $currency ] ) )
						{
							$costs[ $currency ] = new \IPS\nexus\Money( $this->value[ 'amount_' . $currency ], $currency );
						}
						else
						{
							$costs[ $currency ] = 0;
						}
					}
				}
				else
				{
					if ( isset( $this->value['currency'] ) )
					{
						$currency = $this->value['currency'];
					}
					else
					{
						$currencies = \IPS\nexus\Money::currencies();
						$currency = array_shift( $currencies );
					}
					$costs = new \IPS\nexus\Money( $this->value['amount'], $currency );
				}

				/* Perform some sanity checks */
				if ( $this->value['term'] < 1 )
				{
					$this->value = new \IPS\nexus\Purchase\RenewalTerm( $costs, new \DateInterval( 'P' . 1 . mb_strtoupper( $this->value['unit'] ) ), NULL, $this->options['addToBase'] ? isset( $this->value['add'] ) : FALSE );
					throw new \LengthException( \IPS\Member::loggedIn()->language()->addToStack('form_number_min', FALSE, array( 'sprintf' => array( 0 ) ) ) );
				}

				if ( !\in_array( $this->value['unit'], array( 'd', 'm', 'y' ) ) )
				{
					$this->value = new \IPS\nexus\Purchase\RenewalTerm( $costs, new \DateInterval( 'P' . $this->value['term'] . 'D' ), NULL, $this->options['addToBase'] ? isset( $this->value['add'] ) : FALSE );
					throw new \OutOfRangeException( 'form_bad_value' );
				}
				
				return new \IPS\nexus\Purchase\RenewalTerm( $costs, new \DateInterval( 'P' . $this->value['term'] . mb_strtoupper( $this->value['unit'] ) ), NULL, $this->options['addToBase'] ? isset( $this->value['add'] ) : FALSE );
			}
		}

		return $this->value;
	}

	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		if( !parent::validate() )
		{
			return FALSE;
		}

		if( !$this->value instanceof \IPS\nexus\Purchase\RenewalTerm )
		{
			return TRUE;
		}

		$cost = $this->value->cost;

		if( !\is_array( $cost ) )
		{
			$cost = array( $cost );
		}

		foreach( $cost as $money )
		{
			if( !$money->amount->isGreaterThanZero() )
			{
				throw new \LengthException( \IPS\Member::loggedIn()->language()->addToStack('form_number_min', FALSE, array( 'sprintf' => array( '0' ) ) ) );
			}
		}
	}
}