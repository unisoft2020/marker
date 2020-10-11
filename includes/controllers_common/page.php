<?php

	function controller_page($page_id) {
		// info
		$page = Page::page_info($page_id);
		// output
		HTML::assign('page', $page);
		return HTML::assign('section_content', './partials/section/pages/page.html');
	}

?>