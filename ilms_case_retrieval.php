<?php
/* iLMS - Funktionsbibliothek zur Ermittlung von passenden Fällen aus der Fallbibliothek
 *
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
  
error_reporting(E_ALL);
 
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->libdir.'/dmllib.php');
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->dirroot.'/blocks/case_repository/dmllib2.php');
require_once($CFG->dirroot.'/blocks/case_repository/ilms_cases.php');
require_once($CFG->dirroot.'/blocks/case_repository/ilms_case_compare.php');

define("CASES_BLOCK_RETRIEVAL_COUNT", 1000);
    
/**
 * Erzeugt einen Fall für das aktuelle Kurs-Bündel.
 * @param array   $course_ids (AS) Anstelle eines Kurses werden alle Kurse eines Bündels berücksichtigt
 * @return iLMSCase Fallobjekt für den angegebenen Kurs im aktuellen Zustand oder NULL, falls keine gültigen Tracking-Informationen vorhanden sind
 */
function current_case($course_ids) {
	global $CFG, $USER, $DB, $SESSION, $COURSE;
	
    list($cids, $params) = $DB->get_in_or_equal($course_ids);  // (AS) neue Syntax der DB in Moodle 2.0+
    // Bestimme Metadaten des aktuellen Lerners
    $sql = "SELECT l2.*, d.attribute, d.type \n". 
           "FROM \n".
           "  (SELECT l.definitionid, l.subtype, SUM(l.appliance*l.value)/SUM(l.appliance) AS value  \n".
           "   FROM {ilms_learnermeta} l   \n".
           "   WHERE userid = ? \n".
           "   GROUP BY l.subtype, l.definitionid".
           "   UNION ALL\n".
           "   SELECT d2.id as definitionid, NULL as subtype, SUM(k.appliance*k.value)/SUM(k.appliance) AS mean_value\n".
           "   FROM {ilms_learner_knowledge} k\n".
           "   INNER JOIN {ilms_learnermeta_definitions} d2 ON d2.attribute = 'difficulty'\n".
           "   WHERE userid = ? AND courseid $cids \n".
           "   GROUP BY d2.id\n".
           "  ) l2 \n".
           "  INNER JOIN {ilms_learnermeta_definitions} d ON l2.definitionid = d.id  \n";
    if(!$learner_meta = get_records_sql_by_field($sql, array_merge(array($USER->id, $USER->id), $params))) {
		$learner_meta = array();
	}
    // Lese die History der bisher besuchten Lernaktivitäten
    $sql = "SELECT h.id AS historyid , h.idx, h.timemodified, cm.id\n".
           "FROM {course_modules} cm \n".
           "  INNER JOIN {ilms_history} h ON cm.id = h.coursemoduleid \n".
           "WHERE h.userid = ? AND h.courseid $cids \n";
    if(!$history = $DB->get_records_sql($sql, array_merge(array($USER->id), $params))) {
    	// Es wurde noch überhaupt keine Lernaktivität in diesem Kurs besucht
    	$history = array();
    }
    
    // Lese die ID der aktuellen Lernaktivität (letzte in der History mit größtem Index) 
    /*
    $sql = "SELECT MAX(idx) AS idx FROM {ilms_history} WHERE userid = $USER->id AND courseid IN $cids";
    if($current = $DB->get_record_sql($sql)) {
        //$current = count($history) > 0 ? $history[$current->idx]->id : null;
        $current = count($history) > 0 ? $history[$current->idx]->id : $DB->get_field_select("course_modules", "id", "course IN $cids LIMIT 0,1"); //! Variation von Andre Scherl, damit es beim Anlegen eines Kurses keine Undefiniertheiten gibt
    } else {
        // Es wurde noch überhaupt keine Lernaktivität in diesem Kurs besucht
        $current = null;
    }
    */
    $current = $SESSION->dasis_activityId; // (AS) Verwende DASIS's activityId
    $current_course_id = $DB->get_field("course_modules", "course", array("id" => $current));
    // Metadaten und Zustände aller Lernaktivitäten dieses Kurses bestimmen
    //$context = get_context_instance(CONTEXT_MODULE, $current); // (AS) changed from course to module
    $context = get_context_instance(CONTEXT_COURSE, $current_course_id); // (AS) neuer Versuch wg. Warning
    $activities = array();
    $language = current_language();
    $sql = "SELECT cm.id, m.id AS activitymetaid, cm.module, m.linguistic_requirement, m.logical_requirement, m.social_requirement, m.learningstyle_perception, m.learningstyle_organization, m.learningstyle_perspective, m.learningstyle_input, m.difficulty, m.learningstyle_processing, m.learning_time, s.state, cm.visible \n".
           "FROM {dasis_modmeta} m \n".
           "  RIGHT OUTER JOIN  {course_modules} cm ON m.coursemoduleid = cm.id \n".
           "  INNER JOIN {modules} m2 ON cm.module = m2.id \n".
           "  LEFT OUTER JOIN  {ilms_states} s ON cm.id = s.coursemoduleid AND s.userid = ? \n".
           "WHERE cm.course $cids AND m2.name <> 'label'"; // (GS) Bugfix iLMS-18: Labels müssen ausgefiltert werden, da sie keine Lernaktivitäten im engeren Sinn darstellen!!!
    if($activities = get_records_sql_by_field($sql, array_merge(array($USER->id), $params))) { // Dauer der isolierten Abfrage 0.5ms
        $activities_with_id_key = array();
        foreach($activities as $key => $m) {
        	$activities_with_id_key[$m->id] = $m; // (AS) Speichere die Aktivitätsmetadaten mit course_module_id als key
    		if(!($m->visible || has_capability('moodle/course:viewhiddenactivities', $context))) {
    			// Unsichtbare Lernaktivitäten werden für die Adaption nicht betrachtet und ausgefiltert
                unset($activities_with_id_key[$key]);	
    		}
            if($m->state == null) {
            	// Falls (noch) kein Zustand gesetzt wurde, wurde die Lernaktivität noch nicht besucht
                $m->state = 'state_not_attempted';
            }
            // (GS) Die Sprache wird nicht aus der Datenbank ermittelt, sondern es wird die aktuell im Kurs eingestellte Sprache verwendet
            $m->language = $language; 
    	}
    	$activities = $activities_with_id_key;
    } else {
    	// Dieser Kurs besitzt (noch) keine Lernaktivitäten
    	$activities = array();
    }
    //$acts = recordset_to_array2($activities, $key_field=null);
    // Bestimme Beziehungen zu den Folgelernaktivitäten
    if(!$relations = $DB->get_records_sql("SELECT r.id, r.target AS activityid, r.type AS semantic_type FROM {dasis_relations} r WHERE source = ?", array($current))) {
    	$relations = array();
    }
    // Fall erstellen
    return new iLMSCase($learner_meta, $activities, $history, $relations, $current);
}

/**
 * Liest alle Fälle in Blöcken vordefinierter Größe aus der Datenbank.
 * @param int   $block  Index des zu lesenden Blocks
 * @return mixed ein Array, welches einer Fall-ID den entsprechenden Fall zuordnet oder false, falls keine Fälle für den angegebenen Block gefunden werden
 */
function get_all_cases($block) {
	global $CFG, $DB;
    $sql = "SELECT * FROM {ilms_cases} c ORDER BY id";
    
    return $DB->get_records_sql($sql, null, $block, CASES_BLOCK_RETRIEVAL_COUNT);
}

/**
 * Sucht nach einem ähnlichen Fall in der Fallbibliothek.
 * @param Object    $similarcase Beschreibung des Suchfalls als iLMSCase Objekt
 * @param int       $count       Anzahl ähnlicher Fälle, nach denen die Suche abgebrochen wird
 * @return Array    Ein Array mit maximal $count vielen dem Referenzfall ähnlichen Fällen aus der Fallbibliothek
 */
function retrieve_cases(&$similarcase, $count=3) {
	global $CFG, $DB, $ENABLE_REUSE_ADJUSTMENTS, $REUSE_LEARNER_ACTIONS;
    global $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $WEIGHT_FACTOR_LEARNERMETA, $WEIGHT_FACTOR_ACTIVITYMETA, $WEIGHT_FACTOR_FOLLOW_RELATIONS, $WEIGHT_FACTOR_CURRENT_ACTIVITY, $WEIGHT_FACTOR_HISTORY, $WEIGHT_FACTOR_STATES;
            
    $result = new stdClass();
    $result->best_case = null;
    $result->worst_case = null;
    $result->solutions = array();
    $block = 0;
    while($cases = get_all_cases($block)) {
        foreach($cases as $c) {
        	$case = unserialize(stripslashes($c->serialized_case));
            $case->id = $c->id;
            $case->used_count = $c->used_count;
            $case->appliance = $c->appliance;
            
            $compare_result = compare_cases($similarcase, $case);
            
            if(is_null($case->solution_activityid)) {
                continue; // Keine Lösung in diesem Fall, also weiter zum nächsten Fall
            } else {
                if(!array_key_exists($case->solution_activityid, $case->activity_meta)) {
                	continue; // Ungültige Lösung, also weiter zum nächsten Fall 
                }
                // Bestimme Mapping für Lösung
                if(!array_key_exists($case->solution_activityid, $compare_result->mapping21)) {
                    continue; // Zwar eine Lösung, aber kein Mapping für die Lösung auf diesen Fall vorhanden, also weiter zum nächsten Fall 
                }
                $mapped_key =  $compare_result->mapping21[$case->solution_activityid];
                if($mapped_key < 0) {
                    continue; // Zwar eine Lösung, aber kein Mapping für die Lösung auf diesen Fall vorhanden, also weiter zum nächsten Fall 
                }
                $solution = $similarcase->activity_meta[$mapped_key];
            }
            $case->similarity = $compare_result->similarity;
            $case->similarity_activity_meta = $compare_result->similarity_activity_meta;
            $case->similarity_history = $compare_result->similarity_history;
            $case->similarity_learner_meta = $compare_result->similarity_learner_meta;
            $case->similarity_relations = $compare_result->similarity_relations;
            $case->similarity_states = $compare_result->similarity_states;
            $case->similarity_current_activity = $compare_result->similarity_current_activity;
            if($ENABLE_REUSE_ADJUSTMENTS) {
            	// Reuse-Schritt: Eignung des Falls prüfen, ggf. Fallparameter adjustieren oder Fall verwerfen
                if(!reuse_case($similarcase, $case)) {
                	continue;
                }
            }
            $case->mapping = $compare_result->mapping12;
            $case->reverse_mapping = $compare_result->mapping21;
            $case->history_mapping = $compare_result->history_mapping;
            // 1. Ergebnis: Bestimmt den besten passenden Fall (für RETAIN / STORE benötigt)
            if($result->best_case == null || $compare_result->similarity > $result->best_case->similarity) {
                $result->best_case = $case;
            }
            // 2. Ergebnis: Bestimme den am wenigsten passenden/repräsentativen Fall (für RETAIN / STORE benötigt)
            $similarity_factor = $compare_result->similarity*(log($case->used_count, 10)+1); // (GS) Hier wird die Häufigkeit (used_count) in logarithmisierter Form mit berücksichtigt, damit oft verwendete Fälle nicht so leicht aus der Fallbasis entfernt werden  
            if($result->worst_case == null || $similarity_factor < $result->worst_case->similarity_factor) {
                $result->worst_case = $case;
                $result->worst_case->similarity_factor = $similarity_factor;
            }
            // 3. Bestimme für möglichst jede Folgelernaktivität bis zu n = 3 ähnlichste Fälle
            if(!array_key_exists($solution->id, $result->solutions)) {
            	$result->solutions[$solution->id] = array();
            }
            if(count($result->solutions[$solution->id]) < $count) {
            	$result->solutions[$solution->id][] = $case;
            } else {
                foreach($result->solutions[$solution->id] as $i => $sol) {
                    if($compare_result->similarity > $sol->similarity) {
                        for($j = $i+1; $j < $count; ++$j) {
                            $result->solutions[$solution->id][$j] = $result->solutions[$solution->id][$j-1];
                        }
                        $result->solutions[$solution->id][$i] = $case;
                    	break;
                    }
                }
            }
        }
        $block += CASES_BLOCK_RETRIEVAL_COUNT;	   	
    }
    return $result;
}

/**
 * Prüft, ob ein gefundener Referenzfall anwendbar ist und passt diesen gegebenenfalls an den aktuellen Fall an
 * 
 * @param $similarcase Object  Ein iLMSCase-Objekt, das den aktuellen (Such-)Fall enthält
 * @param $case   Object  Ein iLMSCase-Objekt, das den zu prüfenden Referenzfall mit seinem Mapping zum aktuellen Suchfall und allen Bewertungsangaben enthält
 * @return bool true, falls die Lösung des Referenzfall als Lösung für den Suchfall geeignet ist (gegebenenfalls nach einer Anpassung), oder false, falls der Fall unbrauchbar ist und verworfen werden soll
 */
function reuse_case(&$similarcase, &$case) { // (GS) WICHTIG: Übergabe per Referenz, sonst werden die Anpassungen nicht übernommen!!!
	global $REUSE_LEARNER_ACTIONS;
	
    foreach($similarcase->learner_meta as $key => $l) {
    	$a = $l->attribute;
        if(!array_key_exists($a, $REUSE_LEARNER_ACTIONS)) {
        	continue;
        }
        
    	$rule = $REUSE_LEARNER_ACTIONS[$a];
    	// Bestimme den Vergleichswert
        switch($rule['COMPARETO']) {
        	case 'activity':
                if(is_null($case->solution_activityid)) {
                	continue 2;
                }
                $compare_value = $case->activity_meta[$case->solution_activityid]->$a;
                break;
            case 'learner':
                $compare_value = array_key_exists($key, $case->learner_meta) ? $case->learner_meta[$key]->value : null;
                break;
            default: continue 2;
        }

        // Fallunterscheidung der einzelnen Typen von Bedingungen, Auswertung der Bedingung
        if(is_null($l->value) || is_null($compare_value)) {
        	continue;
        }
    	switch($rule['DIFFTYPE']) {
    		case 'gt':
                $condition = $l->value > $compare_value + $rule['LIMIT'];
                break;
            case 'lt':
                $condition = $l->value < $compare_value - $rule['LIMIT'];
                break;
            case 'diff':
                $condition = abs($l->value - $compare_value) > $rule['LIMIT'];
                break;
            default: $condition = false;
    	}

        // Falls Bedingung eingetreten -> Aktion ausführen
        if($condition) {
            $case->action = $rule['ACTION']; // (GS) Die zuletzt angewendete Aktion ist diejenige, die für den gesamten Fall gilt
        	switch($rule['ACTION']) {
        		case 'reject': return false;
                case 'adjust': 
                    assert($rule['STRENGTH'] <= 1.0 || $rule['STRENGTH'] >= 0.0); 
                    $case->similarity = $case->similarity*(1.0-$rule['STRENGTH']);
        	}
        }
    }
    return true;
}

/**
 * Prüft die gefundenen Fälle für alle möglichen Lösungs-Lernaktivitäten, fasst sie zusammen und bewertet sie.
 * @param Array $solutions_to_revise   Ein Array, welches jeder Lösung des aktuellen Falls ein Array von repräsentativen Referenzfällen zuweist
 * @return Array ein Array, welches jeder Lösungs-Lernaktivität eine Bewertung zuweist
 */
function revise_cases($solutions_to_revise) {
    global $CFG;
    $solutions = array();
    // Berechne die Bewertungen und markiere das Maximum
    $max_appliance = -INF;
    $max = null;
    foreach($solutions_to_revise as $id => $s) {
        $new = new stdClass();
        $new->id = $id;
        $new->appliance = 0.0;
        $new->maximum = false;
        $count = 0;
        // (GS) Aktuelle Bewertungsfunktion: Nach Häufigkeit gewichtetes arithmetisches Mittel über dem Produkt aus SIMILARITY und APPLIANCE 
        foreach($s as $sol){
        	$new->appliance += $sol->similarity*$sol->appliance;
            $count++;
        }
        $new->appliance = $new->appliance / $count;
        $new->count = $count;
        if($new->appliance >= $max_appliance) {
        	$max_appliance = $new->appliance;
            $max = $id;
        }
        $solutions[$id] = $new;
    }
    if($max != null) {
    	$solutions[$max]->maximum = true;
    }
    return $solutions;
}

?>
