<?php

	function controller_owner($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (Session::$access) Session::access_error();
			if ($act == 'login') return Session::login($data);
			if ($act == 'password_restore_window') return User_Password_Restore::password_restore_window();
			if ($act == 'password_restore') return User_Password_Restore::password_restore_request($data);
			if ($act == 'new_password') return User_Password_Restore::password_restore_update($data);
			if ($act == 'send_request_window') return Intro::send_request_window();
			if ($act == 'send_request') return Email::send_request($data);
		}
		
		if ($sub == 'notifications') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Notification::notifications_fetch($data);
			if ($act == 'notifications_read') return Notification::notifications_read($data);
		}
	
	}

?>