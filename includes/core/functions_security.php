<?php

	// COMMON
	
	function flt_input($var) {
		return str_replace(
			[ '\\', "\0", "'", '"', "\x1a", "\x00" ],
			[ '\\\\', '\\0', "\\'", '\\"', '\\Z', '\\Z' ],
			$var);
	}
	
	function unflt_input($var) {
		return str_replace(
			[ '\\\\', '\\0', "\\'", '\\"', '\\Z', '\\Z' ],
			[ '\\', "\0", "'", '"', "\x1a", "\x00" ],
			$var);
	}
	
	function flt_output($str) {
		return htmlentities($str, ENT_QUOTES, 'UTF-8');
	}
	
	function unflt_output($str) {
		$str = html_entity_decode($str);
		return htmlentities($str, ENT_NOQUOTES, 'UTF-8');	
	}
	
	// PASSWORD

	function p_create() {
		return generate_rand_str(12, 'password');
	}

	function p_salt() {
		srand(time());
		$length = rand(5, 10);
		return generate_rand_str($length);
	}
	
	function p_hash($password, $salt) {
		return md5(md5($salt).md5($password));
	}
	
?>