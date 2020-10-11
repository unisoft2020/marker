<?php

	function controller_label($sub, $act, $data) {

		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'update') return Label::label_update($data);
			if ($act == 'save') return Label::label_save($data);
			if ($act == 'delete') return Label::label_delete($data);
		}
	
	}

?>