<?php

	function controller_dashboard($sub, $act, $data) {
		
		if ($sub == 'stats') {
			if ($act == 'export') return Stats::stats_export();
		}
	
	}

?>