var in_progress = false;

// CONTRACTORS

function contractor_search() {
	in_progress = true;
	setTimeout(contractor_search_do, 200);
}

function contractor_search_do() {
	// vars
	var query = gv('search_query');
	var type = gv('type');
	// ajax (vars)
	var data = { query: query, type: type };
	var location = { section: 'contractor', action: 'search' };
	// ajax (call)
	request({ location: location, data: data }, function(result) {
		// actions
		html('companies_table', result.html);
		html('paginator_wrap', result.paginator);
		in_progress = false;
		// update
		if (query) {
			replace_class('search_sub', 'icon_search', 'icon_delete');
			add_class('search_sub', 'active');
			attr('search_sub', 'onclick', 'contractor_search_clear();')
		} else {
			replace_class('search_sub', 'icon_delete', 'icon_search');
			remove_class('search_sub', 'active');
			attr('search_sub', 'onclick', '')
		}
		// url
		var url = './auth?sub=companies';
		//if (offset) url += '&offset=' + offset;
		if (query) url += '&query=' + query;
		if (type != -1) url += '&type=' + type;
		window.history.pushState('', '', url);
	});
}

function contractor_search_clear() {
	sv('search_query', '');
	contractor_search();
}

function contractor_type() {
	contractor_search_do();
}

function company_edit(id) {
	// vars
	var type = gv('i_type');
	var code = gv('i_code');
	var title = gv('i_title');
	var ogrn = gv('i_ogrn');
	var inn = gv('i_inn');
	var kpp = gv('i_kpp');
	var address = gv('i_address');
	var phone = gv('i_phone');
	var email = gv('i_email');
	var last_name = gv('i_last_name');
	var first_name = gv('i_first_name');
	var middle_name = gv('i_middle_name');
	var user_email = gv('i_user_email');
	var user_phone = gv('i_user_phone');
	// ajax (vars)
	var data = {
		id: id,
		type: type,
		code: code,
		title: title,
		ogrn: ogrn,
		inn: inn,
		kpp: kpp,
		address: address,
		phone: phone,
		email: email,
		last_name: last_name,
		first_name: first_name,
		middle_name: middle_name,
		user_email: user_email,
		user_phone: user_phone
	};
	var location = { section: 'contractor', action: 'edit' };
	// ajax (call)
	request({ location: location, data: data }, function(result) {
		// update
		if (result.errors) m_errors(result.errors);
		else {
			if (scroll_top() > 100) scroll_to(document.body, 3);
			setTimeout(function() {
				html('info_note_text', result.note);
				add_class('info_note', 'active');
			}, 500);
		}
	});
}

function company_delete(id, offset, query) {
	// vars
	var data = { id: id, offset: offset, query: query };
	var location = { section: 'contractor', action: 'delete' };
	// ajax (call)
	request({ location: location, data: data }, function(result) {
		html('companies_table', result.html);
		html('paginator_wrap', result.paginator);
	});
}

function contractor_type_change() {
	if (gv('i_type') == 3) add_class('contractors_connect_wrap', 'active');
	else remove_class('contractors_connect_wrap', 'active');
}

function contractor_connect_search(user_id) {
	// vars
	var query = gv('contractor_connect_title');
	// validate
	if (!query) {
		remove_class('contractors_connect_list', 'active');
		return false;
	}
	// vars (call)
	var data = { user_id: user_id, query: query, type: 1 };
	var location = { section: 'contractor', action: 'connect_search' };
	// call
	request({ location: location, data: data }, function(result) {
		html('contractors_connect_list', result.html);
		add_class('contractors_connect_list', 'active');
	});
}

function contractor_connect_select(user_id, company_id) {
	// actions
	remove_class('contractors_connect_list', 'active');
	// vars
	var data = { user_id: user_id, company_id: company_id };
	var location = { section: 'contractor', action: 'connect_add' };
	// call
	request({ location: location, data: data }, function(result) {
		html('connected_contractors', result.html);
		sv('contractor_connect_title', '');
		ef('contractor_connect_title');
	});
}

function contractor_connect_delete(id) {
	// vars
	var data = { id: id };
	var location = { section: 'contractor', action: 'connect_delete' };
	// call
	request({ location: location, data: data }, function(result) {
		re('contractor_' + id);
	});
}

// USERS

function user_search(cid) {
	in_progress = true;
	setTimeout(function() { user_search_do(cid); }, 200);
}

function user_search_do(cid) {
	// vars
	var query = gv('search_query');
	// call (vars)
	var data = { cid: cid, query: query };
	var location = { section: 'user', action: 'search' };
	// call
	request({ location: location, data: data }, function(result) {
		// actions
		html('users_table', result.html);
		html('paginator_wrap', result.paginator);
		in_progress = false;
		// update
		if (query) {
			replace_class('search_sub', 'icon_search', 'icon_delete');
			add_class('search_sub', 'active');
			attr('search_sub', 'onclick', 'user_search_clear(' + cid + ');')
		} else {
			replace_class('search_sub', 'icon_delete', 'icon_search');
			remove_class('search_sub', 'active');
			attr('search_sub', 'onclick', '')
		}
	});
}

function user_search_clear(cid) {
	sv('search_query', '');
	user_search(cid);
}

function user_edit(company_id, user_id) {
	// vars
	var last_name = gv('i_last_name');
	var first_name = gv('i_first_name');
	var middle_name = gv('i_middle_name');
	var email = gv('i_email');
	var phone = gv('i_phone');
	var password = gv('i_password');
	// vars (call)
	var data = {
		company_id: company_id,
		user_id: user_id,
		last_name: last_name,
		first_name: first_name,
		middle_name: middle_name,
		email: email,
		phone: phone,
		password: password
	};
	var location = { section: 'user', action: 'edit' };
	// call
	request({ location: location, data: data }, function(result) {
		// update
		if (result.errors) m_errors(result.errors);
		else {
			if (scroll_top() > 100) scroll_to(document.body, 3);
			html('info_note_text', result.note);
			add_class('info_note', 'active');
		}
	});
}

function user_block(user_id) {
	// vars
	var data = { user_id: user_id };
	var location = { section: 'user', action: 'block' };
	// call
	request({ location: location, data: data }, function(result) {
		if (result.blocked) remove_class('user_blocked_'+ user_id, 'disabled');
		else add_class('user_blocked_' + user_id, 'disabled')
	});
}

function user_delete(cid, user_id, offset, query) {
	// vars
	var data = { cid: cid, user_id: user_id, offset: offset, query: query };
	var location = { section: 'user', action: 'delete' };
	// call
	request({ location: location, data: data }, function(result) {
		// actions
		html('users_table', result.html);
		html('paginator_wrap', result.paginator);
		// history
		var url = './auth?sub=users';
		if (cid) url += '&cid=' + cid;
		if (offset) url += '&offset=' + offset;
		if (query) url += '&query=' + query;
		window.history.pushState('', '', url);
	});
}
