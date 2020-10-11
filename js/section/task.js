add_event(document, 'DOMContentLoaded', function() { task.init(); });

var task = {
	
	// INIT
	
	init: function() {
		task.company_id = global.company_id;
		task.products = global.task_products;
		task.users = global.task_users;
	},
	
	// COMMON
	
	save: function(task_id) {
		// vars
		var title = gv('title');
		var date_end = gv('date_end');
		var d = date_end.split('.');
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (!date_end) { input_error_show('date_end'); val = false; }
		else if (!is_date(d[0], d[1], d[2])) { input_error_show('date_end'); val = false; }
		else if (!is_future_date(d[0], d[1], d[2])) { input_error_show('date_end'); val = false; }
		if (!task.users.length) { input_error_show('selected_users_blank'); val = false; }
		if (!task.products.length) { input_error_show('selected_products_blank'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { task_id: task_id, title: title, date_end: date_end, users: task.users, products: task.products };
		var location = { dpt: 'task', sub: 'common', act: 'edit' };
		// call
		request({ location: location, data: data }, function(result) {
			window.location = '/tasks';
		});
	},
	
	delete: function(task_id) {
		// vars
		var data = { task_id: task_id };
		var location = { dpt: 'task', sub: 'common', act: 'delete' };
		// call
		request({ location: location, data: data }, function(result) {
			window.location = window.location.href;
		});
	},
	
	// MODAL (USERS)
	
	user_add_window: function() {
		// vars
		var data = { company_id: task.company_id, users: task.users };
		var location = { dpt: 'task', sub: 'users', act: 'add_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
		});
	},
	
	user_search: function() {
		// vars
		var query = gv('modal_search');
		// vars (call)
		var data = { company_id: task.company_id, users: task.users, query: query };
		var location = { dpt: 'task', sub: 'users', act: 'search' };
		// call
		request({ location: location, data: data }, function(result) {
			html('modal_search_results', result.html);
		});
	},
	
	user_toggle: function(user_id, user_name) {
		task.users.indexOf(user_id) != -1 ? task.user_delete(user_id) : task.user_add(user_id, user_name);
	},
	
	user_add: function(user_id, user_name) {
		add_class('modal_user_' + user_id, 'active');
		task.users.push(user_id);
		re('selected_users_blank');
		append('selected_users', '<div id="selected_user_' + user_id + '" class="selected_users_item">' + user_name + '<i class="icon icon_delete_3" onclick="task.user_delete(' + user_id + ');"></i></div>');
	},
	
	user_delete: function(user_id) {
		remove_class('modal_user_' + user_id, 'active');
		task.users.splice(task.users.indexOf(user_id), 1);
		re('selected_user_' + user_id);
		if (!task.users.length) html('selected_users', '<div id="selected_users_blank" class="selected_empty">Сотрудники еще не назначены. Используйте выпадающий список для назначения исполнителей по этому заданию.</div>');
	},
	
	// MODAL (PRODUCTS)
	
	product_add_window: function() {
		// vars
		var data = { company_id: task.company_id, products: task.products };
		var location = { dpt: 'task', sub: 'products', act: 'add_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
		});
	},
	
	product_search: function() {
		// vars
		var query = gv('modal_search');
		// vars (call)
		var data = { company_id: task.company_id, products: task.products, query: query };
		var location = { dpt: 'task', sub: 'products', act: 'search' };
		// call
		request({ location: location, data: data }, function(result) {
			html('modal_search_results', result.html);
		});
	},
	
	product_toggle: function(product_id, product_title) {
		task.products.indexOf(product_id) != -1 ? task.product_delete(product_id) : task.product_add(product_id, product_title);
	},
	
	product_add: function(product_id, product_title) {
		add_class('modal_product_' + product_id, 'active');
		task.products.push(product_id);
		re('selected_products_blank');
		append('selected_products', '<div id="selected_product_' + product_id + '" class="selected_products_item">' + product_title + '<i class="icon icon_delete_3" onclick="task.product_delete(' + product_id + ');"></i></div>');
	},
	
	product_delete: function(product_id) {
		remove_class('modal_product_' + product_id, 'active');
		task.products.splice(task.products.indexOf(product_id), 1);
		re('selected_product_' + product_id);
		if (!task.products.length) html('selected_products', '<div id="selected_products_blank" class="selected_empty">Изделия не добавлены. Используйте выпадающий список для назначения изделий этому заданию.</div>');
	},

}