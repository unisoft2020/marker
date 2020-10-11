<?php

	function controller_bills($data) {
		// validate
		if (Session::$access != 2) access_error(Session::$mode);
		// vars
		$offset = isset($data['offset']) ? $data['offset'] : 0;
		// info
		$bills = Bill::bills_list($data);
		// output
		HTML::assign('bills', $bills['info']);
		HTML::assign('paginator', $bills['paginator']);
		HTML::assign('offset', $offset);
		return HTML::main_content('./partials/section/bills/bills.html', Session::$mode);
	}

	function controller_bill_download($bill_id) {
		// validate
		if (Session::$access != 2) access_error(Session::$mode);
		// actions
		Bill::bill_download($bill_id);
	}

?>