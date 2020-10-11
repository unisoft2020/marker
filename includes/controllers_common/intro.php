<?php

	function controller_intro($section) {
		// vars
		$preload = isset($_COOKIE['preload']) && is_numeric($_COOKIE['preload']) ? $_COOKIE['preload'] : 1;
		Session::set_cookie_intro($preload);
		// output
		HTML::assign('title', Intro::page_title($section));
		HTML::assign('preload', $preload);
		HTML::assign('async', false);
		HTML::assign('section', $section);
		HTML::assign('section_content', './partials/intro/'.$section.'.html');
		HTML::display('./partials/intro/index.html');
		exit();
	}

?>