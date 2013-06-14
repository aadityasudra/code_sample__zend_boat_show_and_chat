$(document).ready(function(){
	initChat();
	//$('#status_indicator').click(function(){initChat();});
	$('#status_indicator').click(function(){getRecentMessages();});
	$('#the_message').keydown(function(e){if(e.keyCode == 13){sendMessage();}});
	$('#the_send_button').click(function(){sendMessage();});
});
function heartbeat(){
	getRecentMessages();
	setTimeout("heartbeat();", (chatCfg['heartbeatdelayseconds']*1000));
}
function startThrob(){
	if(!chatCfg['busy'])
	{
		chatCfg['busy'] = true;
		$('#status_indicator').html('<img src="/images/wait_blue_pulse.gif">');
		return true;
	}
	//silently ignore for now//alert('Please limit your requests to once every 5 seconds.');
	return false;
}
function stopThrob(){
	chatCfg['busy'] = false;
	$('#status_indicator').html('<img src="/images/wait_blue_pulse_stopped.gif">');
}
function addToChatDisplay(boatshowchatid, name, nameclass, message, messageclass, added){
	chatCfg['lastboatshowchatid'] = boatshowchatid;
	if(chatCfg['chatrowonoff'] == 'off'){
		chatCfg['chatrowonoff'] = 'on';
	}else{
		chatCfg['chatrowonoff'] = 'off';
	}
	$('#the_chat').append('<div class="chat-row '+chatCfg['chatrowonoff']+' '+nameclass+'"><div class="name '+nameclass+'">'+name+'</div><div class="message '+messageclass+'">'+message+'</div></div>');
	//$("#the_outer_chat").scrollTop($("#the_outer_chat")[0].outerHeight);
	$("#the_outer_chat").scrollTop($("#the_chat").outerHeight());
}
function sendMessage(){
	//alert('sending message..');
	if(trim($('#the_message').val()) != ''){
		var tmpMessage = $('#the_message').val();
		$.ajax({
			url: 'http://'+chatCfg['domain']+'/basic-chat/ajax-send-message/boat_show_id/'+chatCfg['boatshowid']+'/ad_id/'+chatCfg['adid']+'/message/'+escape($('#the_message').val()),
			type: 'POST',
			dataType: 'json',
			timeout: 4000,
			error: function(msg)
			{
				//alert('ERROR : '+msg);
			},
			success: function(msg)
			{
				if(typeof msg == 'object')
				{
					//alert(print_r(msg, true, ' '));
					if(msg['response_code'] != 0)
					{
						$('#the_message').val(tmpMessage);
						alert(msg['response_message']);
					}
				}
			}
		});
		setTimeout('getRecentMessages();', 500);
	}
	setTimeout("$('#the_message').val('');", 100);
}
function getRecentMessages(){
	//alert('http://'+chatCfg['domain']+'/basic-chat/ajax-get-recent-messages/boat_show_id/'+chatCfg['boatshowid']+'/ad_id/'+chatCfg['adid']+'/last_boat_show_chat_id/'+chatCfg['lastboatshowchatid']);
	if(startThrob())
	{
		//alert('last seen boat_show_chat_id : '+chatCfg['lastboatshowchatid']);
		//stopThrob();
		$.ajax({
			url: 'http://'+chatCfg['domain']+'/basic-chat/ajax-get-recent-messages/boat_show_id/'+chatCfg['boatshowid']+'/ad_id/'+chatCfg['adid']+'/last_boat_show_chat_id/'+chatCfg['lastboatshowchatid'],
			type: 'POST',
			dataType: 'json',
			timeout: 4000,
			error: function(msg)
			{
				stopThrob();
				//alert('ERROR : '+msg);
			},
			success: function(msg)
			{
				setTimeout("stopThrob();", (chatCfg['userwaitseconds']*1000));
				if(typeof msg == 'object')
				{
					var responseLength=msg.length;
					for(var i=0; i<responseLength; i++)
					{
						//alert(print_r(msg[i], true, ' '));
						addToChatDisplay(msg[i]['boat_show_chat_id'], msg[i]['firstname'], msg[i]['nameclass'], msg[i]['message'], msg[i]['messageclass'], msg[i]['added']);
					}
				}
			}
		});
	}
}
function initChat(){
	if(startThrob())
	{
		$('#the_chat tr').remove(); // blank out the chat log
		addToChatDisplay(-1, 'SYSTEM', 'system', 'Fetching chat log..', 'specialinfo', chatCfg['datetimeshort']);
		$.ajax({
			url: 'http://'+chatCfg['domain']+'/basic-chat/ajax-get-room-log/boat_show_id/'+chatCfg['boatshowid']+'/ad_id/'+chatCfg['adid'],
			type: 'POST',
			dataType: 'json',
			timeout: 4000,
			error: function(msg)
			{
				stopThrob();
				//alert('ERROR : '+msg);
			},
			success: function(msg)
			{
				setTimeout("stopThrob();", (chatCfg['userwaitseconds']*1000));
				if(typeof msg == 'object')
				{
					var responseLength=msg.length;
					addToChatDisplay(-1, 'SYSTEM', 'system', 'Found '+responseLength+' message(s)', 'specialinfo', chatCfg['datetimeshort']);
					for(var i=0; i<responseLength; i++)
					{
						//alert(print_r(msg[i], true, ' '));
						addToChatDisplay(msg[i]['boat_show_chat_id'], msg[i]['firstname'], msg[i]['nameclass'], msg[i]['message'], msg[i]['messageclass'], msg[i]['added']);
					}
				}
				heartbeat();
			}
		});
	}
}
