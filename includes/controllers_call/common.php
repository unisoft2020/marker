<?php

	function controller_common($sub, $act, $data) {
		
		if ($sub == 'search') {
			if ($act == 'do') return Search::search_do($data);
		}
		
		if ($sub == 'intro') {
			if ($act == 'change_page') return Intro::change_page($data);
			if ($act == 'video_window') return Intro::video_window($data);
			if ($act == 'send_request') return Email::send_request($data);
			if ($act == 'send_demo') return Email::send_demo($data);
		}
	
	}

?>