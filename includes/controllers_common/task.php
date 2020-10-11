<?php
	
	function controller_tasks($data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// vars
		$data['mode'] = 'default';
		$show = isset($data['show']) ? $data['show'] : 'all';
		// info
		$tasks = Task::tasks_list($data);
		// output
		HTML::assign('tasks', $tasks['info']);
		HTML::assign('show', $show);
		return HTML::main_content('./partials/section/tasks/tasks.html', Session::$mode);
	}

	function controller_task_edit($task_id, $data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// info
		$task = Task::task_info($task_id, $data);
		// output
		HTML::assign('task', $task);
		return HTML::main_content('./partials/section/tasks/task_edit.html', Session::$mode);
	}

?>