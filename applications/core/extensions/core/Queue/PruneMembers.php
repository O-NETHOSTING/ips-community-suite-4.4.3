<?php
/**
 * @brief		Background Task: Prune members
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 May 2016
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Prune members
 */
class _PruneMembers
{

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$select	= \IPS\Db::i()->select( 'core_members.*', 'core_members', $data['where'], 'member_id ASC', array(), NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )
			->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' )
			->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id')
			->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
			->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', \IPS\Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );

        $data['originalCount'] = $select->count( TRUE );

		if( $data['originalCount'] == 0 )
		{
			return null;
		}

		$data['skip_ids'] = array();

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$perCycle = \IPS\REBUILD_SLOW;

		/* Skip accounts that we couldn't remove previously */
		if( \count( $data['skip_ids'] ) )
		{
			$data['where'] = array_merge( $data['where'], array( \IPS\Db::i()->in( 'member_id', $data['skip_ids'], TRUE ) ) );
		}

		$select	= \IPS\Db::i()->select( 'core_members.*', 'core_members', $data['where'], 'member_id ASC', array( 0, $perCycle ), NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )
			->join( 'core_pfields_content', 'core_members.member_id=core_pfields_content.member_id' )
			->join( array( 'core_validating', 'v' ), 'v.member_id=core_members.member_id')
			->join( array( 'core_admin_permission_rows', 'm' ), "m.row_id=core_members.member_id AND m.row_id_type='member'" )
			->join( array( 'core_admin_permission_rows', 'g' ), array( 'g.row_id', \IPS\Db::i()->select( 'row_id', array( 'core_admin_permission_rows', 'sub' ), array( "((sub.row_id=core_members.member_group_id OR FIND_IN_SET( sub.row_id, core_members.mgroup_others ) ) AND sub.row_id_type='group') AND g.row_id_type='group'" ), NULL, array( 0, 1 ) ) ) );

        $total	= $select->count( TRUE );

		if ( !$total )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		foreach( $select AS $row )
		{
			try
			{
				$member = \IPS\Member::constructFromData( $row );

				if ( $member->member_id == \IPS\Member::loggedIn()->member_id OR ( $member->isAdmin() AND !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) ) )
				{
					throw new \OutOfBoundsException;
				}

				$member->delete();
			}
			catch( \Exception $e )
			{
				$data['skip_ids'][] = $row['member_id'];
			}

			$offset++;
		}

		return $offset;
	}

	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$text = \IPS\Member::loggedIn()->language()->addToStack('pruning_members', FALSE, array() );

		return array( 'text' => $text, 'complete' => $data['originalCount'] ? ( round( 100 / $data['originalCount'] * $offset, 2 ) ) : 100 );
	}
}