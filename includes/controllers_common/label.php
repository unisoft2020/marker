<?php
	
	function controller_labels($data) {
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// info
		$labels = Label::labels_list($data);
		// output
		HTML::assign('labels', $labels);
		return HTML::main_content('./partials/section/labels/labels.html', Session::$mode);
	}

	function controller_label_edit($data) {
		// vars
		$id = isset($data['id']) && is_numeric($data['id']) ? $data['id'] : 0;
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// info
		$label = Label::label_info($id);
		$types = Label::label_type();
		// output
		HTML::assign('label', $label);
		HTML::assign('types', $types);
		HTML::assign('label_preview', HTML::fetch('./partials/print/label_'.$label['type_id'].'.html'));
		return HTML::main_content('./partials/section/labels/label_edit.html', Session::$mode);
	}

?>