add_event(document, 'DOMContentLoaded', function() { intro.init(); });

var intro = {

	// vars

	modal_progress: false,
	modal_open: false,
	path: '',

	// common

	init: function() {
		// vars
		intro.path = global.path;
		// actions
		add_event(document, 'mousedown touchstart', intro.auto_hide_modal);
		intro.preloader_timeout();
		if (intro.path == 'login') ef('login');
	},

	// modal

	modal_show: function(width, content) {
		// progress
		if (intro.modal_progress) return false;
		// width
		var display_width = w_width();
		//if (width > display_width - 20)
		width = display_width * 0.7;
		// active
		add_class('modal', 'active');
		intro.modal_open = true;
		set_style('modal_content', 'width', width);
		set_style(document.body, 'overflow', 'hidden');
		// actions
		html('modal_content', content);
		var frame = qs('#modal_content iframe');
		set_style(frame, 'minHeight', width * 0.5625);
		intro.modal_resize();
	},

	modal_hide: function() {
		// progress
		if (intro.modal_progress) return false;
		intro.modal_progress = true;
		// update
		set_style('modal_container', 'overflow', 'hidden');
		remove_class('modal', 'active');
		html('modal_content', '');
		set_style('modal_container', 'overflow', '');
		set_style(document.body, 'overflow', '');
		intro.modal_progress = false;
		intro.modal_open = false;
	},

	modal_resize: function() {
		// vars
		var h_display = window.innerHeight;
		var h_content = ge('modal_content').clientHeight;
		var margin = (h_display - h_content) * 0.5;
		if (margin < 20) margin = 20;
		// update
		ge('modal_content').style.marginTop = margin + 'px';
		ge('modal_content').style.height = 'auto';
	},

	auto_hide_modal: function(e) {
		if (!has_class('modal', 'active')) return false;
		var t = e.target || e.srcElement;
		if (t.id == 'modal_overlay') on_click('modal_close');
	},

	// sections

	video_window: function(id) {
		// vars
		var data = { id: id };
		var location = { dpt: 'common', sub: 'intro', act: 'video_window' };
		// call
		request({ location: location, data: data }, function(result) {
			intro.modal_show(640, result.html);
		});
	},

	question_toggle: function(el) {
		// vars
		var desc = qs('.question_desc', el);
		var wrap = qs('.question_desc div', el);
		// update
		if (has_class(el, 'active')) {
			remove_class(el, 'active');
			set_style(desc, 'height', 0);
		} else {
			add_class(el, 'active');
			set_style(desc, 'height', ge(wrap).offsetHeight);
		}
	},

	footer_form_submit: function(el) {
		add_class(el, 'footer_submit_icon_disabled');
		// vars
		var email = gv('footer_email');
		// validate
		if (!is_email(email)) { show_error('footer_email'); remove_class(el, 'footer_submit_icon_disabled'); return false; }
		// vars (call)
		var data = { email: email };
		var location = { dpt: 'common', sub: 'intro', act: 'send_demo' };
		// call
		request({ location: location, data: data }, function() {
			// swap blocks
			hide('footer_submit');
			set_style('footer_submit_success', 'display', 'inline-block');
			// reset inputs value
			sv('footer_email', '');
			// reset label class
			toggle_input_class('footer_email');
			// undisabled button
			remove_class(el, 'footer_submit_icon_disabled');
		});
	},

	return_footer_form: function() {
		hide('footer_submit_success');
		set_style('footer_submit', 'display', 'inline-block');
	},

	submit_form: function(el) {
		show_loading(el);
		// vars (values)
		var name = gv('name');
		var email = gv('email');
		var phone = gv('phone');
		var comment = gv('comment');
		var subscribe = ge('checkbox').checked ? 'Да' : 'Нет';
		// validate
		var val = true;
		if (name.length < 2) { show_error('name'); val = false; }
		if (!is_email(email)) { show_error('email'); val = false; }
		// not required (optional)
		if ((!is_phone(phone) && phone.length > 0) || (phone.length > 0 && phone.length < 10)) { show_error('phone'); val = false; }
		if (comment.length > 0 && comment.length < 2) { show_error('comment'); val = false; }
		if (!val) { hide_loading(el); return false; }
		// vars (call)
		var data = { full_name: name, email: email, phone: phone, comment: comment, subscribe: subscribe };
		var location = { dpt: 'common', sub: 'intro', act: 'send_request' };
		// call
		request({ location: location, data: data }, function() {
			// reset inputs value
			sv('name', '');
			sv('email', '');
			sv('phone', '');
			sv('comment', '');
			ge('checkbox').checked = false;
			// reset input class
			toggle_input_class('name');
			toggle_input_class('email');
			toggle_input_class('phone');
			toggle_input_class('comment');
			// Hide laoding (reset button state on default)
			hide_loading(el);
			add_class(el, 'button_success');
			html(el, 'Успешно<i class="icon button_icon"></i>');
			// swap button state
			setTimeout(function() {
				remove_class(el, 'button_success');
				html(el, 'Оставить заявку');
			}, 4000);
		});
	},

	toggle_menu: function() {
		toggle_class('mobile-menu', 'mobile_menu_active');
		toggle_class('burger', 'active');
		setTimeout(function() { toggle_class('header', 'header_dark') }, 350)
		toggle_class('body', 'body_fixed');
	},

	preloader_timeout: function() {
		setTimeout(function() {
			remove_class('body', 'body_fixed');
		}, 2000);
	},

	checkbox_toggle: function(el) {
		ge(el).checked = !ge(el).checked;
	},

	type_toggle: function(this_el, el) {
		// vars
		var attr_val = attr(el, 'type');
		// actions
		toggle_class(this_el, 'login_eye_active');
		attr_val === 'password' ? attr(el, 'type', 'text') : attr(el, 'type', 'password');
	},


	change_page: function(section) {
		// vars
		var data = { section: section };
		var location = { dpt: 'common', sub: 'intro', act: 'change_page' };
		// call
		request({ location: location, data: data }, function(result) {
			html('intro_content', result.html);
			document.title = result.title;
			window.history.pushState('', '', result.url);
			remove_class('body', 'body_fixed');
		});
	},

	login_submit: function(el) {
		// vars
		var login = gv('login');
		var password = gv('password');
		var short = ge('short').checked ? 0 : 1;
		// validate
		var val = true;
		if (!is_email(login) && !is_phone(login)) { show_error('login'); val = false; }
		if (password.length < 4) { show_error('password'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { login: login, password: password, short: short };
		var location = { dpt: 'owner', sub: 'common', act: 'login' };
		// call
		request({ location: location, data: data }, function(result) {
			if (result.error_data) {
				set_style('login_error', 'opacity', 1);
				html('login_error', result.error_msg);
			} else {
				window.location = '/';
			}
		});
	},

	new_password: function() {
		// vars
		var password = gv('password');
		var token = global.token;
		// validate
		var val = true;
		if (password.length < 4) { show_error('password'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { password: password, token: token };
		var location = { dpt: 'owner', sub: 'common', act: 'new_password' };
		// call
		request({ location: location, data: data }, function(result) {
			if (result.errors) {
				set_style('login_error', 'opacity', 1);
				html('login_error', result.errors.common);
			} else {
				window.location = '/';
			}
		});
	},

	password_restore_window: function(event) {
		cancel_event(event);
		// vars
		var location = { dpt: 'owner', sub: 'common', act: 'password_restore_window' };
		// call
		request({ location: location }, function(result) {
			common.modal_show(420, result.html);
			ef('email');
		});
	},

	password_restore: function() {
		// vars
		var email = gv('email');
		// validate
		var val = true;
		if (!is_email(email)) { input_error_show('email'); val = false; }
		if (!val) { hide_loading(el); return false; }
		// vars (call)
		var data = { login: email };
		var location = { dpt: 'owner', sub: 'common', act: 'password_restore' };
		// call
		request({ location: location, data: data }, function(result) {
			result.errors ? html('password_restore_note', '<div style="font-size: 14px; line-height: 18px;">' + result.errors.login + '</div>') : html('password_restore_note', '<div style="font-size: 14px; line-height: 18px;">Ссылка для восстановления пароля отправлена на указанный электронный адрес</div>');
		});
	},

	send_request_window: function(event) {
		cancel_event(event);
		// vars
		var location = { dpt: 'owner', sub: 'common', act: 'send_request_window' };
		// call
		request({ location: location }, function(result) {
			common.modal_show(420, result.html);
			ef('full_name');
		});
	},

	send_request: function() {
		// vars (values)
		var full_name = gv('full_name');
		var company = gv('company');
		var email = gv('email');
		var phone = gv('phone');
		// validate
		var val = true;
		if (full_name.length < 2) { input_error_show('full_name'); val = false; }
		if (company.length < 2) { input_error_show('company'); val = false; }
		if (!is_email(email)) { input_error_show('email'); val = false; }
		if (!is_phone(phone)) { input_error_show('phone'); val = false; }
		if (!val) return false;
		// vars (call)
		var data = { full_name: full_name, company: company, email: email, phone: phone };
		var location = { dpt: 'owner', sub: 'common', act: 'send_request' };
		// call
		request({ location: location, data: data }, function() {
			html('send_request', '<div style="font-size: 14px; line-height: 18px;">Данные отправлены, ожидайте ответного сообщения на почте, которую Вы указали!</div>');
		});
	},
};

function toggle_input_class(el) {
	gv(el).length > 0 ? add_class(el, 'active') : remove_class(el, 'active');
}

function hide_error(el) {
	remove_class(el, 'error');
}

function show_error(el) {
	add_class(el, 'error');
}

function show_loading(el) {
	add_class(el, 'button_loading');
}

function hide_loading(el) {
	remove_class(el, 'button_loading');
}

function change_range() {
	// vars
	var sum = 0;
	// vars (values)
	var range_doc_val = gv('calculator_range_doc');
	var range_otg_val = gv('calculator_range_otg');
	// set new values
	html('range_doc_title', range_doc_val + ' <span>шт</span>');
	html('range_otg_title', range_otg_val + ' <span>шт</span>');
	// sum
	sum = (20 * range_doc_val * range_otg_val) / 1024;
	// check sum and set needed information
	if (sum > 50 && sum < 100) {
		html('calc_title', 'Стандарт');
		html('calc_price', '50000');
		html('calc_volume', '<div class="calculator_card_item">Файловое хранилище 100 Гб</div><div class="calculator_card_item">Доп. функциональность</div>')
	} else if (sum > 100) {
		html('calc_title', 'Премиум');
		html('calc_price', '200000');
		html('calc_volume', '<div class="calculator_card_item">Файловое хранилище 500 Гб</div><div class="calculator_card_item">Доп. функциональность</div><div class="calculator_card_item">Кастомизация под заказчика</div>');
	} else {
		html('calc_title', 'Базовый');
		html('calc_price', '25000');
		html('calc_volume', '<div class="calculator_card_item">Файловое хранилище 50 Гб</div>')
	}
}