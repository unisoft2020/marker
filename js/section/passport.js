add_event(document, 'DOMContentLoaded', function() { passport.init(); });

var passport = {
	
	// INIT
	
	init: function() {
		// vars
		passport.offset_active = 0;
		passport.offset_archive = 0;
	},
	
	// COMMON
	
	paginator: function(offset, type, query, mode) {
		// vars
		type == 'active' ? passport.offset_active = offset : passport.offset_archive = offset;
		// vars (call)
		var data = { offset_active: passport.offset_active, offset_archive: passport.offset_archive, mode: mode };
		var location = { dpt: 'passport', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passports_active_table', result.passports_active);
			html('passports_active_paginator', result.paginator_active);
			html('passports_archive_table', result.passports_archive);
			html('passports_archive_paginator', result.paginator_archive);
		});
	},
	
	details: function(passport_id) {
		document.location.href='/passports/' + passport_id;
	},
	
	edit_window: function(passport_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id };
		var location = { dpt: 'passport', sub: 'common', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
			ge('modal_container').scrollTop = 0;
			ef('title');
		});
	},
	
	edit_update: function(passport_id) {
		// vars
		var title = gv('title');
		var number = gv('number');
		var order_number = gv('order_number');
		var type = gv('type_id');
		var produced = gv('produced');
		var dp = produced.split('.');
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (produced && !is_date(dp[0], dp[1], dp[2])) { input_error_show('produced'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = {
			passport_id: passport_id,
			title: title,
			number: number,
			order_number: order_number,
			type: type,
			produced: produced
		};
		var location = { dpt: 'passport', sub: 'common', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passports_active_table', result.passports_active);
			html('passports_active_paginator', result.paginator_active);
			html('passports_archive_table', result.passports_archive);
			html('passports_archive_paginator', result.paginator_archive);
			common.modal_hide();
		});
	},
	
	archive_toggle: function(passport_id, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, status: status };
		var location = { dpt: 'passport', sub: 'common', act: 'archive_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passports_active_table', result.passports_active);
			html('passports_active_paginator', result.paginator_active);
			html('passports_archive_table', result.passports_archive);
			html('passports_archive_paginator', result.paginator_archive);
			common.modal_hide();
		});
	},
	
	sort: function(sort, mode) {
		// vars
		var new_sort = sort == 'asc' ? 'dsc' : 'asc';
		// vars (call)
		var data = { sort: sort, mode: mode };
		var location = { dpt: 'passport', sub: 'common', act: 'sort' };
		// call
		request({ location: location, data: data }, function(result) {
			// active
			if (ge('passports_active_table')) {
				html('passports_active_table', result.passports_active);
				html('passports_active_paginator', result.paginator_active);
				attr('passports_sort_active', 'onclick', 'passport.sort(\'' + new_sort + '\', \'' + mode + '\');');
				replace_class('passports_sort_active', 'icon_sort_' + sort, 'icon_sort_' + new_sort);
			}
			// archive
			if (ge('passports_archive_table')) {
				html('passports_archive_table', result.passports_archive);
				html('passports_archive_paginator', result.paginator_archive);
				attr('passports_sort_archive', 'onclick', 'passport.sort(\'' + new_sort + '\', \'' + mode + '\');');
				replace_class('passports_sort_archive', 'icon_sort_' + sort, 'icon_sort_' + new_sort);
			}
		});
	},
	
	delete_window: function(passport_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id }
		var location = { dpt: 'passport', sub: 'common', act: 'delete_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
		});
	},
	
	delete_update: function(passport_id, e) {
		// actions
		cancel_event(e);
		// vars
		var data = {
			passport_id: passport_id,
			/*offset: product.offset*/
		};
		var location = { dpt: 'passport', sub: 'common', act: 'delete_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passports_active_table', result.passports_active);
			html('passports_active_paginator', result.paginator_active);
			html('passports_archive_table', result.passports_archive);
			html('passports_archive_paginator', result.paginator_archive);
			common.modal_hide();
		});
	},
	
	// WORKER
	
	expand: function(passport_id) {
		if (has_class('passport_details_' + passport_id, 'active')) {
			remove_class('passport_preview_' + passport_id, 'active');
			remove_class('passport_details_' + passport_id, 'active');
			set_style('passport_details_' + passport_id, 'height', 0);
		} else {
			add_class('passport_preview_' + passport_id, 'active');
			add_class('passport_details_' + passport_id, 'active');
			var height = ge('passport_details_wrap_' + passport_id).offsetHeight + 12;
			set_style('passport_details_' + passport_id, 'height', height);
		}
	},
	
	// SECTIONS
	
	section_edit_window: function(passport_id, section_id, mode, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, section_id: section_id, mode: mode };
		var location = { dpt: 'passport', sub: 'section', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
			ge('modal_container').scrollTop = 0;
			ef('title');
		});
	},
	
	section_edit_update: function(passport_id, section_id, mode) {
		// vars
		var title = gv('title');
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { passport_id: passport_id, section_id: section_id, title: title };
		var location = { dpt: 'passport', sub: 'section', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			if (mode == 'list') html('passport_sections', result.html);
			else window.location = window.location.href;
			common.modal_hide();
		});
	},
	
	section_archive_toggle: function(passport_id, section_id, mode, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, section_id: section_id, mode: mode, status: status };
		var location = { dpt: 'passport', sub: 'section', act: 'archive_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passport_sections', result.html);
		});
	},
	
	section_delete_window: function(passport_id, section_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, section_id: section_id }
		var location = { dpt: 'passport', sub: 'section', act: 'delete_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
		});
	},
	
	section_delete_update: function(passport_id, section_id) {
		// vars
		var data = { passport_id: passport_id, section_id: section_id };
		var location = { dpt: 'passport', sub: 'section', act: 'delete_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passport_sections', result.html);
			common.modal_hide();
		});
	},
	
	section_details: function(passport_id, section_id) {
		document.location.href = '/passports/' + passport_id + '?section=' + section_id;
	},
	
	// ANNEXES
	
	annex_edit_window: function(passport_id, annex_id, mode, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, annex_id: annex_id, mode: mode };
		var location = { dpt: 'passport', sub: 'annex', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
			ge('modal_container').scrollTop = 0;
			ef('title');
		});
	},
	
	annex_edit_update: function(passport_id, annex_id, mode) {
		// vars
		var title = gv('title');
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { passport_id: passport_id, annex_id: annex_id, title: title };
		var location = { dpt: 'passport', sub: 'annex', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			if (mode == 'list') html('passport_annexes', result.html);
			else window.location = window.location.href;
			common.modal_hide();
		});
	},
	
	annex_archive_toggle: function(passport_id, annex_id, mode, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, annex_id: annex_id, mode: mode, status: status };
		var location = { dpt: 'passport', sub: 'annex', act: 'archive_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passport_annexes', result.html);
		});
	},
	
	annex_delete_window: function(passport_id, annex_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id, annex_id: annex_id }
		var location = { dpt: 'passport', sub: 'annex', act: 'delete_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(380, result.html);
		});
	},
	
	annex_delete_update: function(passport_id, annex_id) {
		// vars
		var data = { passport_id: passport_id, annex_id: annex_id };
		var location = { dpt: 'passport', sub: 'annex', act: 'delete_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passport_annexes', result.html);
			common.modal_hide();
		});
	},
	
	annex_details: function(passport_id, annex_id) {
		document.location.href = '/passports/' + passport_id + '?annex=' + annex_id;
	},
		
	// FILES
	
	files_list: function(passport_id) {
		document.location.href = '/passports/' + passport_id + '?files';
	},
	
	file_full_list: function(passport_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { passport_id: passport_id };
		var location = { dpt: 'passport', sub: 'file', act: 'list' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(900, result.html);
			ge('modal_container').scrollTop = 0;
		});
	},
	
	files_upload: function(passport_id, sub_id, sub_type, sub_number, template_id, file_id, mode) {
		var data = { passport_id: passport_id, sub_id: sub_id, sub_type: sub_type, sub_number: sub_number, template_id: template_id, file_id: file_id, mode: mode };
		var location = { dpt: 'passport', sub: 'file', act: 'upload' };
		create_upload('passport.files_upload_do', 'tmp_file', location, data, false);
	},

	files_upload_do: function() {
		// call
		request_upload(function(result) {
			if (result.error) common.modal_show(420, result.error);
			if (result.mode == 'common') html('passport_files', result.html);
			if (result.mode == 'worker') {
				html('passport_details_wrap_' + result.passport_id, result.html);
				var height = ge('passport_details_wrap_' + result.passport_id).offsetHeight + 12;
				set_style('passport_details_' + result.passport_id, 'height', height);
			}
		});
	},
	
	file_edit_window: function(file_id, mode, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { file_id: file_id, mode: mode };
		var location = { dpt: 'passport', sub: 'file', act: 'edit_window' };
		// call
		request({ location: location, data: data }, function(result) {
			common.modal_show(420, result.html);
			ge('modal_container').scrollTop = 0;
			ef('title');
		});
	},
	
	file_edit_update: function(file_id, mode) {
		// vars
		var title = gv('title');
		var template_id = gv('template_id');
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { file_id: file_id, title: title, template_id: template_id, mode: mode };
		var location = { dpt: 'passport', sub: 'file', act: 'edit_update' };
		// call
		request({ location: location, data: data }, function(result) {
			if (mode == 'worker') html('passport_details_wrap_' + result.passport_id, result.html);
			else html('passport_files', result.html);
			common.modal_hide();
		});
	},
	
	file_status_update: function(file_id, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { file_id: file_id, status: status };
		var location = { dpt: 'passport', sub: 'file', act: 'status_update' };
		// call
		request({ location: location, data: data }, function(result) {
			if (status == 2) { common.modal_show(420, result.html); ef('note'); }
			else html('passport_files', result.html);
		});
	},
	
	file_revision: function(file_id) {
		// vars
		var data = { file_id: file_id, note: gv('note') };
		var location = { dpt: 'passport', sub: 'file', act: 'revision' };
		// call
		request({ location: location, data: data }, function(result) {
			html('passport_files', result.html);
			common.modal_hide();
		});
	},
	
	file_delete: function(file_id, mode, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { file_id: file_id, mode: mode };
		var location = { dpt: 'passport', sub: 'file', act: 'delete' };
		console.log(file_id, mode);
		// call
		request({ location: location, data: data }, function(result) {
			if (mode == 'worker') {
				console.log(result);
				html('passport_details_wrap_' + result.passport_id, result.html);
				var height = ge('passport_details_wrap_' + result.passport_id).offsetHeight + 12;
				set_style('passport_details_' + result.passport_id, 'height', height);
			} else html('passport_files', result.html);
		});
	},
	
	file_open: function(path) {
		var win = window.open(path, '_blank');
		win.focus();
	},
	
}