<?php
	$plugin->version  = 2013051800; // Plugin version
	$plugin->cron = 1; // Set min time between cron executions in seconds
	$plugin->requires = 2010112400; // Moodle 2.0
	$plugin->component = 'block_case_repository';
	$plugin->release = '1.2 (Build: 2013032300)';
	$plugin->dependencies = array('block_semantic_web' => 2013031700, 'block_user_preferences' => 2013031700);