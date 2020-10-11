<?php

	function controller_users($data) {
		// validate
		if (Session::$access != 2) access_error(Session::$mode);
		// vars
		$query = isset($data['query']) ? $data['query'] : '';
		$offset = isset($data['offset']) ? $data['offset'] : 0;
		// info
		$users = User::users_list($data);
		// output
		HTML::assign('users', $users['info']);
		HTML::assign('paginator', $users['paginator']);
		HTML::assign('query', $query);
		HTML::assign('offset', $offset);
		return HTML::main_content('./partials/section/users/users.html', Session::$mode);
	}

?>