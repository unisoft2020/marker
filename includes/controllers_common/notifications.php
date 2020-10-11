<?php
	
	function controller_notifications($data) {
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// vars
		$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
		// info
		$notifications = Notification::notifications_list(['offset'=>$offset]);
		$user = User::user_info(Session::$user_id);
		Notification::notifications_read();
		// output
		HTML::assign('offset', $offset);
		HTML::assign('notifications', $notifications['info']);
		HTML::assign('paginator', $notifications['paginator']);
		HTML::assign('count_notifications', $user['count_notifications']);
		return HTML::main_content('./partials/section/notifications/notifications.html', Session::$mode);
	}

?>