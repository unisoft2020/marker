<?php

	function controller_settings($sub, $act, $data) {

		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'update') return Company::company_settings_update($data);
		}
	
	}

?>