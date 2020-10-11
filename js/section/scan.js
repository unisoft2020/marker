add_event(document, 'DOMContentLoaded', function() { scan.init(); });

var scan = {

	// INIT

	init: function() {

	},

	// COMMON

	paginator: function(offset) {
		// vars
		var query = gv('scans_search');
		// vars (call)
		var data = { offset: offset, query: query };
		var location = { dpt: 'scan', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			// common
			html('scans_table', result.info);
			html('scans_paginator', result.paginator);
			// url
			var url = '/scans';
			var p = [];
			if (offset) p.push('offset=' + offset);
			if (query) p.push('q=' + query);
			if (p.length > 0) url += '?' + p.join('&');
			window.history.pushState('', '', url);
		});
	},

	sort: function(value) {
		// vars
		var query = gv('scans_search').trim().substr(0, 64);
		// vars (call)
		var data = { value: value, query: query };
		var location = { dpt: 'scan', sub: 'common', act: 'sort' };
		// call
		request({ location: location, data: data }, function(result) {
			html('scans_table', result.info);
			html('scans_paginator', result.paginator);
		});
	},

	show_product: function(product_id) {
		document.location.href = '/products/' + product_id;
	},
	
}