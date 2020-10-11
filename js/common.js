add_event(document, 'DOMContentLoaded', function() { common.init(); });

// PAGE

var common = {
	
	// vars
	
	modal_progress: false,
	modal_open: false,
	
	// common
	
	init: function() {
		add_event(document, 'mousedown touchstart', common.auto_hide_modal);
		add_event(window, 'resize', common.window_resize);
		add_event(document, 'click', function() { common.menu_popup_hide_all('inactive', event); common.results_hide('inactive', event); });
		add_event(document, 'scroll', function() { common.menu_popup_hide_all('all', event); common.results_hide('all', event); });
		common.scroll_fix();
	},
	
	menu_toggle: function() {
		if (has_class('content', 'short')) {
			remove_class('content', 'short');
			set_cookie('menu', '1', '31536000', '/');
		} else {
			add_class('content', 'short');
			set_cookie('menu', '0', '31536000', '/');
		}
	},
	
	menu_popup_toggle: function(el, e) {
		var el = qs('.menu_popup', el);
		if (has_class(el, 'active') && !e.target.closest('.menu_popup')) remove_class(el, 'active');
		else { common.menu_popup_hide_all('all'); add_class(el, 'active'); }
		cancel_event(e);
	},
	
	menu_popup_hide_all: function(mode, e) {
		qs_all('.menu_popup.active').forEach(function(el) {
			if (mode == 'all' || !e.target.closest('.menu_popup')) remove_class(el, 'active');
		})
	},
	
	window_resize: function() {
		if (has_class('modal', 'visible')) common.modal_resize();
		common.scroll_fix();
	},
	
	scroll_fix: function() {
		if (has_class('modal', 'visible')) {
			var width = window.innerWidth - scrollbar_width() - 0.6;
		} else {
			var w1 = window.innerWidth - scrollbar_width() - 0.6;
			var w2 = document.body.offsetWidth - 0.6;
			var width = Math.max(w1, w2);
		}
		if (ge('scroll_fix')) set_style('scroll_fix', 'width', width);
		if (ge('top_menu')) set_style('top_menu', 'width', width);
	},
	
	// modal

	modal_show: function(width, content) {
		// progress
		if (common.modal_progress) return false;
		// width
		var display_width = w_width();
		if (width > display_width - 20) width = display_width - 40;
		// active
		add_class('modal', 'active');
		common.modal_open = true;
		set_style('modal_content', 'width', width);
		set_style(document.body, 'overflow', 'hidden');
		// actions
		html('modal_content', content);
		common.modal_resize();
	},

	modal_hide: function() {
		// progress
		if (common.modal_progress) return false;
		common.modal_progress = true;
		// update
		set_style('modal_container', 'overflow', 'hidden');
		remove_class('modal', 'active');
		html('modal_content', '');
		set_style('modal_container', 'overflow', '');
		set_style(document.body, 'overflow', '');
		common.scroll_fix();
		common.modal_progress = false;
		common.modal_open = false;
	},

	modal_resize: function() {
		// vars
		var h_display = window.innerHeight;
		var h_content = ge('modal_content').clientHeight;
		var k = (h_content*100/h_display > 85) ? 0.5 : 0.25;
		var margin = (h_display-h_content)*k;
		if (margin < 20) margin = 20;
		// update
		ge('modal_content').style.marginTop = margin + 'px';
		ge('modal_content').style.height = 'auto';
	},
	
	// common

	auto_hide_modal: function(e) {
		if (has_class('modal', 'active')) {
			var t = e.target || e.srcElement;
			if (t.id == 'modal_overlay') on_click('modal_close');
		}
	},
	
	results_toggle: function(el, e) {
		var el_parent = el.parentElement;
		var el_results = qs('.results_container', el_parent);
		if (has_class(el_results, 'active')) remove_class(el_results, 'active');
		else { common.results_hide('all'); add_class(el_results, 'active'); }
	},
	
	result_select: function(item, value, id, mode) {
		// vars
		var el_parent = item.parentElement.parentElement;
		var el_parent_hide = item.parentElement;
		var el_value = qs('input[type="text"]', el_parent);
		var el_id = qs('input[type="hidden"]', el_parent);
		// actions
		sv(el_value, value);
		sv(el_id, id);
		// hide
		var el_results = qs('.results_container', el_parent_hide);
		remove_class(el_results, 'active');
	},

	results_hide: function(mode, e) {
		qs_all('.results_container.active').forEach(function(el) {
			if (mode == 'all' || !e.target.closest('.selector')) remove_class(el, 'active');
		})
	},
	
	// search
	
	search: function() {
		// vars
		var query = gv('search');
		// vars (call)
		var data = { query: query };
		var location = { dpt: 'common', sub: 'search', act: 'do' };
		// call
		request({ location: location, data: data }, function(result) {
			html('main_content', result.html);
			// history
			var url = '/search';
			if (query) url += '?q=' + query;
			window.history.replaceState('', '', url);
		});
	},

}