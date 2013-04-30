<?php

class BoatShowController extends UBBaseController
{

	public function indexAction()
	{
		$this->view->headTitle('Virtual Boat Show | Boating Life 360');
		$this->view->sNextVBSDate         = BoatShow::getUpcomingShowString();
		$this->view->sNowDate             = date('n/j/Y g:i A');
		$this->view->oNextThreeShows      = BoatShow::getNextShows(array(3, 0));
		$this->view->oUpcomingShow        = BoatShow::getUpcomingShow();
		$this->view->sUpcomingShowWording = BoatShow::getLengthWording($this->view->oUpcomingShow->length_from, $this->view->oUpcomingShow->length_to);
		$this->view->bIsShowRunning       = BoatShow::isShowCurrentlyRunning();
		if($this->view->bIsShowRunning)
		{
			$this->view->oCurrentShow        = BoatShow::getCurrentShow();
			$this->view->sCurrentShowWording = BoatShow::getLengthWording($this->view->oCurrentShow->length_from, $this->view->oCurrentShow->length_to);
		}
	}

	private function popInnerHeader()
	{
		$this->view->headTitle('Virtual Boat Show | Boating Life 360');
		//debug stuff
		$bOverrideAccess = false;
		if(null !== $this->_request->get('override_access', null))
			$bOverrideAccess = true;
		$test_error = $this->_request->get('test_error', null);
		if(null !== $test_error)
			$this->addError($test_error);
		$test_msg = $this->_request->get('test_msg', null);
		if(null !== $test_msg)
			$this->addMessage($test_msg);
		//end debug stuff
		$this->view->headScript()->appendFile('/js/vbs_dropmenu.js');
		$this->view->headScript()->appendFile('/js/dropmenu_basic.js');
		$this->view->headScript()->appendFile('/js/vbs-homepage.js');
		$this->view->headScript()->appendFile('/js/time-ticker.js');
		$this->view->iBoatShowID     = $this->_request->getParam('boat_show_id', null);
		$this->view->iAdID           = $this->_request->getParam('ad_id', null);
		$this->view->type_id         = $this->_request->getParam('type_id', null);
		$this->view->manufacturer_id = $this->_request->getParam('manufacturer_id', null);
		$this->view->sTypeArgInsert = '';
		$this->view->sManuArgInsert = '';
//		if(null !== $this->view->type_id){$this->view->sTypeArgInsert = '/type_id/' . $this->view->type_id;}
//		if(null !== $this->view->manufacturer_id){$this->view->sManuArgInsert = '/manufacturer_id/' . $this->view->manufacturer_id;}
		$this->view->oCurrentShow    = BoatShow::getCurrentShow($this->view->iBoatShowID);
		$bIsShowCurrentlyRunning     = BoatShow::isShowCurrentlyRunning($this->view->iBoatShowID);
		$this->view->oTypes          = BoatShow::getCurrentShowTypes($this->view->iBoatShowID);
		$this->view->oManufacturers  = BoatShow::getCurrentShowManufacturers($this->view->iBoatShowID);
		//kick them out if the show is over or they are not allowed in after it has been closed
		if(false === $bIsShowCurrentlyRunning && false === BoatShow::validateBoatShowAccess($this->view->iBoatShowID) && false === $bOverrideAccess)
		{
			if($this->view->oCurrentShow == null) { 
				$this->addError('Invalid Boat Show ID : ' . $this->view->iBoatShowID);
			} else {
				$this->addMessage('The <span>' . BoatShow::getLengthWording($this->view->oCurrentShow->length_from, $this->view->oCurrentShow->length_to) . '</span> Boat Show is now closed!');
			}
			$this->_redirect('/boat-show');
		}
		//pick up on ad_id coming in to make sure it belongs to this show
		if(null !== ($iAdID = $this->_request->get('ad_id', null)))
		{
			if(false === BoatShow::validateAdForShow($iAdID, $this->view->iBoatShowID))
			{
				$this->addMessage('Invalid Ad Id : ' . $iAdID);
				$this->_redirect('/boat-show/view/boat_show_id/' . $this->view->iBoatShowID);
			}
		}
		BoatShow::setBoatShowAccess($this->view->iBoatShowID); // this makes it so the user can re-visit this boat show after it has ended.  this grace period only lasts as long as their session.
		$this->view->sNextVBSDate    = BoatShow::getUpcomingShowString();
		$this->view->oNextThreeShows = BoatShow::getNextShows(array(3, 0));
		$this->view->sCurrentVBSEndDate = BoatShow::getCurrentShowEndDateString($this->view->iBoatShowID);
		$this->view->sNowDate = date('n/j/Y g:i A');
	}

	public function viewAction()
	{
		$this->popInnerHeader(); // setup everything needed for the vbs inner header
		$this->view->iBoatShowID     = $this->_request->getParam('boat_show_id', null);
		$tableMaker                  = $this->_helper->getHelper('TableSortableList');
		$tableMaker->setIdField('boat_show_id');
		$this->view->resultsPerPage  = $this->_request->getParam('count', 25);
		$this->view->where_args      = array();
		if(null !== $this->view->type_id)
			$this->view->where_args['type_id'] = $this->view->type_id;
		if(null !== $this->view->manufacturer_id)
			$this->view->where_args['manufacturer_id'] = $this->view->manufacturer_id;
		$boat_show_ads_count         = BoatShow::getCurrentShowAdsCount($this->view->iBoatShowID, $this->view->paging_info, $this->view->where_args);
		$this->view->paging_info     = $tableMaker->setPagingInfo('boat_show_id', $boat_show_ads_count, 'ASC', $this->view->resultsPerPage); // not really using table maker for anything other than figuring out the paging info since all the logic/math is already worked out :)
		$this->view->oBoatShowAds    = BoatShow::getCurrentShowAds($this->view->iBoatShowID, $this->view->paging_info, $this->view->where_args);
		$this->view->sShowLengthText = BoatShow::getLengthWordingByShowID($this->view->iBoatShowID);
	}

	public function viewAdAction()
	{
		#common
		$this->popInnerHeader(); // setup everything needed for the vbs inner header
		$this->view->headScript()->appendFile('/js/vbs-view-ad.js');
		$this->view->ad_id = $this->_request->get('ad_id');
		$boatAd = BoatAd::getBoatAd($this->_request->get('ad_id')); // we can trust that it is populated properly b/c the popInnerHeader() does security before we get here specific to ad_id
		$ad = $boatAd->getAd();
		$this->view->ad_header = BoatShow::getAdHeader($this->_request->get('ad_id'));
		#tab photos
		$this->view->tab_photos = array();
		$this->view->tab_photos['images'] = $ad->getImagesForAd(null, null, null, 'display_order ASC');
		$this->view->headScript()->appendFile('/js/vbs-view-ad--tab-photos.js');
		#tab video
		$this->view->tab_video = array();
		$this->view->tab_video['youtube_video_id'] = BoatShow::getYouTubeID($this->view->iBoatShowID, $this->view->iAdID);
		#tab specifications
		$this->view->tab_specifications = array();
		$this->view->tab_specifications['boat'] = $boatAd->getBoat();
		$this->view->tab_specifications['ad'] = $ad;
		#tab dealer info
		$this->view->tab_dealer_info = array();
		$this->view->tab_dealer_info['data'] = BoatShow::getDealerInfo(BoatShow::getBoatShowDealerInfoIdByAdId($this->_request->get('ad_id')));
		$this->view->tab_dealer_info['user_id'] = BoatShow::getUserIdByAdId($this->view->iAdID);
		#tab request info
		$this->view->tab_request_info = array();
		$this->view->tab_request_info['boat_show_id'] = $this->view->iBoatShowID;
		$this->view->tab_request_info['ad_id'] = $this->view->iAdID;
		$this->view->tab_request_info['form_processed'] = $this->_request->get('form_processed', null);
		$this->view->headScript()->appendFile('/js/vbs-view-ad--tab-request-info.js');
		#chat room
		$oSiteConfig = Zend_Registry::get('siteConfig');
		$this->view->vbs_chat_info = array();
		$this->view->vbs_chat_info['hostname'] = $oSiteConfig->hostname;
		$this->view->vbs_chat_info['ad_id'] = $this->view->iAdID;
		$this->view->vbs_chat_info['boat_show_id'] = $this->view->iBoatShowID;
		$this->view->vbs_chat_info['datetimeshort'] = date('m/d@g:sa');
		//$this->view->sChatRoomID = 'boat_' . $this->view->iBoatShowID . '__' . $this->view->iAdID;
		//$oChat = new Bonnier_Chat();
		//$oChat->createRoom($this->view->sChatRoomID);
		$this->view->sDefaultTab = $this->_request->get('tab', null);
		if(null === $this->view->sDefaultTab)
			$this->view->sDefaultTab = 'photos';
		$this->view->fHideChat = $this->_request->get('hide_chat', null);
		$userauth = User::getUserSession();
		if(isset($userauth->user_id) && $userauth->user_id > 0)
		{
			if(!is_array($userauth->boat_show)) $userauth->boat_show = array();
			$userauth->boat_show['my_ads'] = BoatShow::getUserAdIdsArray($userauth->user_id);
		}
	}

	public function requestInfoProcessingAction()
	{
		$boatAd = BoatAd::getBoatAd($this->_request->get('ad_id', null));
		$ad     = $boatAd->getAd();
		$lead = array(
			'name' => Zend_Filter::get($this->_request->get('first_name') . ' ' . $this->_request->get('last_name'), 'StripTags'),
			'email' => Zend_Filter::get($this->_request->get('email'), 'StripTags'),
			'zipcode' => Zend_Filter::get($this->_request->get('zip_code'), 'StripTags'),
			'phone' => '',
			'subject' => 'BL360 Boat Show Lead for Ad Id ' . $this->_request->get('ad_id'),
			'message' => 'I saw the Listing for the Ad No. ' . $this->_request->get('ad_id') . ' on BoatingLife360.com Boat Classifieds. Please contact me regarding this listing.',
			'copy_me' => 1
		);
		$valid = AdLead::validate($lead);
		if($valid['passed'] !== false)
		{
			$mNewAdLeadResp = $ad->newAdLead($lead,$lead['copy_me']);
			if($mNewAdLeadResp === false)
			{
				$this->addError("Your email was not sent to the seller. An unknown error occurred. Please try again.");
				$this->_redirect('/boat-show/view-ad/boat_show_id/' . $this->_request->get('boat_show_id') . '/ad_id/' . $this->_request->get('ad_id') . '/tab/request_info');
			} elseif($mNewAdLeadResp === 'email_queued') {
				$sQueuedMessage = 'Your message has been queued and a verification email has been sent to you.  You must check your email and follow the verification link for it to be delivered.';
				//$this->addMessage('<b>' . $sQueuedMessage . '</b><script> $(document).ready(function(){ alert("' . $sQueuedMessage . '"); }); </script>');
				$this->addMessage('<b>' . $sQueuedMessage . '</b>');
			} else {
				//not entirely sure this will ever happen .. here (this code was taken from BoatAdController)
				$this->addMessage("Your email was sent to the seller.");
			}
			$this->_redirect('/boat-show/view-ad/boat_show_id/' . $this->_request->get('boat_show_id') . '/ad_id/' . $this->_request->get('ad_id') . '/tab/request_info/form_processed/yes');
		}
		$this->addError("Your email was not sent to the seller. An unknown error occurred. Please try again.");
		$this->_redirect('/boat-show/view-ad/boat_show_id/' . $this->_request->get('boat_show_id') . '/ad_id/' . $this->_request->get('ad_id') . '/tab/request_info');
	}

	public function verifyLeadAction()
	{
		$boatAd = BoatAd::getBoatAd($this->_request->get('ad_id', null));
		$ad     = $boatAd->getAd();
		$lead_id = $this->_request->get('lead_id', null);
		$verification_key = $this->_request->get('verification_key', null);
		if(null !== $lead_id)
		{
			$ad_lead = AdLead::getAdLead($this->_request->get('lead_id', null));
			if($ad_lead->queue_status == 2 && $ad_lead->verification_key == $verification_key)
			{
				if($ad->newAdLead($ad_lead->toArray(), $ad_lead->copy_me, true) === false)
				{
					$this->addError('While your validation was successful, the email was not send to the seller for an unknown reason.  Please try again.');
				} else {
					$ad_lead->queue_status = '1';
					$ad_lead->save();
					$this->addMessage('Your email was sent to the seller.');
				}
			}
		}
		// r00r //$oCurrentBoatShow = BoatShow::getCurrentShow();
		// r00r //$this->_redirect('/boat-show/view-ad/boat_show_id/' . $oCurrentBoatShow->boat_show_id . '/ad_id/' . $this->_request->get('ad_id') . '/tab/request_info');
		$this->_redirect('/boat-show/'); // bu will likely want the above
	}

}



























?>
