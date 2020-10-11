add_event(document, 'DOMContentLoaded', function() { label.init(); });

var label = {

	// INIT

	init: function() {

	},

	// COMMON

	edit: function(label_id) {
		window.location = '/labels?act=edit&id=' + label_id;
	},

	update: function(mode, label_id) {
		// vars
		var title = gv('title');
		var type_title = gv('type_title');
		var type_id = gv('type_id');
		var company_title = gv('company_title');
		var size_width = gv('size_width');
		var size_height = gv('size_height');
		var size_code = gv('size_code');
		var size_row = gv('size_row');
		var options = label.options_list();
		// vars (call)
		var data = {
			id: label_id,
			title: title,
			type_title: type_title,
			type_id: type_id,
			company_title: company_title,
			size_width: size_width,
			size_height: size_height,
			size_code: size_code,
			size_row: size_row,
			options: options
		};
		var location = { dpt: 'label', sub: 'common', act: 'update' };
		// call
		request({ location: location, data: data }, function(result) {
			html('preview', result.preview);
			if (mode == 'type') html('params', result.params);
			if (mode == 'type') html('params_optional', result.params_optional);
			var zoom = 0.5;
			var margin_left = -size_width * zoom * 0.5 * 10;
			var margin_top = -size_height * zoom * 0.5 * 10;
			set_style('label_preview', 'marginLeft', margin_left);
			set_style('label_preview', 'marginTop', margin_top);
		});
	},

	save: function(label_id) {
		// vars
		var title = gv('title');
		var type_id = gv('type_id');
		var company_title = gv('company_title');
		var size_width = gv('size_width');
		var size_height = gv('size_height');
		var size_code = gv('size_code');
		var size_row = gv('size_row');
		var options = label.options_list();
		// validate
		var val = true;
		if (!title) { input_error_show('title'); val = false; }
		if (!company_title) { input_error_show('company_title'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = {
			label_id: label_id,
			title: title,
			type_id: type_id,
			company_title: company_title,
			size_width: size_width,
			size_height: size_height,
			size_code: size_code,
			size_row: size_row,
			options: options
		};
		var location = { dpt: 'label', sub: 'common', act: 'save' };
		// call
		request({ location: location, data: data }, function(result) {
			window.location = '/labels';
		});
	},

	delete: function(label_id) {
		// vars
		var data = { label_id: label_id };
		var location = { dpt: 'label', sub: 'common', act: 'delete' };
		// call
		request({ location: location, data: data }, function(result) {
			window.location = window.location.href;
		});
	},

	size_update: function(el, dir, label_id) {
		// vars
		var val = gv('size_' + el) * 10;
		var min = 0;
		var max = 0;
		// vars (els)
		var el_parent = ge('size_' + el).parentNode.parentNode;
		var el_minus = qs('div:nth-of-type(1) > div', el_parent);
		var el_plus = qs('div:nth-of-type(3) > div', el_parent);
		// vars (step)
		var step = 10;
		if (el == 'row') step = 1;
		// vars (min/max)
		if (el == 'width') { min = 600; max = 1000; }
		if (el == 'height') { min = 300; max = 800; }
		if (el == 'code') { min = 130; max = 300; }
		if (el == 'row') { min = 30; max = 70; }
		// change
		if (dir == 'minus') val -= step;
		if (dir == 'plus') val -= -step;
		// validate
		if (dir == 'minus' && val <= min) { val = min; add_class(el_minus, 'disabled'); }
		else remove_class(el_minus, 'disabled');
		if (dir == 'plus' && val >= max) { val = max; add_class(el_plus, 'disabled'); }
		else remove_class(el_plus, 'disabled');
		// update
		sv('size_' + el, val / 10);
		label.update(label_id);
	},

	option_add: function() {
		var a = '';
		a += '<div class="label_edit_option">';
		a += '<div>';
		a += '<input type="text" class="input_primary label_edit_option_title" placeholder="Наименование" oninput="label.update(\'common\');">';
		a += '</div>';
		a += '<div>';
		a += '<div onclick="label.option_sort(this, \'up\');"><i class="icon icon_expand"></i></div>';
		a += '<div onclick="label.option_sort(this, \'down\');"><i class="icon icon_expand"></i></div>';
		a += '</div>';
		a += '</div>';
		append('label_edit_options', a);
	},

	options_list: function() {
		var options = [];
		qs_all('.label_edit_option').forEach(function(option) {
			var option_title = qs('.label_edit_option_title', option).value;
			var option_title_print = option_title;
			if (option_title) options.push([option_title, option_title_print]);
		});
		return options;
	},

	option_sort: function(el, dir) {
		// vars (curr)
		var curr = el.parentNode.parentNode;
		// up
		if (dir == 'up') {
			var prev = el_prev(curr);
			if (prev) { before(prev, curr.outerHTML); re(curr); }
		}
		// down
		if (dir == 'down') {
			var next = el_next(curr);
			if (next) { after(next, curr.outerHTML); re(curr); }
		}
		// update
		label.update('common');
	},

}