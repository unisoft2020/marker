<?php

	function controller_bill($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Bill::bills_fetch($data);
			if ($act == 'create_window') return Bill::bill_create_window();
			if ($act == 'create_update') return Bill::bill_create_update($data);
			if ($act == 'delete') return Bill::bill_delete($data);
		}
	
	}

?>