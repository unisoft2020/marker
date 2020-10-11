<?php
	
	function controller_picks($data) {
		// validate
		if (Session::$access != 4) access_error(Session::$mode);
		// vars
		$offset = isset($data['offset']) ? $data['offset'] : 0;
        $data['query'] = isset($data['q']) ? trim($data['q']) : '';
		// info
		$picks = Pick::picks_list($data);
		// output
		HTML::assign('picks', $picks['info']);
		HTML::assign('paginator', $picks['paginator']);
		HTML::assign('offset', $offset);
        HTML::assign('search_query', $data['query']);
		return HTML::main_content('./partials/section/picks/picks.html', Session::$mode);
	}

?>