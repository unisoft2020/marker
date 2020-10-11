<?php
	
	function controller_settings() {
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// info
		$info = Company::company_info(Session::$company_id);
		// output
		HTML::assign('info', $info);
		return HTML::main_content('./partials/section/settings/settings.html', Session::$mode);
	}

?>