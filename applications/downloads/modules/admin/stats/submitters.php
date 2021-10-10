<?php
/**
 * @brief		Top Submitters
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		17 Dec 2013
 */

namespace IPS\downloads\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Top Submitters
 */
class _submitters extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Number of results per page
	 */
	const PER_PAGE = 25;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'submitters_manage' );
		parent::execute();
	}

	/**
	 * Top Submitters
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$values = NULL;
		$where = array();
		
		if ( isset( \IPS\Request::i()->form ) )
		{
			$form = new \IPS\Helpers\Form( 'form', 'go' );
			$form->add( new \IPS\Helpers\Form\DateRange( 'stats_date_range' ), NULL, FALSE, array( 'start' => array( 'max' => new \IPS\DateTime() ), 'end' => array( 'max' => new \IPS\DateTime() ) ) );
			
			if ( $values = $form->values() )
			{
				if ( $values['stats_date_range']['start'] )
				{
					$where[] = array( 'file_submitted>?', $values['stats_date_range']['start']->getTimestamp() );
				}
				if ( $values['stats_date_range']['end'] )
				{
					$where[] = array( 'file_submitted<?', $values['stats_date_range']['end']->getTimestamp() );
				}
			}
			else
			{
				\IPS\Output::i()->output = $form;
				return;
			}
		}
		
		$page = isset( \IPS\Request::i()->page ) ? \intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$select = \IPS\Db::i()->select( 'file_submitter, count(*) as files', 'downloads_files', $where, 'files DESC', array( ( $page - 1 ) * static::PER_PAGE, static::PER_PAGE ), 'file_submitter', NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )->join( 'core_members', 'core_members.member_id=downloads_files.file_submitter' );
		$mids = array();
		
		foreach( $select as $row )
		{
			$mids[] = $row['file_submitter'];
		}
		
		$members = array();
		
		if ( \count( $mids ) )
		{
			$members = iterator_to_array( \IPS\Db::i()->select( '*', 'core_members', array( \IPS\Db::i()->in( 'member_id', $mids ) ) )->setKeyField('member_id') );
		}
		
		$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			\IPS\Http\Url::internal( 'app=downloads&module=stats&controller=submitters' )->setQueryString( $values ),
			ceil( $select->count( TRUE ) / static::PER_PAGE ),
			$page,
			static::PER_PAGE,
			FALSE
		);
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'stats_date_range',
				'icon'		=> 'calendar',
				'link'		=> \IPS\Http\Url::internal( 'app=downloads&module=stats&controller=submitters&form=1' )->setQueryString( $values ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('stats_date_range') )
			)
		);
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'stats_include_hidden_content' ), 'info' );
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate('stats')->submittersTable( $select, $pagination, $members );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__downloads_stats_submitters');
	}
}