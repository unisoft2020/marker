<?php

	// INIT

	require('./cfg/general.inc.php');
	require('./includes/core/functions_general.php');
	require('./includes/core/functions_security.php');
	require('./includes/core/functions_validate.php');

	class_autoload();
	controllers_common();
	DB::connect();	
	
	// VARS

	Session::init();
	$g['user_id'] = Session::$user_id;
	$g['company_id'] = Session::$company_id;
	$g['access'] = Session::$access;
	$g['menu'] = Session::$menu;
	$g['tz'] = Session::$tz;
	$g['sid'] = Session::$sid;
	$g['url'] = flt_input($_SERVER['REQUEST_URI']);
	HTML::assign('search', '');

	Route::init();
	$g['path'] = Route::$path;
	$g['query'] = Route::$query;
	HTML::assign('title', DEFAULT_TITLE);

	$owner = User::user_info(Session::$user_id);

	$notifications = Notification::notifications_list(['limit'=>10, 'offset'=>0]);
	$notifications_read = 0;
	foreach ($notifications['info'] as $key => $notification) {
		if ($notification['unread'] == 0) $notifications_read++;
	}

	Stats::stats_update();

	// OUTPUT
	
	HTML::assign('owner', $owner);
	HTML::assign('notifications_limit', $notifications_read != count($notifications['info']) ? $notifications['info'] : []);
	HTML::assign('notifications_read', $notifications_read);
	HTML::assign('global', $g);
	HTML::display('./partials/index.html'); 
	
?>