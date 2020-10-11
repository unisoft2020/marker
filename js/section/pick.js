add_event(document, 'DOMContentLoaded', function() { pick.init(); });

var pick = {
	
	// INIT
	
	init: function() {

	},
	
	// COMMON
	
	paginator: function(offset) {
		// vars
		var data = { offset: offset };
		var location = { dpt: 'pick', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			// common
			html('picks_table', result.info);
			html('picks_paginator', result.paginator);
			// url
			var url = '/picks';
			var p = [];
			if (offset) p.push('offset=' + offset);
			if (p.length > 0) url += '?' + p.join('&');
			window.history.pushState('', '', url);
		});
	},
	
	expand: function(pick_id) {
		if (has_class('pick_details_' + pick_id, 'active')) {
			remove_class('pick_preview_' + pick_id, 'active');
			remove_class('pick_details_' + pick_id, 'active');
			set_style('pick_details_' + pick_id, 'height', 0);
		} else {
			add_class('pick_preview_' + pick_id, 'active');
			add_class('pick_details_' + pick_id, 'active');
			var height = ge('pick_details_wrap_' + pick_id).offsetHeight + 12;
			set_style('pick_details_' + pick_id, 'height', height);
		}
	},
	
	export: function(pick_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { pick_id: pick_id };
		var location = { dpt: 'pick', sub: 'common', act: 'export' };
		// call
		request_file({ location: location, data: data }, function(result) {
			download_file('export.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', result);
		});
	},
	
	archive_toggle: function(pick_id, status, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { pick_id: pick_id, status: status };
		var location = { dpt: 'pick', sub: 'common', act: 'archive_toggle' };
		// call
		request({ location: location, data: data }, function(result) {
			html('picks_table', result.info);
			html('picks_paginator', result.paginator);
		});
	},
	
}