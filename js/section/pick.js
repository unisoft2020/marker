add_event(document, 'DOMContentLoaded', function() { pick.init(); });

var pick = {

	// INIT

	init: function() {
        this.checkInput();
	},

	// COMMON

	paginator: function(offset) {
		// vars
		var query = gv('picks_search');
		// vars (call)
		var data = { offset: offset, query: query };
		var location = { dpt: 'pick', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			// common
			html('picks_table', highlight(result.info, query));
			html('picks_paginator', highlight(result.paginator, query));
			// url
			var url = '/picks';
			var p = [];
			if (offset) p.push('offset=' + offset);
			if (query) p.push('q=' + query);
			if (p.length > 0) url += '?' + p.join('&');
			window.history.pushState('', '', url);
		});
        this.checkInput();
	},
    
    checkInput: function(){
        setTimeout(function(){
            if(gv('picks_search').length > 0){
                remove_class(qs("#picks_search").nextElementSibling, "icon_search");
                add_class(qs("#picks_search").nextElementSibling, "icon_delete");
                qs("#picks_search").nextElementSibling.id = "picks_remove";
                if (qs("#picks_remove").addEventListener) {
                    add_event("picks_remove", 'click', function(){
                        location = "/picks";
                    });
                }
            }else{
                remove_class(qs("#picks_search").nextElementSibling, "icon_delete");
                add_class(qs("#picks_search").nextElementSibling, "icon_search");
                qs("#picks_search").nextElementSibling.id = "";
            }
        }, 200);
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

	sort: function(value) {
		// vars
		var query = gv('picks_search').trim().substr(0, 64);
		// vars (call)
		var data = { value: value, query: query };
		var location = { dpt: 'pick', sub: 'common', act: 'sort' };
		// call
		request({ location: location, data: data }, function(result) {
			html('picks_table', result.info);
			html('picks_paginator', result.paginator);
		});
	},

};