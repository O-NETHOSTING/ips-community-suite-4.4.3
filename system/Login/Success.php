<?php
/**
 * @brief		Successful Login Result
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Successful Login Result
 */
class _Success 
{
	/**
	 * @brief	The member who has successfully logged in
	 */
	public $member;
	
	/**
	 * @brief	The device
	 */
	public $device;
	
	/**
	 * @brief	The handler that processed the login
	 */
	public $handler;
	
	/**
	 * @brief	If the "remember me" box was checked
	 */
	public $rememberMe = TRUE;
	
	/**
	 * @brief	If the "sign in anonymously" box was checked
	 */
	public $anonymous = FALSE;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Member			$member		The member who has successfully logged in
	 * @param	\IPS\Login\Handler	$handler	The handler that processed the login
	 * @param	bool				$rememberMe	If the "remember me" box was checked
	 * @param	bool				$anonymous	If the "sign in anonymously" box was checked
	 * @param	bool				$sendNewDeviceEmail	Should a new device email be sent.
	 * @return	void
	 */
	public function __construct( \IPS\Member $member, \IPS\Login\Handler $handler, $rememberMe = TRUE, $anonymous = FALSE, $sendNewDeviceEmail = TRUE )
	{
		$this->member = $member;
		$this->device = \IPS\Member\Device::loadOrCreate( $member, $sendNewDeviceEmail );
		$this->handler = $handler;
		$this->rememberMe = $rememberMe;
		$this->anonymous = ( $anonymous or $member->group['g_hide_online_list'] );
	}
	
	/**
	 * Get two-factor authentication form required to process login
	 *
	 * @param	string|NULL	$area	The area being accessed or NULL to automatically use AuthenticateFront(Known)
	 * @return	string|NULL
	 */
	public function mfa( $area = NULL )
	{
		if ( !$area )
		{
			$area = $this->device->known ? 'AuthenticateFrontKnown' : 'AuthenticateFront';
		}
		return \IPS\MFA\MFAHandler::accessToArea( 'core', $area, \IPS\Http\Url::internal( '' ), $this->member );
	}
	
	/**
	 * Process the login - set the session data, and send required cookies
	 *
	 * @return	void
	 */
	public function process()
	{
		/* Log in */
		\IPS\Session::i()->setMember( $this->member );
		if ( $this->anonymous )
		{
			if ( !\IPS\Settings::i()->disable_anonymous )
			{
				\IPS\Session::i()->setAnon();
				\IPS\Request::i()->setCookie( 'isAnon', 1, \IPS\DateTime::create()->add( new \DateInterval( 'P14D' ) ) );
			}
		}
		else
		{
			if ( isset( \IPS\Request::i()->cookie['isAnon'] ) AND \IPS\Request::i()->cookie['isAnon'] )
			{
				\IPS\Request::i()->setCookie( 'isAnon', 0, \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) ) );
			}
		}

		$this->member->last_visit	= $this->member->last_activity;
		$this->member->save();

		/* Log device */
		$this->device->anonymous = $this->anonymous and !\IPS\Settings::i()->disable_anonymous;
		$this->device->updateAfterAuthentication( $this->rememberMe, $this->handler );
		
		/* Remove any noCache cookies */
		\IPS\Request::i()->setCookie( 'noCache', 0, \IPS\DateTime::ts( time() - 86400 ) );
		
		/* Member sync */
		$this->member->memberSync( 'onLogin' );
		$this->member->profileSync();
	}
}