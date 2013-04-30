<?php

class BoatShow extends UBBaseModel
{

	const DEALER_INFO_LARGE_IMAGE_X = 407;
	const DEALER_INFO_LARGE_IMAGE_Y = 271;
	const DEALER_INFO_SMALL_IMAGE_X = 160;
	const DEALER_INFO_SMALL_IMAGE_Y = 160;
	const CHAT_MAX_POSTS_PER_TIMEPERIOD = 50;
	const CHAT_TIMPERIOD_LENGTH = 180; // seconds ( 180 is 3 minutes )
	const TIER_TWO_IMAGE_SIZE_X = 100;
	const TIER_TWO_IMAGE_SIZE_Y = 150;
	const TIER_TWO_IMAGE_STORAGE_DIR = '/images/tier_two_sponsors';

	public static function sendChatMessage($iBoatShowId = false, $iAdId = false, $iUserId = false, $sMessage = false)
	{
		$sNow = date('Y-m-d H:i:s');
		if(false === $iBoatShowId || !isset($iBoatShowId) || trim($iBoatShowId) == '')
			return array('response_code' => '1', 'response_message' => 'Boat Show Id missing.');
		if(false === $iAdId || !isset($iAdId) || trim($iAdId) == '')
			return array('response_code' => '2', 'response_message' => 'Ad Id missing.');
		if(false === $iUserId || !isset($iUserId) || trim($iUserId) == '' || $iUserId == 0)
			return array('response_code' => '3', 'response_message' => 'You must be logged in to post.');
		if(false === $sMessage || !isset($sMessage) || trim($sMessage) == '')
			return array('response_code' => '4', 'response_message' => 'Empty message.');
		$table = new boat_show_chat();
		$select = $table->select()
			->from($table, array('count(boat_show_chat_id) as total'))
			->where('boat_show_chat.added > date_sub(\'' . $sNow . '\', INTERVAL ' . self::CHAT_TIMPERIOD_LENGTH . ' SECOND)')
			->where('boat_show_chat.boat_show_id = ?', $iBoatShowId)
			->where('boat_show_chat.ad_id = ?', $iAdId)
			->where('boat_show_chat.user_id = ?', $iUserId)
		;
		$theRow = $table->fetchRow($select);
		if($theRow->total == self::CHAT_MAX_POSTS_PER_TIMEPERIOD)
			return array('response_code' => '5', 'response_message' => 'Please limit yourself to ' . self::CHAT_MAX_POSTS_PER_TIMEPERIOD . ' messages every ' . (self::CHAT_TIMPERIOD_LENGTH/60) . ' minutes in each room.');
		unset($table);
		$table = new boat_show_chat();
		$aData = array(
			'user_id' => $iUserId,
			'boat_show_id' => $iBoatShowId,
			'ad_id' => $iAdId,
			'added' => date('Y-m-d H:i:s'),
			'message' => $sMessage,
		);
		$table->insert($aData);
		return array('response_code' => '0', 'response_message' => 'Looks good.');
	}

	public static function getRecentChatMessages($iBoatShowId = false, $iAdId = false, $iStartingBoatShowChatId = false)
	{
		$table = new boat_show_chat();
		$select = $table->select()
			->setIntegrityCheck(false)
			->from($table, array('boat_show_chat.added', 'boat_show_chat.user_id as boat_show_chat_user_id', 'boat_show_chat.boat_show_chat_id', 'boat_show_chat.message'))
			->where('boat_show_chat.boat_show_id = ?', $iBoatShowId)
			->where('boat_show_chat.ad_id = ?', $iAdId)
			->from('ads', array('ads.user_id as ad_owner_user_id'))
			->where('boat_show_chat.ad_id = ads.ad_id')
			->from('users', array('users.firstname'))
			->where('boat_show_chat.user_id = users.user_id')
			->where('boat_show_chat.boat_show_chat_id > ?', $iStartingBoatShowChatId)
		;
		$theRows = $table->fetchAll($select);
		return($theRows);
	}

	public static function getChatRoomLog($iBoatShowId = false, $iAdId = false)
	{
		$table = new boat_show_chat();
		$select = $table->select()
			->setIntegrityCheck(false)
			->from($table, array('boat_show_chat.added', 'boat_show_chat.user_id as boat_show_chat_user_id', 'boat_show_chat.boat_show_chat_id', 'boat_show_chat.message'))
			->where('boat_show_chat.boat_show_id = ?', $iBoatShowId)
			->where('boat_show_chat.ad_id = ?', $iAdId)
			->from('ads', array('ads.user_id as ad_owner_user_id'))
			->where('boat_show_chat.ad_id = ads.ad_id')
			->from('users', array('users.firstname'))
			->where('boat_show_chat.user_id = users.user_id')
		;
		$theRows = $table->fetchAll($select);
		return($theRows);
	}

	public static function getAdImageBasePath($iUserId = false, $iAdId = false)
	{
		return ROOT_DIR . IMAGE_SERVER . AdImage::getImageDirectoryHash($iUserId) . '/' . $iUserId . '/' . $iAdId;
	}

	public static function getDPubImagePath($iUserId = false, $iAdId = false, $sImageName = 'dpub_image', $sPathType = 'relative') {
		return BoatShow::getDealerInfoImagePath($iUserId, $iAdId, $sImageName, $sPathType);
	}

	public static function getDealerInfoImagePath($iUserId = false, $iAdId = false, $sImageName = 'bsdi_large_image', $sPathType = 'relative') {
		if(false !== $iUserId && false !== $iAdId) {
			//make sure the base of the path we are after actually exists in the filesystem
			$sImageBasePath = ROOT_DIR . IMAGE_SERVER . AdImage::getImageDirectoryHash($iUserId) . '/' . $iUserId . '/' . $iAdId;
			if(!file_exists($sImageBasePath)) {
				mkdir($sImageBasePath, 0777, true);
			}
			switch($sPathType) {
				case 'relative' :
					return str_replace('www', '', IMAGE_SERVER) . AdImage::getImageDirectoryHash($iUserId) . '/' . $iUserId . '/' . $iAdId . '/' . $sImageName . '.jpg';
				break;
				case 'absolute' :
					return ROOT_DIR . IMAGE_SERVER . AdImage::getImageDirectoryHash($iUserId) . '/' . $iUserId . '/' . $iAdId . '/' . $sImageName . '.jpg';
				break;
			}
		}
		return false;
	}

	public static function getBoatShowDealerInfoIdByAdId($iAdId = null)
	{
		$table = new boat_show_dealer_info();
		$select = $table->select()
			->setIntegrityCheck(false)
			->from($table)
			->from('ads', 'ads.ad_id')
			->where('ads.ad_id = boat_show_dealer_info.ad_id')
			->where('ads.ad_id = ?', $iAdId)
		;
		$theRow = $table->fetchRow($select);
		if(isset($theRow->boat_show_dealer_info_id))
		{
			return $theRow->boat_show_dealer_info_id;
		}
		return null;
	}

	public static function getUserIdByAdId($iAdId = null)
	{
		$table = new ads();
		$select = $table->select()
			->where('ads.ad_id = ?', $iAdId)
		;
		$theRow = $table->fetchRow($select);
		if(isset($theRow->user_id))
		{
			return $theRow->user_id;
		}
		return null;
	}

	public static function getDealerInfo($iBoatShowDealerInfoId = null)
	{
//echo('iBoatShowDealerInfoId : ' . $iBoatShowDealerInfoId . '<br />');
		$table = new boat_show_dealer_info();
		if(null !== $iBoatShowDealerInfoId)
		{
			$select = $table->select()
				->from($table)
				->where('boat_show_dealer_info.boat_show_dealer_info_id = ?', $iBoatShowDealerInfoId)
			;
			$theRow = $table->fetchRow($select);
		}
		if(!isset($theRow->boat_show_dealer_info_id))
		{
			//echo('making new row<br />');
			$theRow = $table->createRow();
		} else {
			//echo('using existing row<br />');
			$select = $table->select()
				->from($table)
				->where('boat_show_dealer_info.boat_show_dealer_info_id = ?', $iBoatShowDealerInfoId)
			;
			$theRow = $table->fetchRow($select);
		}
//echo('<pre>' . print_r($theRow, true));
		return $theRow;
	}

	public static function addShow($aData = false)
	{
		if(false !== $aData)
		{
			$aData['admin_id'] = AdminUser::getCurrentUser()->user_id;
			$aData['start_datetime'] = date('Y-m-d H:i:s', $aData['start_timestamp']);
			$aData['end_datetime'] = date('Y-m-d H:i:s', $aData['end_timestamp']);
			unset($aData['start_timestamp']);
			unset($aData['end_timestamp']);
			$table = new boat_shows();
			$table->insert($aData);
		}
	}

	public static function getNextShows($limit = false)
	{
		$table = new boat_shows();
		$select = UBBaseModel::getListSelect($table);
		$select
			->where('start_datetime > now()')
			->where('status = 1')
			->order('start_datetime asc')
		;
		if(false !== $limit) {
			$select->limit($limit[0], $limit[1]);
		}
		$row = $table->fetchAll($select);
		return $row;
	}

	public static function isShowCurrentlyRunning($boat_show_id = null)
	{
		$table = new boat_shows();
		$select = UBBaseModel::getListSelect($table, array('count(*) as total'));
		$select
			->where('start_datetime < now()')
			->where('end_datetime > now()')
			->where('status = 1')
			->order('start_datetime asc')
		;
		if(null !== $boat_show_id)
		{
			$select->where('boat_show_id = ?', $boat_show_id);
		}
		$row = $table->fetchRow($select);
		return ($row->total > 0) ? true : false;
	}

	public static function getShowById($id)
	{
		$table = new boat_shows();
		$theRow = $table->get($id);
	}

	public static function getShows($fields = null, $arguments = null, $where = null)
	{
		$table  = new boat_shows();
		$select = UBBaseModel::getListSelect($table, $fields, $arguments, $where);
		$select->setIntegrityCheck(false);
		$select
			->where('end_datetime > now()')
		;
		return    UBBaseModel::getListOfRows($table, $select);
	}

	public static function getShowAds($fields = null, $arguments = null, $where = null)
	{
		$table  = new boat_show_ads();
		$select = UBBaseModel::getListSelect($table, $fields, $arguments, $where);
		$select->setIntegrityCheck(false);
		$select
			->from('boat_shows', 'boat_shows.boat_show_id')
			->where('boat_show_ads.boat_show_id = boat_shows.boat_show_id')
		;
//echo($select . '<br />');
		return    UBBaseModel::getListOfRows($table, $select);
	}

	public static function getShowCount($where = null)
	{
		$table  = new boat_shows();
		$select = UBBaseModel::getCountSelect($table, $where);
		return    UBBaseModel::getCount($table, $select);
	}

	public static function getUpcomingBoatShowAdsCount()
	{
		$table = new boat_show_ads();
		return $table->getUpcomingBoatShowAdsCount();
	}

	public static function delShow($id = null)
	{
		if(null !== $id)
		{
			$table = new boat_shows();
			$aData = array(
				'status' => '0',
			);
			$where = $table->getAdapter()->quoteInto('boat_show_id = ?', $id);
			$table->update($aData, $where);
		}
	}

	public static function delAd($id = null)
	{
		if(null !== $id)
		{
			$table = new boat_show_ads();
			$where = $table->getAdapter()->quoteInto('boat_show_ad_id = ?', $id);
			$table->delete($where);
		}
	}

	public static function delTierTwoSponsor($id = null)
	{
		if(null !== $id)
		{
			$table = new boat_show_tier_two_sponsors();
			$aData = array(
				'status' => '0',
			);
			$where = $table->getAdapter()->quoteInto('boat_show_tier_two_sponsor_id = ?', $id);
			$table->update($aData, $where);
		}
	}

	public static function getUpcomingShow()
	{
		$table = new boat_shows();
		$select = $table->select()
			->where('status = 1') // only active
			->where('start_datetime > now()') // only shows that haven't actually started yet
			->order('start_datetime asc')
			->limit(1)
		;
		$theRow = $table->fetchRow($select);
		//die(Zend_Debug::dump($select));
		return $theRow;
	}

	public static function getAdHeader($ad_id = null)
	{
		if(null !== $ad_id)
		{
			$table = new ads();
			$select = $table->select()
				->setIntegrityCheck(false)
				->from($table, 'ads.ad_headline')
				->from('boats', 'boats.manufacturer_name')
				->where('ads.ad_id = boats.ad_id')
				->where('ads.ad_id = ?', $ad_id)
			;
			$theRow = $table->fetchRow($select);
//			die('theRow :<pre>' . print_r($theRow, true) . '</pre>');
			return array('title' => $theRow->ad_headline, 'manufacturer' => $theRow->manufacturer_name);
		}
		return array('title' => 'Untitled', 'manufacturer' => 'Unknown');
	}

	public static function getCurrentShow($boat_show_id = null)
	{
		$table = new boat_shows();
		if(null !== $boat_show_id)
		{
			$select = $table->select()
				->where('boat_show_id = ?', $boat_show_id)
			;
		} else {
			$select = $table->select()
				->where('status = 1') // only active
				->where('start_datetime < now()') // only shows that haven't actually started yet
				->where('end_datetime > now()') // only shows that haven't actually started yet
				->order('start_datetime asc')
				->limit(1)
			;
		}
		$theRow = $table->fetchRow($select);
		//die(Zend_Debug::dump($select));
		return $theRow;
	}

	public static function getUpcomingShows($bIncludeCurrent = false)
	{
		$table = new boat_shows();
		$select = $table->select()
			->where('status = 1') // only active
			->order('start_datetime asc')
		;
		if($bIncludeCurrent)
			$select->where('end_datetime > now()');
		else
			$select->where('start_datetime > now()');
		$theRow = $table->fetchAll($select);
		//die(Zend_Debug::dump($select));
		return $theRow;
	}

	function getTierTwoSponsorsCount()
	{
		$table = new boat_show_tier_two_sponsors();
		$select = $table->select()
			->from($table, 'count(*) as count')
			->where('status = 1')
		;
		$oResult = $table->fetchRow($select);
		return $oResult->count;
	}

	function getTierTwoSponsors($fields = null, $arguments = null, $where = null)
	{
		$table = new boat_show_tier_two_sponsors();
		$select = UBBaseModel::getListSelect($table, $fields, $arguments, $where);
		$select
			->setIntegrityCheck(false)
			->where('status = 1')
		;
		return UBBaseModel::getListOfRows($table, $select);
	}

	function getTierTwoSponsorsForFooter()
	{
		$oTierTwoSponsors = BoatShow::getTierTwoSponsors();
		return $oTierTwoSponsors; // i was going to do some 'limit to 4 only' stuff here but i'll do it later
	}

	function addAdToBoatShow($ad_id, $boat_show_id)
	{
		$aData['admin_id']     = AdminUser::getCurrentUser()->user_id;
		$aData['ad_id']        = $ad_id;
		$aData['boat_show_id'] = $boat_show_id;
		$table = new boat_show_ads();
		$table->insert($aData);
	}

	function addToTierTwoSponsors($aTierTwoSponsor = false)
	{
		if(false !== $aTierTwoSponsor)
		{
			$aTierTwoSponsor['admin_id'] = AdminUser::getCurrentUser()->user_id;
			$aTierTwoSponsor['added'] = date('Y-m-d H:i:s');
			$table = new boat_show_tier_two_sponsors();
			$mTierTwoSponsorInsert = $table->insert($aTierTwoSponsor);
			return $mTierTwoSponsorInsert;
		}
		return 0;
	}

	public static function getLengthWordingByShowID($iShowID = null)
	{
		if(null === $iShowID)
			return BoatShow::getLengthWording(null, null);
		$oShow = BoatShow::getCurrentShow($iShowID);
		return BoatShow::getLengthWording($oShow->length_from, $oShow->length_to);
	}

	public static function getLengthWording($iMin = null, $iMax = null)
	{
		if(null === $iMin || null === $iMax)
			return '(No show found)';
		if($iMin == 0 && $iMax == 99999)
			return 'All Lengths';
		else {
			if($iMin == 0) {
				$pre = 'Up';
				$mid = 'to';
				$post = number_format($iMax, 0) . '\'';
			} elseif($iMax == 99999) {
				$pre = number_format($iMin, 0) . '\'';
				$mid = 'and';
				$post = 'Up';
			} else {
				$pre = number_format($iMin, 0) . '\'';
				$mid = 'to';
				$post = number_format($iMax, 0) . '\'';
			}
		}
		return $pre . ' ' . $mid . ' ' . $post;
	}

	public static function getUpcomingShowString()
	{
		if(null === ($oNextBoatShow = BoatShow::getUpcomingShow()))
			return date('n/j/Y g:i A', strtotime('+2 days', mktime(date('H'), date('i'), date('s'), date('n'), date('j'), date('Y'))));
		else
			return date('n/j/Y g:i A', strtotime($oNextBoatShow->start_datetime));
	}

	public static function getCurrentShowEndDateString($boat_show_id = null)
	{
		if(null === ($oBoatShow = BoatShow::getCurrentShow($boat_show_id)))
			return date('n/j/Y g:i A'); // now
		else
			return date('n/j/Y g:i A', strtotime($oBoatShow->end_datetime));
	}

	public static function validateBoatShowAccess($boat_show_id)
	{
		$oZendSession__boat_show = new Zend_Session_Namespace('boat-show');
		if(!is_array($oZendSession__boat_show->validated_shows))
			$oZendSession__boat_show->validated_shows = array();
		if(isset($oZendSession__boat_show->validated_shows[$boat_show_id]) && true === $oZendSession__boat_show->validated_shows[$boat_show_id])
			return true;
		return false;
	}

	public static function setBoatShowAccess($boat_show_id)
	{
		$oZendSession__boat_show = new Zend_Session_Namespace('boat-show');
		if(!is_array($oZendSession__boat_show->validated_shows))
			$oZendSession__boat_show->validated_shows = array();
		$oZendSession__boat_show->validated_shows[$boat_show_id] = true;
	}

	public static function getCurrentShowTypes($boat_show_id = null)
	{
		$table = new boat_shows();
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select
			->from($table, array('boat_shows.boat_show_id'))
			->from('boat_show_ads', array('boat_show_ads.boat_show_ad_id'))
			->from('ads', array('ads.ad_id'))
			->from('boats', array('boats.boat_id'))
			->from('list_boat_types', array('list_boat_types.boat_type_name', 'list_boat_types.boat_type_id'))
			->where('boats.type_id_1 = list_boat_types.boat_type_id || boats.type_id_2 = list_boat_types.boat_type_id || boats.type_id_3 = list_boat_types.boat_type_id')
			->where('boats.boat_id = ads.boat_id')
			->where('ads.ad_id = boat_show_ads.ad_id')
			->where('boat_show_ads.boat_show_id = boat_shows.boat_show_id')
			->where('boat_shows.status = 1') // only active
			->order('list_boat_types.boat_type_name asc')
			->group('list_boat_types.boat_type_name')
		;
		if(null === $boat_show_id)
		{
			$select
				->where('boat_shows.start_datetime < now()') // only shows that haven't actually started yet
				->where('boat_shows.end_datetime > now()') // only shows that haven't actually started yet
			;
		} else {
			$select->where('boat_shows.boat_show_id = ?', $boat_show_id);
		}
		//die($select);
		$theRow = $table->fetchAll($select);
		//die(Zend_Debug::dump($select));
		return $theRow;
	}

	public static function getCurrentShowManufacturers($boat_show_id = null)
	{
		$table = new boat_shows();
		$select = $table->select();
		$select->setIntegrityCheck(false);
		$select
			->from($table, array('boat_shows.boat_show_id'))
			->from('boat_show_ads', array('boat_show_ads.boat_show_ad_id'))
			->from('ads', array('ads.ad_id'))
			->from('boats', array('boats.boat_id'))
			->from('list_boat_manufacturers', array('list_boat_manufacturers.mfg_name', 'list_boat_manufacturers.boat_manufacturer_id'))
			->where('boats.manufacturer_id = list_boat_manufacturers.boat_manufacturer_id')
			->where('boats.boat_id = ads.boat_id')
			->where('ads.ad_id = boat_show_ads.ad_id')
			->where('boat_show_ads.boat_show_id = boat_shows.boat_show_id')
			->where('boat_shows.status = 1') // only active
			->order('list_boat_manufacturers.mfg_name asc')
			->group('list_boat_manufacturers.mfg_name')
		;
		if(null === $boat_show_id)
		{
			$select
				->where('boat_shows.start_datetime < now()') // only shows that haven't actually started yet
				->where('boat_shows.end_datetime > now()') // only shows that haven't actually started yet
			;
		} else {
			$select->where('boat_shows.boat_show_id = ?', $boat_show_id);
		}
		//die($select);
		$theRow = $table->fetchAll($select);
		//die(Zend_Debug::dump($select));
		return $theRow;
	}

	public static function getCurrentShowAdsCount($boat_show_id = null, $where = null, $more_where = null)
	{
		$table  = new boat_shows();
		$select = UBBaseModel::getCountSelect($table, $where);
		$select->setIntegrityCheck(false);
		$select
			->from('boat_show_ads')
			->from('ads')
			->from('boats')
			->where('boats.boat_id = ads.boat_id')
			->where('ads.ad_id = boat_show_ads.ad_id')
			->where('boat_show_ads.boat_show_id = boat_shows.boat_show_id')
			->where('boat_shows.status = 1') // only active
			->group('boat_show_ads.boat_show_ad_id')
		;
		if(isset($more_where['type_id']))
		{
			$select
				->from('list_boat_types')
				->where('list_boat_types.boat_type_id in (boats.type_id_1, boats.type_id_2, boats.type_id_3)')
				->where('? in (boats.type_id_1, boats.type_id_2, boats.type_id_3)', $more_where['type_id'])
			;
		}
		if(isset($more_where['manufacturer_id']))
		{
			$select
				->from('list_boat_manufacturers')
				->where('boats.manufacturer_id = list_boat_manufacturers.boat_manufacturer_id')
				->where('list_boat_manufacturers.boat_manufacturer_id = ?', $more_where['manufacturer_id'])
			;
		}
		if(null !== $boat_show_id)
			$select->where('boat_shows.boat_show_id = ?', $boat_show_id);
		return    UBBaseModel::getCount($table, $select);
	}

	public static function getCurrentShowAds($boat_show_id = null, $arguments = null, $where = null)
	{
		$more_where = $where;
		if(null === $boat_show_id)
			return null;
		$table = new boat_shows();
		$select = UBBaseModel::getListSelect($table, null, $arguments, $where);
		$select->setIntegrityCheck(false);
		$select
			->from($table, array('boat_shows.boat_show_id'))
			->from('boat_show_ads', array('boat_show_ads.boat_show_ad_id'))
			->from('ads', array('ads.ad_id', 'ads.user_id', 'ads.price_dollars', 'ads.price_currency'))
			->from('boats', array('boats.boat_id', 'boats.manufacturer_name', 'boats.model_name', 'boats.length', 'boats.year'))
			->joinLeft('user_organizations', 'user_organizations.organization_id = ads.organization_id', array('user_organizations.organization_name'))
			->where('boats.boat_id = ads.boat_id')
			->where('ads.ad_id = boat_show_ads.ad_id')
			->where('boat_show_ads.boat_show_id = boat_shows.boat_show_id')
			->where('boat_shows.status = 1') // only active
			->group('boat_show_ads.boat_show_ad_id')
		;
		if(isset($more_where['type_id']))
		{
			$select
				->from('list_boat_types')
				->where('list_boat_types.boat_type_id in (boats.type_id_1, boats.type_id_2, boats.type_id_3)')
				->where('? in (boats.type_id_1, boats.type_id_2, boats.type_id_3)', $more_where['type_id'])
			;
		}
		if(isset($more_where['manufacturer_id']))
		{
			$select
				->from('list_boat_manufacturers')
				->where('boats.manufacturer_id = list_boat_manufacturers.boat_manufacturer_id')
				->where('list_boat_manufacturers.boat_manufacturer_id = ?', $more_where['manufacturer_id'])
			;
		}
		if(null !== $boat_show_id)
			$select->where('boat_shows.boat_show_id = ?', $boat_show_id);
		$oResults = UBBaseModel::getListOfRows($table, $select);
		return $oResults;
	}

	public static function getUserAdIdsArray($user_id = null)
	{
		$aReturn = array();
		if(null === $user_id)
			return $aReturn;
		else {
			$table = new boat_shows();
			$select = $table->select()
				->setIntegrityCheck(false)
				->from($table, array('boat_shows.boat_show_id'))
				->from('boat_show_ads', array('boat_show_ads.ad_id'))
				->from('ads', array('ads.user_id'))
				->where('boat_shows.boat_show_id = boat_show_ads.boat_show_id')
				->where('boat_show_ads.ad_id = ads.ad_id')
				->where('ads.user_id = ?', $user_id)
				->group('ads.ad_id')
			;
			$oResults = UBBaseModel::getListOfRows($table, $select);
			foreach($oResults as $iKey => $aResult)
			{
				$aReturn[$aResult->ad_id] = true;
			}
			return $aReturn;
		}
		
	}

	public static function validateAdForShow($ad_id = null, $boat_show_id = null)
	{
		if(null === $ad_id || null === $boat_show_id)
			return false;
		$table = new boat_show_ads();
		$select = $table->select()
			->from($table, 'count(ad_id) as count')
			->where('boat_show_id = ?', $boat_show_id)
			->where('ad_id = ?', $ad_id)
		;
		$theRow = $table->fetchRow($select);
		if($theRow->count > 0)
			return true;
		return false;
	}

	public static function getYouTubeID($boat_show_id = null, $ad_id = null)
	{
		if(null !== $boat_show_id && null !== $ad_id)
		{
			$table = new boat_shows();
			$select = $table->select()
				->setIntegrityCheck(false)
				->from($table, array('boat_show_id'))
				->from('boat_show_ads', array('boat_show_ad_id'))
				->from('ads', array('ad_id'))
				->from('ad_videos', array('youtube_id'))
				->where('boat_show_ads.boat_show_id = boat_shows.boat_show_id')
				->where('ads.ad_id = boat_show_ads.ad_id')
				->where('ad_videos.ad_id = ads.ad_id')
				->where('boat_shows.boat_show_id = ?', $boat_show_id)
				->where('boat_show_ads.ad_id = ?', $ad_id)
				->limit(1)
			;
			$theRow = $table->fetchRow($select);
			if(isset($theRow->youtube_id))
				return $theRow->youtube_id;
		}
		return null;
	}

}

class boat_shows extends Zend_Db_Table_Abstract {
	protected $_name = 'boat_shows';
	protected $_primary = 'boat_show_id';
	protected $_rowClass = 'BoatShow';
}

class boat_show_ads extends Zend_Db_Table_Abstract {
	protected $_name = 'boat_show_ads';
	protected $_primary = 'boat_show_ad_id';
	protected $_rowClass = 'BoatShow';

	// boat_show_ads input not yet implemented // public function getUpcomingBoatShowAdsCount($boat_show_id = null)
	public function getUpcomingBoatShowAdsCount()
	{
		$db = $this->getAdapter();
		$select = $db->select()
			->from('boat_show_ads as bsa', 'COUNT(bsa.boat_show_ad_id) AS count')
			->join('boat_shows as bs', 'bsa.boat_show_id = bs.boat_show_id')
			//->group('bsa.boat_show_ad_id')
		;
//echo('count select : ' . $select . '<br />');
		$aResult = $db->fetchAll($select);
		return $aResult[0]['count'];
	}

	public function getUpcomingBoatShowAds()
	{
		$db = $this->getAdapter();
		$select = $db->select()
			->from('boat_show_ads as bsa', 'bsa.boat_show_ad_id', 'bsa.admin_id', 'bsa.boat_show_id', 'bsa.ad_id')
			->join('boat_show as bs', 'bsa.boat_show_id = bs.boat_show_id')
		;
		return $db->fetchAll($select);
	}

}

class boat_show_dealer_info extends Zend_Db_Table_Abstract {
	protected $_name = 'boat_show_dealer_info';
	protected $_primary = 'boat_show_dealer_info_id';
	protected $_rowClass = 'BoatShow';
}

class boat_show_chat extends Zend_Db_Table_Abstract {
	protected $_name = 'boat_show_chat';
	protected $_primary = 'boat_show_chat_id';
	protected $_rowClass = 'BoatShow';
}

class boat_show_tier_two_sponsors extends Zend_Db_Table_Abstract {
	protected $_name = 'boat_show_tier_two_sponsors';
	protected $_primary = 'boat_show_tier_two_sponsor_id';
	protected $_rowClass = 'BoatShow';
}

?>
