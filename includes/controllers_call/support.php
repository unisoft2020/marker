<?php

	function controller_support($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Support::support_fetch($data);
			if ($act == 'create_window') return Support::support_create_window();
			if ($act == 'create_update') return Support::support_create_update($data);
			if ($act == 'close_ticket') return Support::support_close_ticket($data);
			if ($act == 'send_message') return Support::support_send_message($data);
		}
	
	}

?>