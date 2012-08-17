<?php 
/*
 * Parsen der globalen Einstellungen des iLMS Blocks
 *
 * Copyright (C) 2012, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
 
 require_once('../../config.php');
 global $CFG;
 
 require($CFG->dataroot.'/ilms/config.php');
 
 global $MAX_ILMS_CASE_COUNT, $REPLACE_CASE_SIMILARITY_LIMIT, $WEIGHT_FACTOR_LEARNERMETA, $WEIGHT_FACTOR_ACTIVITYMETA, $WEIGHT_FACTOR_FOLLOW_RELATIONS, $WEIGHT_FACTOR_CURRENT_ACTIVITY, $WEIGHT_FACTOR_HISTORY, $WEIGHT_FACTOR_STATES, $HALF_VALUE_TIME, $LEAK_LIMIT, $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $ENABLE_REUSE_ADJUSTMENTS, $REUSE_LEARNER_ACTIONS, $ILMS_YELLOW_MARKUP_LIMIT;
 
 $globals = new stdClass();
 $globals->max_ilms_case_count = $MAX_ILMS_CASE_COUNT;
 $globals->replace_case_similarity_limit = $REPLACE_CASE_SIMILARITY_LIMIT;
 $globals->weight_factor_learnermeta = $WEIGHT_FACTOR_LEARNERMETA;
 $globals->weight_factor_activitymeta = $WEIGHT_FACTOR_ACTIVITYMETA;
 $globals->weight_factor_follow_relations = $WEIGHT_FACTOR_FOLLOW_RELATIONS;
 $globals->weight_factor_current_activity = $WEIGHT_FACTOR_CURRENT_ACTIVITY;
 $globals->weight_factor_history = $WEIGHT_FACTOR_HISTORY;
 $globals->weight_factor_states = $WEIGHT_FACTOR_STATES;
 $globals->half_value_time = $HALF_VALUE_TIME;
 $globals->leak_limit = $LEAK_LIMIT;
 $globals->weights_learnermeta = $WEIGHTS_LEARNERMETA;
 $globals->weights_activitymeta = $WEIGHTS_ACTIVITYMETA;
 $globals->enable_reuse_adjustments = $ENABLE_REUSE_ADJUSTMENTS;
 $globals->reuse_learner_actions = $REUSE_LEARNER_ACTIONS;
 $globals->ilms_yellow_markup_limit = $ILMS_YELLOW_MARKUP_LIMIT;
 
 print(json_encode($globals));
?>