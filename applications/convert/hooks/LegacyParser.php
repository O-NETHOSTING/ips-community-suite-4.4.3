//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class convert_hook_LegacyParser extends _HOOK_CLASS_
{
	/**
	 * Parse statically
	 *
	 * @param	string				$value				The value to parse
	 * @param	\IPS\Member|null	$member				The member posting. NULL will use currently logged in member.
	 * @param	bool				$allowHtml			Allow HTML
	 * @param	string				$attachClass		Key to use for attachments
	 * @param	int					$id1				ID1 to use for attachments
	 * @param	int					$id2				ID2 to use for attachments
	 * @param	int|NULL			$id3				ID3 to use for attachments
	 * @param	string				$itemClass			The *item* classname (e.g. IPS\forums\Topic)
	 * @return	string
	 * @see		__construct
	 */
	public static function parseStatic( $value, $member=NULL, $allowHtml=FALSE, $attachClass=null, $id1=0, $id2=0, $id3=NULL, $itemClass=NULL )
	{
		/* This content has previously been parsed, don't do it again
		 * -- This can occur with the rebuildNonContent background tasks as they cannot confirm if
		 * -- content was converted, or already present
		 */
		if( mb_stristr( $value, '<___base_url___>' ) OR mb_stristr( $value, 'data-ips' ) )
		{
			return $value;
		}

		return parent::parseStatic( $value, $member, $allowHtml, $attachClass, $id1, $id2, $id3, $itemClass );
	}
}