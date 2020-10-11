add_event(document, 'DOMContentLoaded', function() { settings.init(); });

var settings = {
	
	// INIT
	
	init: function() {

	},
	
	// COMMON
	
	update: function() {
		// vars
		var pick_email = gv('pick_email');
		var pick_server = gv('pick_server');
		// vars (call)
		var data = {pick_email: pick_email, pick_server: pick_server};
		var location = { dpt: 'settings', sub: 'common', act: 'update' };
		// call
		request({ location: location, data: data }, function(result) {
			document.location.href='/settings';
		});
	},
	
}