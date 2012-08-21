<?php
	$plugin->version  = 2012082100; // Plugin version
	$plugin->cron = 1; // Set min time between cron executions in seconds
	$plugin->requires = 2010112400; // Moodle 2.0
	$plugin->component = 'block_case_repository';
	$plugin->release = '1.1 (Build: 2012082100)';
	$plugin->dependencies = array('block_semantic_web' => 2012082100, 'block_user_preferences' => 2012082100);