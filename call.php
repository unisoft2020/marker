<?php

	// INIT

	if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') echo '';
	
	require('./cfg/general.inc.php');
	require('./includes/core/functions_general.php');
	require('./includes/core/functions_security.php');
	require('./includes/core/functions_validate.php');
	require './vendor/autoload.php';
	
	class_autoload();
	controllers_call();
	DB::connect();

	// VARS
		
	$location = isset($_POST['location']) ? flt_input($_POST['location']) : NULL;
	$data = isset($_POST['data']) ? flt_input($_POST['data']) : NULL;

	$dpt = isset($location['dpt']) ? $location['dpt'] : NULL;
	$sub = isset($location['sub']) ? $location['sub'] : NULL;
	$act = isset($location['act']) ? $location['act'] : NULL;	
		
	// SESSION

	Session::init(1);

	$owner = User::user_info(Session::$user_id);
	HTML::assign('owner', $owner);

	// ROUTE
		
	Route::route_call($dpt, $sub, $act, $data);
?>