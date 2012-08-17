<?php

/**
 * Store the config setting of case_repository block
 *
 * @autor 		Gert Sauertstein (in block_case_repository.php)
 * @maintainer	Andre Scherl (got it out of block_case_repository for better handling with external config file)
 *
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/dmllib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->dirroot.'/blocks/case_repository/ilms_config.php');

/** Validiert und speichert die vom Administrator geänderten Einstellungen für die globale Konfigurationsseite in einer Konfigdatei. */
        
// Eingegebene Werte prüfen
//global $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $REUSE_LEARNER_ACTIONS;

if($data=data_submitted()){
	if(!is_numeric($data->YELLOW_MARKUP_LIMIT) || $data->YELLOW_MARKUP_LIMIT < 0.0 || $data->YELLOW_MARKUP_LIMIT > 1.0) {
	    error("Invalid value $data->YELLOW_MARKUP_LIMIT for YELLOW_MARKUP_LIMIT, must be a floting-point value in range [0.0, 1.0]");
	}        
	if(!is_numeric($data->MAX_ILMS_CASE_COUNT) || ceil($data->MAX_ILMS_CASE_COUNT) != floor($data->MAX_ILMS_CASE_COUNT) || $data->MAX_ILMS_CASE_COUNT < 1) {
	    error("Invalid value $data->MAX_ILMS_CASE_COUNT for MAX_ILMS_CASE_COUNT, must be a number greater than or equal to 1");
	}        
	if(!is_numeric($data->REPLACE_CASE_SIMILARITY_LIMIT) || $data->REPLACE_CASE_SIMILARITY_LIMIT < 0.0 || $data->REPLACE_CASE_SIMILARITY_LIMIT > 1.0) {
	    error("Invalid value $data->REPLACE_CASE_SIMILARITY_LIMIT for REPLACE_CASE_SIMILARITY_LIMIT, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_LEARNERMETA) || $data->WEIGHT_FACTOR_LEARNERMETA < 0.0 || $data->WEIGHT_FACTOR_LEARNERMETA > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_LEARNERMETA for WEIGHT_FACTOR_LEARNERMETA, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_ACTIVITYMETA) || $data->WEIGHT_FACTOR_ACTIVITYMETA < 0.0 || $data->WEIGHT_FACTOR_ACTIVITYMETA > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_ACTIVITYMETA for WEIGHT_FACTOR_ACTIVITYMETA, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_FOLLOW_RELATIONS) || $data->WEIGHT_FACTOR_FOLLOW_RELATIONS < 0.0 || $data->WEIGHT_FACTOR_FOLLOW_RELATIONS > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_FOLLOW_RELATIONS for WEIGHT_FACTOR_FOLLOW_RELATIONS, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_CURRENT_ACTIVITY) || $data->WEIGHT_FACTOR_CURRENT_ACTIVITY < 0.0 || $data->WEIGHT_FACTOR_CURRENT_ACTIVITY > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_CURRENT_ACTIVITY for WEIGHT_FACTOR_CURRENT_ACTIVITY, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_HISTORY) || $data->WEIGHT_FACTOR_HISTORY < 0.0 || $data->WEIGHT_FACTOR_HISTORY > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_HISTORY for WEIGHT_FACTOR_HISTORY, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_STATES) || $data->WEIGHT_FACTOR_STATES < 0.0 || $data->WEIGHT_FACTOR_STATES > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_STATES for WEIGHT_FACTOR_STATES, must be a floting-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->HALF_VALUE_TIME) || intval($data->HALF_VALUE_TIME) < 1) {
	    error("Invalid value ".intval($data->HALF_VALUE_TIME)." for HALF_VALUE_TIME, must be an positive integral value");
	}      
	if(!is_numeric($data->LEAK_LIMIT) || $data->LEAK_LIMIT < 0.0 || $data->LEAK_LIMIT > 1.0) {
	    error("Invalid value $data->LEAK_LIMIT for LEAK_LIMIT, must be a floating-point value in range [0.0, 1.0]");
	}      
	if(!is_numeric($data->WEIGHT_FACTOR_STATES) || $data->WEIGHT_FACTOR_STATES < 0.0 || $data->WEIGHT_FACTOR_STATES > 1.0) {
	    error("Invalid value $data->WEIGHT_FACTOR_STATES for WEIGHT_FACTOR_STATES, must be a floting-point value in range [0.0, 1.0]");
	}      
	$sum = $data->WEIGHT_FACTOR_LEARNERMETA+$data->WEIGHT_FACTOR_ACTIVITYMETA+$data->WEIGHT_FACTOR_FOLLOW_RELATIONS+$data->WEIGHT_FACTOR_CURRENT_ACTIVITY+$data->WEIGHT_FACTOR_HISTORY+$data->WEIGHT_FACTOR_STATES;
	if(abs($sum - 1.0) > 1.0e-8) {
	    error("The sum of all CASE weights must be exactly 1.0 (100 %), but is $sum");
	}
	$WEIGHTS_LEARNERMETA2 = array();
	$sum = 0.0;
	foreach($WEIGHTS_LEARNERMETA as $a => $v) {
		$field = 'WEIGHTS_LEARNERMETA_'.$a;
	    $value = $data->$field;
	    if(!is_numeric($value) || $value < 0.0 || $value > 1.0) {
	        error("Invalid value $value for WEIGHTS_LEARNERMETA[$a], must be a floting-point value in range [0.0, 1.0]");
	    }
	    $sum += $value;
	    if($sum - 1.0 > 1.0e-8) {
	    	error("Invalid values for WEIGHTS_LEARNERMETA: The sum of all weights must be exactly 1.0 (100 %), but is $sum or more");
	    }
	    $WEIGHTS_LEARNERMETA2[$a] = $v;
	}
	if(abs($sum - 1.0) > 1.0e-8) {
	    error("Invalid values for WEIGHTS_LEARNERMETA: The sum of all weights must be exactly 1.0 (100 %), but is $sum");
	}
	$WEIGHTS_ACTIVITYMETA2 = array();
	$sum = 0.0;
	foreach($WEIGHTS_ACTIVITYMETA as $a => $v) {
	    $field = 'WEIGHTS_ACTIVITYMETA_'.$a;
	    $value = $data->$field;
	    if(!is_numeric($value) || $value < 0.0 || $value > 1.0) {
	        error("Invalid value $value for WEIGHTS_ACTIVITYMETA[$a], must be a floting-point value in range [0.0, 1.0]");
	    }
	    $sum += $value;
	    if($sum - 1.0 > 1.0e-8) {
	        error("Invalid values for WEIGHTS_ACTIVITYMETA: The sum of all weights must be exactly 1.0 (100 %), but is $sum or more");
	    }
	    $WEIGHTS_ACTIVITYMETA2[$a] = $v;
	}
	if(abs($sum - 1.0) > 1.0e-8) {
	    error("Invalid values for WEIGHTS_ACTIVITYMETA: The sum of all weights must be exactly 1.0 (100 %), but is $sum");
	}
	$REUSE_LEARNER_ACTIONS2 = array();
	foreach($REUSE_LEARNER_ACTIONS as $a => $v) {
	    	$field = 'REUSE_LEARNER_ACTIONS_DIFFTYPE_'.$a;
	        $condition = $data->$field;
	        $field = 'REUSE_LEARNER_ACTIONS_LIMIT_'.$a;
	        $limit = $data->$field;
	        $field = 'REUSE_LEARNER_ACTIONS_COMPARETO_'.$a;
	        $compareto = $data->$field;
	        $field = 'REUSE_LEARNER_ACTIONS_ACTION_'.$a;
	        $action = $data->$field;
	        $field = 'REUSE_LEARNER_ACTIONS_STRENGTH_'.$a;
	        $strength = $data->$field;
	        if(!is_numeric($limit) || $limit < 0.0 || $limit > 1.0) {
	            error("Invalid value $limit for REUSE_LEARNER_ACTIONS_LIMIT (attribute $a): Adjustment limit must be a numeric value between 0.0 and 1.0");
	        }
	        if($condition != 'none' && $condition != 'lt' && $condition != 'gt' && $condition != 'diff') {
	        	error('Invalid enumeration value for REUSE_LEARNER_ACTIONS_DIFFTYPE');
	        }
	        if($compareto != 'learner' && $compareto != 'activity') {
	            error('Invalid enumeration value for REUSE_LEARNER_ACTIONS_COMPARETO');
	        }
	        if($action != 'reject' && $action != 'adjust') {
	            error('Invalid enumeration value for REUSE_LEARNER_ACTIONS_ACTION');
	        }
	        if($action == 'adjust') {
	            if(!is_numeric($strength) || $strength < 0.0 || $strength > 1.0) {
	                error("Invalid value $strength for REUSE_LEARNER_ACTIONS_LIMIT (attribute $a): Adjustment limit must be a numeric value between 0.0 and 1.0");
	            }
	        }
	        $REUSE_LEARNER_ACTIONS2[$a]['DIFFTYPE'] = $condition;
	        $REUSE_LEARNER_ACTIONS2[$a]['LIMIT'] = $limit;
	        $REUSE_LEARNER_ACTIONS2[$a]['COMPARETO'] = $compareto;
	        $REUSE_LEARNER_ACTIONS2[$a]['ACTION'] = $action;
	        $REUSE_LEARNER_ACTIONS2[$a]['STRENGTH'] = $strength;
	}
	
	if(!save_iLMS_config_file(intval($data->HALF_VALUE_TIME), $data->LEAK_LIMIT, $data->MAX_ILMS_CASE_COUNT, $data->REPLACE_CASE_SIMILARITY_LIMIT, $data->YELLOW_MARKUP_LIMIT, $data->WEIGHT_FACTOR_LEARNERMETA, $data->WEIGHT_FACTOR_ACTIVITYMETA, $data->WEIGHT_FACTOR_FOLLOW_RELATIONS, $data->WEIGHT_FACTOR_CURRENT_ACTIVITY, $data->WEIGHT_FACTOR_HISTORY, $data->WEIGHT_FACTOR_STATES, $WEIGHTS_LEARNERMETA2, $WEIGHTS_ACTIVITYMETA2, isset($data->ENABLE_REUSE_ADJUSTMENTS) && $data->ENABLE_REUSE_ADJUSTMENTS == 'true', $REUSE_LEARNER_ACTIONS2)) {
		print_error("Changes could not be saved.");
	}else{
		redirect("{$CFG->wwwroot}/blocks/case_repository/settings_case_repository.php");
	}
}


