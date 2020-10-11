add_event(document, 'DOMContentLoaded', function() { product.init(); });

var product = {

	// INIT

	init: function() {
		product.group_id = global.group_id;
		product.group_status = global.group_status;
		product.offset = global.offset;
		product.group_offset = 0;
		product.group_file_products_offset = 0;
		product.group_file_id = 0;
		product.drag_id = 0;
		product.drag_p = '';
		product.drag_s = [0, 0];
		product.drag_progress = false;
		product.drag_scroll_y = 0;
		product.drag_scroll_old = 0;
		product.drag_x = 0;
		product.drag_y = 0;
	},

	// COMMON

	group_change: function(el, group_id) {
		// vars
		var data = { group_id: group_id, group_status: product.group_status };
		var location = { dpt: 'product', sub: 'group', act: 'change' };
		// call
		request({ location: location, data: data }, function(result) {
			// common
			html('products_table', result.html);
			html('products_paginator', result.paginator);
			remove_class_by_class('group_item', 'active');
			add_class(el, 'active');
			// history
			var url = '/products';
			var p = [];
			if (product.group_status == 'archive') p.push('sub=' + product.group_status);
			if (group_id) p.push('group_id=' + group_id);
			if (p.length > 0) url += '?' + p.join('&');
			window.history.pushState('', '', url);
			// update
			product.group_id = group_id;
			product.offset = 0;
		});
	},

	product_paginator: function(offset, group_id, query, mode) {
		// vars
		var data = { offset: offset, group_id: group_id, query: query };
		var location = { dpt: 'product', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			// common
			html('products_table', result.html);
			html('products_paginator', result.paginator);
			// url
			if (mode != 'update') {
				var url = '/products';
				var p = [];
				if (offset) p.push('offset=' + offset);
				if (group_id > 0) p.push('group_id=' + group_id);
				if (query) p.push('query=' + query);
				if (p.length > 0) url += '?' + p.join('&');
				window.history.pushState('', '', url);
				product.offset = offset;
			}
		});
	},

	group_paginator: function(offset) {
		// vars
		var group_status = product.group_status == 'active' ? 0 : 1;
		// vars (call)
		var data = { offset: offset, group_id: product.group_id, group_status: group_status };
		var location = { dpt: 'product', sub: 'group', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.html);
			html('groups_paginator', result.paginator);
			product.group_offset = offset;
		});
	},

	product_details: function(product_id) {
		if (product.drag_progress) return false;
		document.location.href = '/products/' + product_id;
	},

	// EDIT

	status_toggle: function(el, product_id, status, e) {
		// actions
		if (product.group_status == 'archive') return false;
		cancel_event(e);
		// vars
		var status = status == 1 ? 0 : 1;
		// vars (call)
		var data = { product_id: product_id, status: status };
		var location = { dpt: 'product', sub: 'common', act: 'status_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			if (status == 1) replace_class(el, 'wait', 'complete');
			else replace_class(el, 'complete', 'wait');
			var str = status == 1 ? 'Отмаркировано' : 'Запланировано';
			html(el, str);
			attr(el, 'onclick', 'product.status_toggle(this, ' + product_id + ', ' + status + ', event);');
		});
	},

	history: function(product_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { product_id: product_id };
		var location = { dpt: 'product', sub: 'common', act: 'history' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(480, result.html);
			ge('modal_container').scrollTop = 0;
		});
	},

	copy_window: function(product_id, mode, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { product_id: product_id, mode: mode };
		var location = { dpt: 'product', sub: 'common', act: 'copy' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(800, result.html);
			ge('modal_container').scrollTop = 0;
			ef('title');
		});
	},

	edit_window: function(product_id, mode, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { product_id: product_id, mode: mode, group_id: product.group_id };
		var location = { dpt: 'product', sub: 'common', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(800, result.html);
			ge('modal_container').scrollTop = 0;
			ef('title');
		});
	},

	edit_update: function(product_id, mode) {
		// vars
		var code = gv('code');
		var title = gv('title');
		var destination = gv('destination');
		var quantity = gv('quantity');
		var customer_id = gv('customer_id');
		var customer_title = gv('customer_title');
		var consignee_id = gv('consignee_id');
		var contract_id = gv('contract_id');
		var label_id = gv('label_id');
		var produced = gv('produced');
		var shipped = gv('shipped');
		var options = product.options_list();
		// handling
		var dp = produced.split('.');
		var ds = shipped.split('.');
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (!customer_title) { input_error_show('customer_title'); val = false; }
		if (produced && !is_date(dp[0], dp[1], dp[2])) { input_error_show('produced'); val = false; }
		if (shipped && !is_date(ds[0], ds[1], ds[2])) { input_error_show('shipped'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = {
			product_id: product_id,
			code: code,
			title: title,
			destination: destination,
			quantity: quantity,
			produced: produced,
			shipped: shipped,
			customer_id: customer_id,
			consignee_id: consignee_id,
			contract_id: contract_id,
			label_id: label_id,
			options: options,
			offset: product.offset,
			group_id: product.group_id,
			group_offset: product.group_offset
		};
		var location = { dpt: 'product', sub: 'common', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			if (mode == 'list') {
				html('groups_table', result.groups.html);
				html('groups_paginator', result.groups.paginator);
				html('products_table', result.products.html);
				html('products_paginator', result.products.paginator);
				common.modal_hide();
			} else {
				//document.location.href='/products?group_id=' + product.group_id;
				document.location.href = '/products/' + product_id;
			}
		});
	},

	print: function(product_id, e) {
		cancel_event(e);
		common.menu_popup_hide_all('all');
		window.open('/products/' + product_id + '?act=print', '_blank');
	},

	sort: function(mode) {
		// vars
		var data = { mode: mode, group_id: product.group_id };
		var location = { dpt: 'product', sub: 'common', act: 'sort' };
		// call
		request({ location: location, data: data }, function(result) {
			// common
			html('products_table', result.products.html);
			html('products_paginator', result.products.paginator);
			// sort
			if (mode == 'asc') {
				attr('products_sort', 'onclick', 'product.sort(\'dsc\');');
				replace_class('products_sort', 'icon_sort_asc', 'icon_sort_dsc');
			} else {
				attr('products_sort', 'onclick', 'product.sort(\'asc\');');
				replace_class('products_sort', 'icon_sort_dsc', 'icon_sort_asc');
			}
			// history
			var url = '/products';
			var p = [];
			if (product.group_status == 'archive') p.push('sub=' + product.group_status);
			if (group_id) p.push('group_id=' + product.group_id);
			if (p.length > 0) url += '?' + p.join('&');
			window.history.pushState('', '', url);
		});
	},

	delete_window: function(product_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { product_id: product_id }
		var location = { dpt: 'product', sub: 'common', act: 'delete_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
		});
	},

	delete_update: function(product_id, e) {
		// actions
		cancel_event(e);
		// vars
		var data = {
			product_id: product_id,
			offset: product.offset,
			group_id: product.group_id,
			group_offset: product.group_offset
		};
		var location = { dpt: 'product', sub: 'common', act: 'delete_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.groups.html);
			html('groups_paginator', result.groups.paginator);
			html('products_table', result.products.html);
			html('products_paginator', result.products.paginator);
			common.modal_hide();
		});
	},

	status_change: function(product_id) {
		// vars (call)
		var data = {
			product_id: product_id,
			offset: product.offset,
			group_id: product.group_id,
			group_offset: product.group_offset
		};
		var location = { dpt: 'product', sub: 'common', act: 'status_change' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.groups.html);
			html('groups_paginator', result.groups.paginator);
			html('products_table', result.products.html);
			html('products_paginator', result.products.paginator);
		});
	},

	archive_toggle: function(status) {
		if (status == 'active') {
			remove_class('add_group', 'disabled');
			remove_class('add_product', 'disabled');
			attr('add_group', 'onclick', 'product.group_edit_window(0);');
			attr('add_product', 'onclick', 'product.edit_window(0, \'list\');');
			window.history.pushState('', '', '/products');
		}
		if (status == 'archive') {
			add_class('add_group', 'disabled');
			add_class('add_product', 'disabled');
			attr('add_group', 'onclick', '');
			attr('add_product', 'onclick', '');
			window.history.pushState('', '', '/products?sub=archive');
		}
		product.group_status = status;
		product.group_id = -2;
		product.group_paginator(0);
		product.product_paginator(0, -2, '', 'update');
	},

	// GROUP (COMMON)

	group_edit_window: function(group_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { group_id: group_id };
		var location = { dpt: 'product', sub: 'group', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
			ef('title');
		});
	},

	group_edit_update: function(group_id) {
		// vars
		var title = gv('title');
		var customer_id = gv('customer_id');
		var contract_id = gv('contract_id');
		var label_id = gv('label_id');
		// vars (call)
		var data = {
			group_id: group_id,
			title: title,
			customer_id: customer_id,
			contract_id: contract_id,
			label_id: label_id,
			offset: product.group_offset
		};
		var location = { dpt: 'product', sub: 'group', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.html);
			common.modal_hide();
			if (!group_id) product.group_change('group_' + result.group_id, result.group_id);
		});
	},

	group_archive_toggle: function(group_id, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { group_id: group_id, status: status, group_status: product.group_status, offset: product.group_offset };
		var location = { dpt: 'product', sub: 'group', act: 'archive_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.html);
			html('groups_paginator', result.paginator);
		});
	},

	group_files_window: function(group_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { group_id: group_id };
		var location = { dpt: 'product', sub: 'group', act: 'files_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(480, result.html);
			ge('modal_container').scrollTop = 0;
			product.group_file_products_offset = 0;
			product.group_file_id = 0;
		});
	},

	group_files_product_paginator: function(offset, group_id, mode) {
		// vars
		var query = gv('modal_search');
		// vars (call)
		var data = { offset: offset, group_id: group_id, query: query, group_file_id: product.group_file_id };
		var location = { dpt: 'product', sub: 'group', act: 'files_paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('group_files_products_table', result.html);
			html('group_files_products_paginator', result.paginator);
			product.group_file_products_offset = offset;
		});
	},

	group_files_product_toggle: function(el, product_id, group_id) {
		// actions
		if (!has_class(el, 'active') && product.group_file_id) { add_class(el, 'active'); var status = 'add'; }
		else { remove_class(el, 'active'); var status = 'delete'; }
		// validate
		if (!product.group_file_id) return false;
		// vars (call)
		var data = { group_file_id: product.group_file_id, product_id: product_id, group_id: group_id, status: status };
		var location = { dpt: 'product', sub: 'file', act: 'group_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			console.log(result);
		});
	},

	group_files_upload: function(group_id) {
		// vars
		var data = { group_id: group_id };
		var location = { dpt: 'product', sub: 'file', act: 'group_upload' };
		// upload
		create_upload('product.group_files_upload_do', 'tmp_file', location, data, true);
	},

	group_files_upload_do: function() {
		// call
		request_upload(function(result) {
			add_class('group_files_uploads', 'active');
			html('group_files_uploads', result.html);
		});
	},

	group_files_upload_toggle: function(el, file_id, group_id) {
		if (has_class(el, 'active')) {
			remove_class(el, 'active');
			product.group_file_id = 0;
		} else {
			remove_class_by_class('group_files_upload', 'active');
			add_class(el, 'active');
			product.group_file_id = file_id;
		}
		product.group_files_product_paginator(product.group_file_products_offset, group_id, 'group_files');
		//console.log(product.group_file_id);
	},

	group_files_delete: function(group_id, group_file_id) {
		// vars
		var data = { group_id: group_id, group_file_id: group_file_id };
		var location = { dpt: 'product', sub: 'file', act: 'group_delete' };
		// call
		request({ location: location, data: data }, function(result) {
			if (!result.files_count) remove_class('group_files_uploads', 'active');
			html('group_files_uploads', result.html);
		});
	},

	group_export: function(group_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { group_id: group_id };
		var location = { dpt: 'product', sub: 'group', act: 'export' };
		// call
		request_file({ location: location, data: data }, function(result) {
			download_file('export.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', result);
		});
	},

	group_print: function(group_id, e) {
		cancel_event(e);
		common.menu_popup_hide_all('all');
		window.open('/products/' + group_id + '?act=print_group', '_blank');
	},

	group_delete_window: function(group_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { group_id: group_id }
		var location = { dpt: 'product', sub: 'group', act: 'delete_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
		});
	},

	group_delete_update: function(group_id, e) {
		// actions
		if (group_id == product.group_id) {
			product.group_id = 0;
			window.history.pushState('', '', '/products');
		}
		// vars
		var data = {
			group_id: group_id,
			group_cur_id: product.group_id,
			group_offset: product.group_offset,
			group_status: product.group_status,
			product_offset: product.offset
		};
		var location = { dpt: 'product', sub: 'group', act: 'delete_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.groups.html);
			html('groups_paginator', result.groups.paginator);
			html('products_table', result.products.html);
			html('products_paginator', result.products.paginator);
			common.modal_hide();
		});
	},

	// GROUP (DRAG)

	drag_start: function(id, e) {
		// actions
		common.menu_popup_hide_all('all');
		if (e.which != 1) return false;
		// vars (drag)
		product.drag_p = ge('product_' + id).getBoundingClientRect();
		product.drag_s = [e.clientX - product.drag_p.left, e.clientY - product.drag_p.top];
		product.drag_id = id;
		// events
		add_event(document, 'mousemove', product.drag_move);
		add_event(document, 'mouseup', product.drag_stop);
		add_event(window, 'scroll', product.drag_scroll);
		product.drag_scroll_old = document.documentElement.scrollTop;
	},

	drag_move: function(e) {
		// vars
		product.drag_x = e.clientX;
		product.drag_y = e.clientY;
		var x = product.drag_x - product.drag_p.left - product.drag_s[0];
		var y = product.drag_y - product.drag_p.top - product.drag_s[1] - product.drag_scroll_y;
		// start
		if ((Math.abs(x) > 3 || Math.abs(y) > 3) && !has_class('product_' + product.drag_id, 'drag')) {
			// vars
			var width = ge('product_' + product.drag_id).offsetWidth;
			var height = ge('product_' + product.drag_id).offsetHeight;
			product.drag_progress = true;
			// styles
			add_class('product_' + product.drag_id, 'drag');
			set_style('product_' + product.drag_id, 'width', width);
			set_style('product_' + product.drag_id, 'height', height);
		}
		// move
		if (has_class('product_' + product.drag_id, 'drag')) set_style('product_' + product.drag_id, 'transform', 'translate(' + x + 'px, ' + y + 'px)');
	},

	drag_scroll: function(e) {
		var delta = product.drag_scroll_old - document.documentElement.scrollTop;
		if (has_class('product_' + product.drag_id, 'drag')) {
			product.drag_scroll_y += delta;
			var x = product.drag_x - product.drag_p.left - product.drag_s[0];
			var y = product.drag_y - product.drag_p.top - product.drag_s[1] - product.drag_scroll_y;
			set_style('product_' + product.drag_id, 'transform', 'translate(' + x + 'px, ' + y + 'px)');
		}
		product.drag_scroll_old = document.documentElement.scrollTop;
	},

	drag_stop: function(e) {
		// events
		remove_event(document, 'mousemove', product.drag_move);
		remove_event(document, 'mouseup', product.drag_stop);
		remove_event(window, 'scroll', product.drag_scroll);
		// styles
		remove_class('product_' + product.drag_id, 'drag');
		set_style('product_' + product.drag_id, 'transform', 'inherit');
		set_style('product_' + product.drag_id, 'width', 'inherit');
		set_style('product_' + product.drag_id, 'height', 'inherit');
		// add
		var el = document.elementFromPoint(e.clientX, e.clientY).closest('.droppable');
		if (el) {
			var group_id = el.id.split('_')[1];
			if (group_id && product.group_id != group_id) product.drag_add(group_id, product.drag_id);
		}
		// click
		var x = e.clientX - product.drag_p.left - product.drag_s[0];
		var y = e.clientY - product.drag_p.top - product.drag_s[1];
		if (x < 5 && y < 5) product.product_details(product.drag_id);
		// clear
		product.drag_id = 0;
		product.drag_x = 0;
		product.drag_y = 0;
		product.drag_scroll_y = 0;
		product.drag_scroll_old = 0;
		setTimeout(function() { product.drag_progress = false; }, 500);
	},

	drag_add: function(group_id, product_id) {
		// actions
		re('product_' + product_id);
		// vars
		var data = {
			group_id: group_id,
			product_id: product_id,
			cur_group_id: product.group_id,
			cur_group_offset: product.group_offset,
			cur_product_offset: product.offset
		};
		var location = { dpt: 'product', sub: 'common', act: 'change_group' };
		// call
		request({ location: location, data: data }, function(result) {
			html('groups_table', result.groups.html);
			html('groups_paginator', result.groups.paginator);
			html('products_table', result.products.html);
			html('products_paginator', result.products.paginator);
		});
	},

	// SEARCH (CUSTOMER)

	search_customer: function(el) {
		// vars
		var query = gv('customer_title');
		var el_parent = el.parentElement;
		var el_results = qs('.results_container', el_parent);
		// validate
		if (!query) {
			sv('customer_id', 0);
			remove_class(el_results, 'active');
			return false;
		}
		// vars (call)
		var data = { query: query };
		var location = { dpt: 'product', sub: 'search', act: 'customer' };
		// call
		request({ location: location, data: data }, function(result) {
			html(el_results, result.html);
			common.results_hide('inactive');
			add_class(el_results, 'active');
		});
	},

	search_customer_select: function(id, title) {
		sv('customer_title', title);
		sv('customer_id', id);
		common.results_hide('inactive');
	},

	// SEARCH (CONSIGNEE)

	search_consignee: function(el) {
		// vars
		var query = gv('consignee_title');
		var el_parent = el.parentElement;
		var el_results = qs('.results_container', el_parent);
		// validate
		if (!query) {
			sv('consignee_id', 0);
			remove_class(el_results, 'active');
			return false;
		}
		// vars (call)
		var data = { query: query };
		var location = { dpt: 'product', sub: 'search', act: 'consignee' };
		// call
		request({ location: location, data: data }, function(result) {
			html(el_results, result.html);
			common.results_hide('inactive');
			add_class(el_results, 'active');
		});
	},

	search_consignee_select: function(id, title) {
		sv('consignee_title', title);
		sv('consignee_id', id);
		common.results_hide();
	},

	// SEARCH (CONTRACT)

	search_contract: function(el) {
		// vars
		var query = gv('contract_title');
		var el_parent = el.parentElement;
		var el_results = qs('.results_container', el_parent);
		// validate
		if (!query) {
			sv('contract_id', 0);
			remove_class(el_results, 'active');
			return false;
		}
		// vars (call)
		var data = { query: query };
		var location = { dpt: 'product', sub: 'search', act: 'contract' };
		// call
		request({ location: location, data: data }, function(result) {
			html(el_results, result.html);
			common.results_hide();
			add_class(el_results, 'active');
		});
	},

	search_contract_select: function(id, title) {
		sv('contract_title', title);
		sv('contract_id', id);
		common.results_hide();
	},

	// OPTIONS

	option_add: function(title) {
		// actions
		if (!title) title = '';
		// vars
		var data = { title: title };
		var location = { dpt: 'product', sub: 'option', act: 'add' };
		// call
		request({ location: location, data: data }, function(result) {
			// append
			append('product_edit_options', result.html);
			// not disabled
			qs_all('.product_edit_option_item').forEach(function(op) {
				var el_icon = qs('.product_edit_option_delete i', op);
				remove_class(el_icon, 'disabled');
			});
		});
	},

	option_delete: function(el) {
		// vars
		var options = qs_all('.product_edit_option_item');
		var count = options.length;
		// validate
		if (count == 1) return false;
		// delete
		el = el.parentElement.parentElement;
		re(el);
		// disabled
		if (count == 2) {
			options.forEach(function(op) {
				var el_icon = qs('.product_edit_option_delete i', op);
				add_class(el_icon, 'disabled');
			});
		}
	},

	options_list: function() {
		var options = [];
		qs_all('.product_edit_option_item').forEach(function(op) {
			var op_id = qs('.product_edit_option_id', op).value;
			var op_title = qs('.product_edit_option_title input', op).value;
			var op_value = qs('.product_edit_option_value input', op).value;
			var op_units = qs('.product_edit_option_units input', op).value;
			if (op_title) options.push([op_id, op_title, op_value, op_units]);
		});
		return options;
	},

	options_from_label: function(label_id) {
		// vars
		var product_options = product.options_list();
		// vars (call)
		var data = { label_id: label_id };
		var location = { dpt: 'product', sub: 'common', act: 'options_from_label' };
		// call
		request({ location: location, data: data }, function(result) {
			// update
			result.label_options.forEach(function(label_option) {
				var exist = false;
				product_options.forEach(function(product_option) {
					var regexp = new RegExp('^' + label_option.title + '$', 'iu');
					if (product_option[1].match(regexp)) exist = true;
				});
				if (!exist && label_option.title) product.option_add(label_option.title);
			});
			// remove blank
			setTimeout(function() {
				var els = qs_all('.product_edit_option_item');
				var el_last = els.length - 1;
				els.forEach(function(op, i) {
					var op_title = qs('.product_edit_option_title input', op).value;
					var op_value = qs('.product_edit_option_value input', op).value;
					if (!op_title && !op_value && i != el_last) re(op);
				});
			}, 300);
		});
	},

	// FILES

	files_upload: function(product_id) {
		var data = { product_id: product_id };
		var location = { dpt: 'product', sub: 'file', act: 'upload' };
		create_upload('product.files_upload_do', 'tmp_file', location, data, true);
	},

	files_upload_do: function() {
		// call
		request_upload(function(result) {
			if (result.error) common.modal_show(420, result.error);
			html('product_files', result.html);
		});
	},

	file_delete: function(file_id) {
		// vars
		var data = { file_id: file_id };
		var location = { dpt: 'product', sub: 'file', act: 'delete' };
		// call
		request({ location: location, data: data }, function(result) {
			html('product_files', result.html);
		});
	},

}