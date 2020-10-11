add_event(document, 'DOMContentLoaded', function() { owner.init(); });

var owner = {

	init: function() {
		// vars
		owner.count_notifications = global.count_notifications;
		owner.offset = global.offset;
		// actions
		add_event(document, 'mousedown touchstart', owner.notifications_popup_auto_hide);
	},

	notifications_paginator: function(offset) {
		// vars (call)
		var data = { offset: offset };
		var location = { dpt: 'owner', sub: 'notifications', act: 'paginator' };
		// call
		request({ location: location, data: data }, function(result) {
			html('notifications_table', result.notifications);
			html('notifications_paginator', result.paginator);
		});
	},

	notifications_popup_toggle: function() {
		toggle_class('notifications_button_item', 'active');
		toggle_class('notifications_popup', 'active');
	},

	notifications_popup_auto_hide: function(e) {
		if (has_class('notifications_popup', 'active')) {
			var t = e.target || e.srcElement;
			if (t.offsetParent.className != 'notifications_popup active' && t.className != 'icon icon_bell' && t.className != 'top_menu_item active') {
				remove_class('notifications_button_item', 'active');
				remove_class('notifications_popup', 'active');
			}
		}
	},

	notifications_show_all: function() {
		document.location.href = '/notifications';
	}
}