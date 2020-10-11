<?php

	function controller_user($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return User::users_fetch($data);
			if ($act == 'edit_window') return User::user_edit_window($data);
			if ($act == 'edit_update') return User::user_edit_update($data);
			if ($act == 'sort') return User::user_sort($data);
			if ($act == 'block') return User::user_block($data);
			if ($act == 'delete_window') return User::user_delete_window($data);
			if ($act == 'delete_update') return User::user_delete_update($data);
		}
	
	}

?>