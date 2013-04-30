<?php

class BasicChatController extends UBBaseController {

	public function indexAction()
	{
	}

	public function ajaxSendMessageAction()
	{
		$oMessageFilter = new Zend_Filter();
		$oMessageFilter
			->addFilter(new Zend_Filter_HtmlEntities())
			->addFilter(new Zend_Filter_StripTags())
		;
		$user_id = false;
		if(isset($_SESSION['userauth']) && isset($_SESSION['userauth']['user_id']))
			$user_id = $_SESSION['userauth']['user_id'];
		$this->_helper->layout->disableLayout();
		$aResponse = BoatShow::sendChatMessage($this->_request->get('boat_show_id'), $this->_request->get('ad_id'), $user_id, $oMessageFilter->filter($this->_request->get('message')));
		$this->view->json_output = Zend_Json::encode($aResponse);
	}

	public function ajaxGetRecentMessagesAction()
	{
		$this->_helper->layout->disableLayout();
		$oRoomLog = BoatShow::getRecentChatMessages($this->_request->get('boat_show_id'), $this->_request->get('ad_id'), $this->_request->get('last_boat_show_chat_id'));
		$aResponse = array();
		foreach($oRoomLog as $logKey => $logMessage)
		{
			$sNameClass = '';
			if($logMessage->boat_show_chat_user_id == $logMessage->ad_owner_user_id)
				$sNameClass = 'owner';
			$aResponse[] = array(
				'boat_show_chat_id' => $logMessage->boat_show_chat_id,
				'firstname' => $logMessage->firstname,
				'nameclass' => $sNameClass,
				'message' => $logMessage->message,
				'messageclass' => '',
				'added' => date('m/d@g:sa', strtotime($logMessage->added)),
			);
		}
		$this->view->json_output = Zend_Json::encode($aResponse);
	}

	public function ajaxGetRoomLogAction()
	{
		$this->_helper->layout->disableLayout();
		$oRoomLog = BoatShow::getChatRoomLog($this->_request->get('boat_show_id'), $this->_request->get('ad_id'));
		$aResponse = array();
		foreach($oRoomLog as $logKey => $logMessage)
		{
			$sNameClass = '';
			if($logMessage->boat_show_chat_user_id == $logMessage->ad_owner_user_id)
				$sNameClass = 'owner';
			$aResponse[] = array(
				'boat_show_chat_id' => $logMessage->boat_show_chat_id,
				'firstname' => $logMessage->firstname,
				'nameclass' => $sNameClass,
				'message' => $logMessage->message,
				'messageclass' => '',
				'added' => date('m/d@g:sa', strtotime($logMessage->added)),
			);
		}
		$this->view->json_output = Zend_Json::encode($aResponse);
	}

}
