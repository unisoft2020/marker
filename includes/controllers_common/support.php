<?php
	
	function controller_support($data) {
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// vars
		$offset = isset($data['offset']) ? $data['offset'] : 0;
		$status = isset($data['sub']) ? $data['sub'] : 'active';
		// info
		$support = Support::support_list(['offset'=>$offset, 'status'=>$status]);
		// output
		HTML::assign('support', $support['info']);
		HTML::assign('paginator', $support['paginator']);
		HTML::assign('offset', $offset);
		HTML::assign('status', $status);
		return HTML::main_content('./partials/section/support/support.html', Session::$mode);
	}

	function controller_support_ticket($ticket_id) {
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// info
		$support = Support::support_info($ticket_id);
		$user = User::user_info(Session::$user_id);
		$messages = Support::support_messages_list($ticket_id);
		// output
		HTML::assign('support', $support);
		HTML::assign('user', $user);
		HTML::assign('messages', $messages['info']);
		return HTML::main_content('./partials/section/support/support_ticket.html', Session::$mode);
	}

?>