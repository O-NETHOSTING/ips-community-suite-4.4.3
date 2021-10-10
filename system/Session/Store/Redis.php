<?php
/**
 * @brief		Redis Session Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		6 September 2017
 */

namespace IPS\Session\Store;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Redis Session Handler
 */
class _Redis extends \IPS\Session\Store
{
	/**
	 * @brief	Default expiration for keys in seconds
	 */
	static protected $ttl = 1800; #30 mins
	
	/**
	 * Load the session from the storage engine 
	 *
	 * @param	string	$sessionId	Session ID
	 * @return	array
	 */
	public function loadSession( $sessionId )
	{
		if ( $result = \IPS\Redis::i()->hGetAll( $this->_key( 'session_id_' . md5( $sessionId . \IPS\Settings::i()->sql_pass ) ) ) )
		{  
			return \IPS\Redis::i()->decode( $result['data'] );
		}
		
		return NULL;
	}
	
	/**
	 * Update the session storage engine
	 *
	 * @param	array	$data	Session data to store
	 * @return void
	 */
	public function updateSession( $data )
	{
		/* Groups are loaded into memory so this does not cause a query */
		$group = \IPS\Member\Group::load( $data['member_group'] );

		/* Update the specific session */
		\IPS\Redis::i()->delete( $this->_key( 'session_id_' . md5( $data['id'] . \IPS\Settings::i()->sql_pass ) ) );
		\IPS\Redis::i()->hMSet( $this->_key( 'session_id_' . md5( $data['id'] . \IPS\Settings::i()->sql_pass ) ), array(
			'member_id'		=> $data['member_id'],
			'member_name'	=> $data['member_name'],
			'seo_name'		=> $data['seo_name'],
			'member_group'	=> $data['member_group'],
			'login_type'	=> $data['login_type'],
			'in_editor'	    => $data['in_editor'],
			'data' 			=> \IPS\Redis::i()->encode( $data )
		), static::$ttl );
		
		/* Update the list of sessions for the online list [ microtime => sessionID ] */
		\IPS\Redis::i()->zAdd( $this->_key( 'session_map' ), time(), 'session_id_' . md5( $data['id'] . \IPS\Settings::i()->sql_pass ), static::$ttl );
		
		/* Update users list */
		if ( $data['uagent_type'] == 'search' )
		{
			/* Make a unique row based on IP and user-agent to prevent multiple rows for each spider */
			\IPS\Redis::i()->zAdd( $this->_key( 'session_online_spiders' ), time(), md5( $data['ip_address'] . $data['browser'] ), static::$ttl );
		}
		else if ( $data['member_id'] )
		{
			\IPS\Redis::i()->zAdd( $this->_key( 'session_online_users' ), time(), $data['member_id'] . '__' . 'session_id_' . md5( $data['id'] . \IPS\Settings::i()->sql_pass ), static::$ttl );
		}
		else
		{
			/* A guest may have one ip address but multiple devices, but we don't really need to track that */
			\IPS\Redis::i()->zAdd( $this->_key( 'session_online_guests' ), time(), md5( $data['ip_address'] ), static::$ttl );
		}
				
		/* Delete old items */
		if ( ! \IPS\Redis::i()->get( $this->_key( 'session_cleanup' ) ) )
		{
			/* Do a little clean up */
			\IPS\Redis::i()->zRemRangeByScore( $this->_key( 'session_map' ), 0, time() - static::$ttl );
			\IPS\Redis::i()->zRemRangeByScore( $this->_key( 'session_online_spiders' ), 0, time() - static::$ttl );
			\IPS\Redis::i()->zRemRangeByScore( $this->_key( 'session_online_users' ), 0, time() - static::$ttl );
			\IPS\Redis::i()->zRemRangeByScore( $this->_key( 'session_online_guests' ), 0, time() - static::$ttl );
			
			/* And do it again in 3ish mins */
			\IPS\Redis::i()->setEx( $this->_key( 'session_cleanup' ), 180, time() );
		}
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param	string	$sessionId	Session ID
	 * @return	void
	 */
	public function deleteSession( $sessionId )
	{
		$data = $this->loadSession( $sessionId );
		\IPS\Redis::i()->delete( $this->_key( 'session_id_' . md5( $sessionId . \IPS\Settings::i()->sql_pass ) ) );
		\IPS\Redis::i()->zDelete( $this->_key( 'session_map' ), 'session_id_' . md5( $sessionId . \IPS\Settings::i()->sql_pass ) );
		\IPS\Redis::i()->zDelete( $this->_key( 'session_online_spiders' ), md5( $data['ip_address'] . $data['browser'] ) );
		\IPS\Redis::i()->zDelete( $this->_key( 'session_online_users' ), $data['member_id'] . '__' . 'session_id_' . md5( $data['id'] . \IPS\Settings::i()->sql_pass ) );
		\IPS\Redis::i()->zDelete( $this->_key( 'session_online_guests' ), md5( $data['ip_address'] ) );
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param	int			$memberId	You can probably figure this out right?
	 * @param	string|NULL	$userAgent	User Agent [optional]
	 * @param	array|NULL	$keepSessionIds	Array of session ids to keep [optional]
	 * @return	void
	 */
	public function deleteByMember( $memberId, $userAgent=NULL, $keepSessionIds=NULL )
	{
		if ( ! \is_array( $keepSessionIds ) )
		{
			$keepSessionIds = array( $keepSessionIds );
		}
		
		$sessionMap = \IPS\Redis::i()->zRange( 'session_map', 0, -1 );

		foreach( $sessionMap as $index => $redisKey )
		{
			$session = \IPS\Redis::i()->hMGet( $redisKey, array( 'data' ) );

			$sessionMap[ $index ] = \IPS\Redis::i()->decode( $session['data'] );
		}

		foreach( $sessionMap as $session )
		{
			$delete = false;
			if ( $session['member_id'] == $memberId )
			{
				$delete = true;
			}
			
			if ( $userAgent and $userAgent != $session['browser'] )
			{
				$delete = false;
			}
			
			if ( $keepSessionIds and \in_array( $session['id'], $keepSessionIds ) )
			{
				$delete = false;
			}
			
			if ( $delete )
			{
				$this->deleteSession( $session['id'] );
			}
		}
	}
	
	/**
	 * Fetch all active session keys
	 *
	 * @return	array of session IDs
	 */
	public function getSessionIds()
	{
		if ( $result = \IPS\Redis::i()->zRangeByScore( 'session_map', '-inf', '+inf', array( 'withscores' => false ) ) )
		{
			return $result;
		}
		
		return array();
	}
	
	/**
	 * Delete from the session engine
	 *
	 * @param	int		$memberId	You can probably figure this out right?
	 * @return	array|FALSE
	 */
	public function getLatestMemberSession( $memberId )
	{
		$redis = \IPS\Redis::i()->zRevRangeByScore( 'session_online_users', '+inf', '-inf', array('withscores' => FALSE, 'alpha' => TRUE ) );

		foreach( $redis as $data )
		{
			list( $id, $sessionKey ) = explode( '__', $data );
			
			if ( $id == $memberId )
			{
				if ( $result = \IPS\Redis::i()->hGetAll( $this->_key( $sessionKey ) ) )
				{  
					return \IPS\Redis::i()->decode( $result['data'] );
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Clear sessions - abstracted so it can be called externally without initiating a session
	 *
	 * @param	int		$timeout	Sessions older than the number of seconds provided will be deleted
	 * @return void
	 */
	public static function clearSessions( $timeout )
	{
		/* Remove the public facing items. This is only called by PHP's session gc so individual sessions do not need removing as they are cleaned by Redis' TTL */
		\IPS\Redis::i()->zRemRangeByScore( 'session_map', 0, time() - $timeout );
		\IPS\Redis::i()->zRemRangeByScore( 'session_online_spiders', 0, time() - $timeout );
		\IPS\Redis::i()->zRemRangeByScore( 'session_online_users', 0, time() - $timeout );
		\IPS\Redis::i()->zRemRangeByScore( 'session_online_guests', 0, time() - $timeout );
	}
	
	/**
	 * Returns a key to be stored with Redis
	 *
	 * @param	string	$key		Key suffix
	 * @return	string
	 */
	protected function _key( $key )
	{
		return $key;
	}

	/**
	 * Fetch all online users (but not spiders)
	 *
	 * @param	int			$flags				Bitwise flags
	 * @param	string		$sort				Sort direction
	 * @param	array|NULL	$limit				Limit [ offset, limit ]
	 * @param	int			$memberGroup		Limit by a specific member group ID
	 * @param	boolean		$showAnonymous		Show anonymously logged in peoples?	
	 * @return array
	 */
	public function getOnlineUsers( $flags=0, $sort='desc', $limit=NULL, $memberGroup=NULL, $showAnonymous=FALSE )
	{
		$results = \IPS\Redis::i()->lRange('session_onlinelist', 0, -1 );
		
		if ( ! $results )
		{
			$options = array(
				'sort'  => $sort === 'asc' ? 'asc' : 'desc',
				'store' => \IPS\Redis::i()->prefix . 'session_onlinelist',
				'alpha' => true,
				'by'    => 'nosort ' . $sort === 'asc' ? 'asc' : 'desc',
				'ttl'   => 30, /* This ensures the stored file session_online only lasts for 30 seconds */
				'get'   => array(
					$this->_key( \IPS\Redis::i()->prefix ) . '*->member_id',
					$this->_key( \IPS\Redis::i()->prefix ) . '*->member_name',
					$this->_key( \IPS\Redis::i()->prefix ) . '*->seo_name',
					$this->_key( \IPS\Redis::i()->prefix ) . '*->member_group',
					$this->_key( \IPS\Redis::i()->prefix ) . '*->login_type',
					$this->_key( \IPS\Redis::i()->prefix ) . '*->data'
				)
			);
			
			\IPS\Redis::i()->sort( $this->_key( 'session_map' ), $options );
			
			$results = \IPS\Redis::i()->lRange('session_onlinelist', 0, -1 );
		}

		/* Sort returns a flat array, so [ 1, matt, matt, 4, 0, 2, Joe, joe, 3, 0 .. ] so we need to build that into an associative array we can work with */
		$return = array();
		$i = 0;
		
		while( $i < \count( $results ) )
		{
			$fields = array();
			$data = NULL;
			foreach( array( 'member_id', 'member_name', 'seo_name', 'member_group', 'login_type', 'data' ) as $field )
			{
				if ( $field === 'data' )
				{
					$data = \IPS\Redis::i()->decode( $results[ $i++ ] );
				}
				else
				{
					$fields[ $field ] = $results[ $i++ ];
				}
			}
			
			if ( \is_array( $data ) AND \count( $data ) )
			{
				/* Have we already fetched this member? */
				if ( $fields['member_id'] and isset( $return[ $fields['member_id'] ] ) )
				{
					continue;
				}
				
				/* Cut off is 30 mins */
				if ( $data['running_time'] < ( time() - ( 30 * 60 ) ) )
				{
					continue;
				}

				/* Ignore spiders */
				if ( ! $fields['member_id'] and ! ( $data['login_type'] == \IPS\Session\Front::LOGIN_TYPE_GUEST or $data['login_type'] == \IPS\Session\Front::LOGIN_TYPE_INCOMPLETE ) )
				{
					continue;
				}

				$return[ $fields['member_id'] ? $fields['member_id'] : $data['ip_address'] ] = array_merge( $data, $fields );
			}
		}

		if ( $flags )
		{
			$members = array();
			foreach( $return as $id => $data )
			{
				/* No members */
				if ( ! ( $flags & static::ONLINE_MEMBERS ) )
				{
					if ( $data['member_id'] )
					{
						continue;
					}
				}
				
				if ( ! ( $flags & static::ONLINE_GUESTS ) )
				{
					if ( ! $data['member_id'] )
					{
						continue;
					}
				}
				
				if ( $memberGroup and $data['member_group'] != $memberGroup )
				{
					continue;
				}
				
				if ( ! $showAnonymous and $data['login_type'] == \IPS\Session\Front::LOGIN_TYPE_ANONYMOUS )
				{
					continue;
				}
				
				$members[ $id ] = $data;
			}
			
			/* Count only? */
			if ( $flags & static::ONLINE_COUNT_ONLY )
			{
				return \count( $members );
			}
			
			/* Hooray for PHP 7 */
			usort($members, function ($m1, $m2 )
			{
				return $m2['running_time'] <=> $m1['running_time'];
			} );
			
			if ( $limit )
			{
				return \array_slice( $members, $limit[0], $limit[1], TRUE );
			}
			
			return $members;
		}
		
		return $return;
	}
	
	/**
	 * Fetch all members active at a specific location
	 *
	 * @param	string	$app		Application directory (core, forums, etc)
	 * @param	string	$module		Module
	 * @param	string	$controller Controller
	 * @param	int		$id			Current item ID (empty if none)
	 * @param	string	$url		Current viewing URL
	 * @return array
	 */
	public function getOnlineMembersByLocation( $app, $module, $controller, $id, $url )
	{
		$members = array();
		
		foreach( $this->getOnlineUsers( static::ONLINE_MEMBERS, 'desc' ) as $member )
		{
			if ( $member['current_appcomponent'] == $app and $member['current_module'] == $module and $member['current_controller'] = $controller and $member['current_id'] = $id )
			{
				if ( $url and mb_stristr( $member['location_url'], $url ) )
				{
					$members[ $member['member_id'] ] = array(
						'member_id'		=> $member['member_id'],
						'member_name'	=> $member['member_name'],
						'seo_name'		=> $member['seo_name'],
						'member_group'	=> $member['member_group'],
						'login_type'	=> $member['login_type'],
						'in_editor'		=> $member['in_editor']
					);
				}
			}
		}
			
		return $members;
	}
}