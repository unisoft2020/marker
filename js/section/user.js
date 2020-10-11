add_event(document, 'DOMContentLoaded', function() { user.init(); });

var user = {
	
	// INIT
	
	init: function() {

	},
	
	// COMMON
	
	paginator: function(offset, query) {
		// vars
		var data = { offset: offset };
		var location = { dpt: 'user', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('users_table', result.users);
			html('users_paginator', result.paginator);
		});
	},
	
	edit_window: function(user_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { user_id: user_id };
		var location = { dpt: 'user', sub: 'common', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
			ge('modal_container').scrollTop = 0;
			ef('last_name');
		});
	},
	
	edit_update: function(user_id) {
		// vars
		var first_name = gv('first_name');
		var last_name = gv('last_name');
		var middle_name = gv('middle_name');
		var email = gv('email');
		var phone = gv('phone');
		var group_id = gv('group_id');
		var password = gv('password');
		var occupation = gv('occupation');
		var note = gv('note');
		// validate
		var val = true;
		if (!first_name) { input_error_show('first_name'); val = false; }
		if (!last_name) { input_error_show('last_name'); val = false; }
		if (!email) { input_error_show('email'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = {
			user_id: user_id,
			first_name: first_name,
			last_name: last_name,
			middle_name: middle_name,
			email: email,
			phone: phone,
			group_id: group_id,
			password: password,
			occupation: occupation,
			note: note
		};
		var location = { dpt: 'user', sub: 'common', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('users_table', result.users);
			html('users_paginator', result.paginator);
			html('owner_name', result.owner_name);
			if (result.password) common.modal_show(420, result.password);
			else common.modal_hide();
		});
	},

	sort: function(sort) {
		// vars
		var new_sort = sort == 'asc' ? 'dsc' : 'asc';
		// vars (call)
		var data = { sort: sort };
		var location = { dpt: 'user', sub: 'common', act: 'sort' };
		// call
		request({ location: location, data: data }, function(result) {
			html('users_table', result.users);
			html('users_paginator', result.paginator);
			attr('users_sort', 'onclick', 'user.sort(\'' + new_sort + '\');');
			replace_class('users_sort', 'icon_sort_' + sort, 'icon_sort_' + new_sort);
		});
	},
	
	block: function(user_id, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { user_id: user_id, status: status };
		var location = { dpt: 'user', sub: 'common', act: 'block' };
		// call
		request({ location: location, data: data }, function(result) {
			html('users_table', result.users);
			html('users_paginator', result.paginator);
		});
	},
	
	delete_window: function(user_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { user_id: user_id }
		var location = { dpt: 'user', sub: 'common', act: 'delete_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
		});
	},
	
	delete_update: function(user_id) {
		// vars
		var data = { user_id: user_id };
		var location = { dpt: 'user', sub: 'common', act: 'delete_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('users_table', result.users);
			html('users_paginator', result.paginator);
			common.modal_hide();
		});
	},
	
}