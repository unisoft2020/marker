<?php
	
	function controller_scans($data) {
		// validate
		if (Session::$access != 4) access_error(Session::$mode);
		// vars
		$offset = isset($data['offset']) ? $data['offset'] : 0;
		$data['query'] = isset($data['q']) ? trim($data['q']) : '';
		// info
		$scans = Scan::scans_list($data);
		// output
		HTML::assign('scans', $scans['info']);
		HTML::assign('paginator', $scans['paginator']);
		HTML::assign('offset', $offset);
		HTML::assign('search_query', $data['query']);
		return HTML::main_content('./partials/section/scans/scans.html', Session::$mode);
	}

?>