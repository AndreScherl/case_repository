<?php
    /* iLMS - Hilfsseite zum Start von Lernaktivitäten
     * 
     * Diese Seite wird beim Auswählen von Lernaktivitäten aufgerufen.
     * Sie ändert den Status einer Lernaktivität auf state_incomplete oder state_repeat und leitet zur
     * jeweiligen Modul-Seite weiter. Erstellt außerdem anhand der Eingabe des Lerners einen neuen Fall,
     * der später durch den CRON-Job zur Fallbasis hinzugefügt wird.
     * 
     * Für alle Benutzer, die keine Lerner sind, werden allerdings KEINE Stati geändert und KEINE neuen 
     * Fälle erfasst. Dies liegt daran, dass Kursverwalter, Tutoren und Administratoren Lernaktivitäten
     * über diese Seite nicht nur zum Betrachten aufrufen sondern auch, um bestimmte Eigenschaften der
     * Lernaktivität zu ändern oder Lernergebnisse auszuwerten (GS: das ist in Moodle zwar blöd so, ist aber nun mal so implementiert).
     * 
	 * Copyright (C) 2007, Gert Sauerstein
	 * Edited by Andre Scherl, 17.09.2012
	 * You should have received a copy of the GNU General Public License
	 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
	 */
      
    //error_reporting(E_ALL);
     
    require_once('../../config.php');
    require_once($CFG->libdir.'/weblib.php');
    require_once($CFG->libdir.'/dmllib.php');
    require_once($CFG->libdir.'/datalib.php');
    require_once('ilms_case_retrieval.php');
    
    global $USER, $DB, $COURSE, $SESSION;

    // Prüfe Eingabeparameter ///////////////////////////////////////////

    $activity_id = required_param('id', PARAM_INT);
    $backward = optional_param('backward', false, PARAM_BOOL);
    $foreward = optional_param('foreward', false, PARAM_BOOL);
    $navkind = optional_param('nav', 'none', PARAM_TEXT);
    
	
    if (!$user = $DB->get_record('user', array('id' => $USER->id))) {
        print_error("error: invalid user");
    }
    if (!$activity = $DB->get_record_sql("SELECT cm.*, m.name FROM {course_modules} cm INNER JOIN {modules} m ON cm.module = m.id WHERE cm.id = ?", array($activity_id))) {
        print_error("error: invalid activity");
    }
    
    // Loggen der verschiedenen Navigationsparameter für spätere Auswertung im DASIS-Projekt //////////////////////////////////////
	$info = "from_cm:{$SESSION->dasis_activityId}, ";
	$info .="to_cm:$activity_id, ";
	if(!($SESSION->dasis_selectedPath === "adapt")) {
		if($SESSION->dasis_selectedPath == 0) {
			$info .= "l_path:'none', ";
		} else {
			$info .= "l_path:'".$DB->get_field("dasis_learning_paths", "name", array("id" => $SESSION->dasis_selectedPath))."', ";
		}
	} else {
		$info .= "l_path:'adaptive', ";
	}
	$info .= "next:$foreward, ";
	$info .= "back:$backward, ";
	$info .= "nav:'$navkind'";
	add_to_log($COURSE->id, "DASIS", "navigate", $url="start.php?id=$activity_id", $info, $SESSION->dasis_activityId, $USER->id);
   	
   	
   	// Wenn die aufgerufene Aktivität nicht in der Netznavigation enthalten ist, wird keine Adaptionsfunktion aufgrufen, sondern gleich weitergeleitet
   	if(!$DB->record_exists_select("dasis_relations", "source = ? OR target = ?", array($activity_id, $activity_id))) {
   		redirect("{$CFG->wwwroot}/mod/{$activity->name}/view.php?id=$activity_id");
   	}
    
    $context = get_context_instance(CONTEXT_COURSE, $activity->course);

    // Bestimme den aktuellen Fall ///////////////////////////////////////
    
    // alle Kurse, die in einem beliebigen Bundle enthalten sind werden für die Adaption berücksichtigt
 	if($SESSION->dasis_selectedBundle > 0){
 		$sql = "SELECT DISTINCT course_id FROM {dasis_bundle_connections} WHERE bundle_id =".$SESSION->dasis_selectedBundle;
 	}else{
 		$sql = "SELECT DISTINCT course_id FROM {dasis_bundle_connections}";
 	}
 	$all_bundle_courses = $DB->get_records_sql($sql);
 	$bundle_courses = array();
 	foreach($all_bundle_courses as $bc){
 		$bundle_courses[] = $bc->course_id;
 	}
    
    $current_case = current_case($bundle_courses);
    $current_case->solution_activityid = $activity_id;
    
    // Bestimme letzte Lernaktivität in der History /////////////////////
    
    $sql = "SELECT MAX(idx) AS max FROM {ilms_history} WHERE userid = {$USER->id} AND courseid = {$activity->course} ";
    $history = $DB->get_record_sql($sql);
    if($history) {
        $history = $history->max + 1;
    } else {
    	$history = 1;
    }
	
	if(!($backward && $SESSION->dasis_selectedPath == "adapt")) {
		$SESSION->dasis_historyPosition = 0;
    	// Aktualisiere Status in der Datenbank //////////////////////////////
    	if(has_capability('block/case_repository:store', $context, $USER->id, false)) {
    	    //Zeile in der ilms_states Datenbank
    	    $stateObject->userid = $USER->id;
    	    $stateObject->coursemoduleid = $activity_id;
    	    $stateObject->timemodified = time();
    	    if($old_state = $DB->get_record("ilms_states", array("userid" => $USER->id, "coursemoduleid" => $activity_id))){
    	    	$stateObject->state = $old_state->state == "state_complete" ? "state_repeat" : "state_incomplete";
    	    	$stateObject->id = $old_state->id;
    	        $DB->update_record("ilms_states", $stateObject);               
    	    } else {
    	    	$stateObject->state = "state_incomplete";
    	        $DB->insert_record("ilms_states", $stateObject);               
    	    }
    	    
    	    $newCaseObject->userid = $USER->id;
    	    $newCaseObject->serialized_case = addslashes(serialize($current_case));
    	    $newCaseObject->courseid = $activity->course;
    	    $newCaseObject->coursemoduleid = $activity_id;
    	    $newCaseObject->timemodified = time();
    	    $DB->insert_record("ilms_new_cases", $newCaseObject); //(AS) Speichern des neuen Falls: Database error: "Data too long for column"
    	    
    	    $historyObject->userid = $USER->id;
    	    $historyObject->courseid = $activity->course;
    	    $historyObject->coursemoduleid = $activity_id;
    	    $historyObject->timemodified = time();
    	    $historyObject->idx = $history;
    	    $DB->insert_record("ilms_history", $historyObject);
    	}
    } else {
    	$SESSION->dasis_historyPosition++;
    }     

    // Leite zur etsprechenden Lernaktivität weiter ///////////////////////
    redirect("{$CFG->wwwroot}/mod/{$activity->name}/view.php?id=$activity_id");
?>  