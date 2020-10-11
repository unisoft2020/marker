<?php
	
	function controller_dashboard() {
		// validate
		if (Session::$access != 2) access_error(Session::$mode);
		// actions
		Stats::stats_empty_fill();
		// output
		HTML::assign('company', Company::company_info(Session::$company_id));
		HTML::assign('users_count', User::users_count());
		HTML::assign('stats', Stats::users_list(Session::$ts));
		return HTML::main_content('./partials/section/dashboard/dashboard.html', Session::$mode);
	}

?>