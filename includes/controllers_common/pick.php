<?php
	
	function controller_picks($data) {
		// validate
		if (Session::$access != 4) access_error(Session::$mode);
		// vars
		$offset = isset($data['offset']) ? $data['offset'] : 0;
		// info
		$picks = Pick::picks_list($data);
		// output
		HTML::assign('picks', $picks['info']);
		HTML::assign('paginator', $picks['paginator']);
		HTML::assign('offset', $offset);
		return HTML::main_content('./partials/section/picks/picks.html', Session::$mode);
	}

?>