add_event(document, 'DOMContentLoaded', function() { support.init(); });

var support = {

	// INIT

	init: function() {
		support.status = global.status;
	},

	// COMMON

	paginator: function(offset) {
		// vars
		var data = { offset: offset, status: support.status };
		var location = { dpt: 'support', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('support_table', result.support);
			html('support_paginator', result.paginator);
		});
	},

	create_window: function() {
		// vars
		var location = { dpt: 'support', sub: 'common', act: 'create_window' };
		// call
		request({ location: location }, function(result) {
			common.modal_show(420, result.html);
			ef('title');
		});
	},

	create_update: function() {
		// vars
		var title = gv('title');
		var msg = gv('msg');
		// validate
		var val = true;
		if (title.length < 2) { input_error_show('title'); val = false; }
		if (msg.length < 2) { input_error_show('msg'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { title: title, msg: msg };
		var location = { dpt: 'support', sub: 'common', act: 'create_update' };
		// call
		request({ location: location, data: data }, function(result) {
			document.location.href = '/support';
			common.result_select(ge('type_id'), 'Открытые', 0, 'full');
			html('support_table', result.support);
			html('support_paginator', result.paginator);
			window.history.pushState('', '', '/support');
			common.modal_hide();
		});
	},

	send_message: function(ticket_id) {
		// vars
		var msg = gv('msg');
		// validate
		var val = true;
		if (msg.length < 2) { input_error_show('msg'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { ticket_id: ticket_id, msg: msg };
		var location = { dpt: 'support', sub: 'common', act: 'send_message' };
		// call
		request({ location: location, data: data }, function(result) {
			html('support_ticket_table', result.messages);
			sv('msg', '');
		});
	},

	close_ticket: function(ticket_id, page, e) {
		// actions
		cancel_event(e);
		// vars
		var data = { ticket_id: ticket_id, page: page };
		var location = { dpt: 'support', sub: 'common', act: 'close_ticket' };
		// call
		request({ location: location, data: data }, function(result) {
			if (page == 'support') { html('support_table', result.support); html('support_paginator', result.paginator); }
			else { html('support_ticket_table', result.messages); }
		});
	},

	ticket_details: function(ticket_id) {
		document.location.href = '/support/' + ticket_id;
	},

	archive_toggle: function(status) {
		if (status == 'active') window.history.pushState('', '', '/support');
		if (status == 'archive') window.history.pushState('', '', '/support?sub=archive');
		if (support.status != status) { support.status = status; support.paginator(0); }
	},
}