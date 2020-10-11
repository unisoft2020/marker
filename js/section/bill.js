add_event(document, 'DOMContentLoaded', function() { bill.init(); });

var bill = {
	
	// INIT
	
	init: function() {

	},
	
	// COMMON
	
	paginator: function(offset) {
		// vars
		var data = { offset: offset };
		var location = { dpt: 'bill', sub: 'common', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('bills_table', result.bills);
			html('bills_paginator', result.paginator);
		});
	},
	
	create_window: function() {
		// vars
		var location = { dpt: 'bill', sub: 'common', act: 'create_window' };
		// call
		request({ location: location }, function(result) {
			common.modal_show(420, result.html);
			ef('amount');
		});
	},
	
	create_update: function() {
		// vars
		var amount = gv('amount');
		// validate
		var val = true;
		if (!amount || !is_positive(amount) || amount < 1000) { input_error_show('amount'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { amount: amount };
		var location = { dpt: 'bill', sub: 'common', act: 'create_update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('bills_table', result.bills);
			html('bills_paginator', result.paginator);
			common.modal_hide();
		});
	},
	
	delete: function(bill_id, e) {
		// actions
		cancel_event(e);
		common.menu_popup_hide_all('all');
		// vars
		var data = { bill_id: bill_id };
		var location = { dpt: 'bill', sub: 'common', act: 'delete' };
		// call
		request({ location: location, data: data }, function(result) {
			html('bills_table', result.bills);
			html('bills_paginator', result.paginator);
			common.modal_hide();
		});
	}
	
}