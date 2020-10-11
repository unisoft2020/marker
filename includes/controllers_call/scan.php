<?php

	function controller_scan($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Scan::scans_fetch($data);
			if ($act == 'sort') return Scan::scan_sort($data);
		}
	
	}

?>