<?php

	function controller_task($sub, $act, $data) {

		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'edit') return Task::task_edit($data);
			if ($act == 'delete') return Task::task_delete($data);
		}
		
		if ($sub == 'users') {
			if (!Session::$access) Session::access_error();
			if ($act == 'add_window') return Task_User::task_users_add_window($data);
			if ($act == 'search') return Task_User::task_users_search($data);
		}
		
		if ($sub == 'products') {
			if (!Session::$access) Session::access_error();
			if ($act == 'add_window') return Task_Product::task_products_add_window($data);
			if ($act == 'search') return Task_Product::task_products_search($data);
		}
	
	}

?>