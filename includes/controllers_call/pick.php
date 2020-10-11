<?php

	function controller_pick($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Pick::picks_fetch($data);
			if ($act == 'export') return Pick::pick_export($data);
			if ($act == 'archive_toggle') return Pick::pick_archive_toggle($data);
		}
	
	}

?>