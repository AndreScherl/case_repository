<?php
    /* iLMS - Bibliothek zur Abspeichern von neuen Fällen anhand der Bewertungen/Noten in der Fallbasis
     * 
 	 * Copyright (C) 2007, Gert Sauerstein
 	 * Edited by Andre Scherl, 17.09.2012
 	 * You should have received a copy of the GNU General Public License
 	 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 	 */
     
    global $DB;
     
    require_once($CFG->libdir.'/dmllib.php');
    require_once($CFG->dirroot.'/blocks/case_repository/dmllib2.php');
    require_once($CFG->dirroot.'/blocks/case_repository/ilms_config.php');
    require_once($CFG->dirroot.'/blocks/case_repository/ilms_case_retrieval.php');
    
    error_reporting(E_ALL);
             
    /**
     * Normalisiert Noten zwischen einem Minimum und einem Maximum auf das Intervall [0,1]
     * @param float $grade  Note
     * @param float $min    Schlechteste Note (kleinster Wert)
     * @param float $max    Beste Note (größer Wert)
     * @return  float die mittels Verhältnisgleichung in das Intervall [0,1] normalisierte Note
     */
    function normalize_grades($grade, $min = 0.0, $max = 100.0) {
    	assert($max > $min);
        assert($grade <= $max && $grade >= $min);
        if($min == $max) {
            return 1.0; // Vermeide Division durch Null
        } else {
            return ($grade - $min)/($max - $min);
        }
    }

    /**
     * Verarbeitet alle noch nicht verarbeiteten Noten und fügt die zugehörigen Fälle,
     * sondern vorhanden zur Fallbasis hinzu. Anschließend werden sie Noten markiert,
     * (in der Tabelle ilms_grades abgelegt) damit sie nicht erneut verarbeitet werden.
     */
    function process_grades() {
        global $CFG, $DB;
        global $REPLACE_CASE_SIMILARITY_LIMIT, $MAX_ILMS_CASE_COUNT;
        global $ENABLE_REUSE_ADJUSTMENTS, $REUSE_LEARNER_ACTIONS;
        global $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $WEIGHT_FACTOR_LEARNERMETA, $WEIGHT_FACTOR_ACTIVITYMETA, $WEIGHT_FACTOR_FOLLOW_RELATIONS, $WEIGHT_FACTOR_CURRENT_ACTIVITY, $WEIGHT_FACTOR_HISTORY, $WEIGHT_FACTOR_STATES;
        $ENABLE_REUSE_ADJUSTMENTS = false; // (GS) Hier keine Reuse-Betrachtung

        echo "  Frage Datenbank ab ...\n";
    	// Lese Fallanzahl aus
        if(!$case_count = $DB->get_record_sql("SELECT COUNT(*) AS count FROM {ilms_cases}")) {
            echo("  Abbruch: Kann nicht auf die Fallbasis zugreifen\n");
            return;
        }
        $case_count = $case_count->count;
        echo "  $case_count Fälle sind bereits in der Fallbasis vorhanden.\n";
        // Aktualisiere die bereits vorhandenen Fälle der Fallbasis
        $sql = "SELECT c.id, c.appliance, c.used_count\n ".
               "FROM {ilms_cases} c\n ";
        if($cases = get_records_sql_by_field($sql, null, "id")) {
        	foreach($cases as $case) {
        		echo "  Betrachte Fall $case->id.\n";
                $sql = "SELECT g.id, g.userid, g.lastgrade, g.lastgrademax, g.lastgrademin, g.courseid, i.grademax, i.grademin, g2.finalgrade AS grade, g2.itemid \n ".
                       "FROM {ilms_grades} g\n ".
                       "  INNER JOIN {grade_items} i ON g.courseid = i.courseid AND i.itemtype = 'course'\n ".
                       "  INNER JOIN {grade_grades} g2 ON g2.itemid = i.id AND g2.userid = g.userid\n ".
                       "WHERE g.caseid = ?";
                if($grades = get_records_sql_by_field($sql, array($case->id), "id")) {
                	$new_appliance = 0.0;
                	$transaction1 = $DB->start_delegated_transaction();
                    //begin_sql();
                    foreach($grades as $g) {
                    	$grade = normalize_grades($g->grade, $g->grademin, $g->grademax);
                        echo "    Betrachte Benotung #$g->id: Bisherige Note $g->lastgrade [$g->lastgrademin,$g->lastgrademax], neue Note $g->grade [$g->grademin,$g->grademax], umgerechnet $grade.\n";
                		$new_appliance += $grade;
                        //execute_sql("UPDATE {ilms_grades} SET lastgrademax = $g->grademax, lastgrademin = $g->grademin, lastgrade = $g->grade, timemodified = ".time()." WHERE id = $g->id", false);
                        $gradesObject->id = $g->id;
                        $gradesObject->lastgrademax = $g->grademax;
                        $gradesObject->lastgrademin = $g->grademin;
                        $gradesObject->lastgrade = $g->grade;
                        $gradesObject->timemodified = time();
                        $DB->update_record("ilms_grades", $gradesObject);
                	}
                    $new_appliance = $new_appliance / $case->used_count;
                    //if(execute_sql("UPDATE {ilms_cases} SET appliance = $new_appliance WHERE id = $case->id", false)) {
                    $caseObject->id = $case->id;
                    $caseObject->appliance = $new_appliance;
                    if($DB->update_record("ilms_cases", $caseObject)) {
                        echo "    Aktualisierte Bewertung für diesen Fall: $new_appliance\n";
                        $transaction1->allow_commit();
                    } else {
                    	echo "    Bewertung konnte nicht aktualisiert werden.";
                        $transaction1->rollback();
                    }
                }
        	}
        } else {
            echo("    Keine zu aktualisierenden Fälle gefunden, prüfe auf neue Fälle.\n");
        }
        // Füge neue Fälle hinzu
        $sql =  "SELECT c.*, i.grademax, i.grademin, g.id AS gradeid, g.finalgrade AS grade, g.itemid\n ".
                "FROM {ilms_new_cases} c \n ".
                "  INNER JOIN {grade_items} i ON c.courseid = i.courseid AND i.itemtype = 'course'\n ".
                "  INNER JOIN {grade_grades} g ON g.itemid = i.id AND c.userid = g.userid\n ".
                "WHERE g.finalgrade IS NOT NULL"; //! (AS) finalgrade = NULL causes error when storing to ilms_grades

        if($cases = get_records_sql_by_field($sql)) {
        	foreach($cases as $c) {
                echo "  Betrachte neuen Fall: USERID $c->userid, COURSE $c->courseid, GRADE $c->gradeid.\n";
                // Bestimme aktuellen Fall und den besten/schlechtesten Referenzfall
                assert($c->serialized_case != null);
                $current_case = unserialize(stripslashes($c->serialized_case));
                $ref_cases = retrieve_cases($current_case, 1);
                $case_id = null;
                $transaction2 = $DB->start_delegated_transaction();
                // Eine neue Bewertung  
                echo "    Setze den Status von Lernaktivität #$c->coursemoduleid auf BEENDET\n";
                //$sql = "UPDATE {ilms_states} SET state = 'state_complete', timemodified = ".time()." WHERE coursemoduleid = $c->coursemoduleid AND userid = $c->userid";
                //! ich rufe den record ab, damit ich die für das folgenden Update notwendige id bekomme
                $stateObject = $DB->get_record("ilms_states", array("coursemoduleid" => $c->coursemoduleid, "userid" => $c->userid));
                $stateObject->coursemoduleid = $c->coursemoduleid;
                $stateObject->userid = $c->userid;
                $stateObject->state = 'state_complete';
                $stateObject->timemodified = time();
                if(!$DB->update_record("ilms_states", $stateObject)) {
                    echo "    Fehler: Konnte den Status der Lernaktivität nicht auf \"abgeschlossen\" setzen\n";
                    $transaction2->rollback();
                    continue;
                }
                if($ref_cases->best_case) {
                    echo "    Größte Ähnlichkeit mit einem vorhandenen Fall ".$ref_cases->best_case->similarity.", geringste Ähnlichkeit mit einem vorhandenen Fall ".$ref_cases->worst_case->similarity.", Limit für Strategie 1 bei ".$REPLACE_CASE_SIMILARITY_LIMIT."\n";
                }
                $samesolution = !empty($ref_cases->best_case) && $ref_cases->best_case->mapping[$current_case->solution_activityid] >= 0 && $ref_cases->best_case->mapping[$current_case->solution_activityid] == $ref_cases->best_case->solution_activityid;
                if($ref_cases->best_case != null && $ref_cases->best_case->similarity > $REPLACE_CASE_SIMILARITY_LIMIT 
                  && $samesolution) {
                    // 1. Falls der gefundene beste Fall dem aktuellen Fall SEHR ähnlich ist UND die gleiche Lösung hat, passe diesen an
                    echo "    Strategie 1: Verwende einen bereits vorhandenen Fall #{$ref_cases->best_case->id} wieder und passe ihn an.\n";
                    if(!update_case($ref_cases->best_case->id, $c)) {
                        $transaction2->rollback();
                        continue;
                    }
                    $case_id = $ref_cases->best_case->id;
                } else {                
                    if($case_count >= $MAX_ILMS_CASE_COUNT && $ref_cases->worst_case != null) {
                        // 2. Falls kein Platz mehr in der Datenbank ist: ersetzte den am schlechtesten passenden Fall (lösche ihn) 
                        echo "    Maximale Größe der Fallbasis von $MAX_ILMS_CASE_COUNT erreicht.\n";
                        echo "    Strategie 2: Ersetze unpassenden Fall durch einen neuen Fall\n";
                        if(!remove_case($ref_cases->worst_case->id)) {
                            $transaction2->rollback();
                            continue;
                        }
                        $case_count--;
                    } else {
                        echo "    Strategie 3: Füge neuen Fall zur Fallbasis hinzu\n";
                    }
                    // 3. Füge den aktuellen Fall neu hinzu 
                    if(!add_new_case($current_case, $c, $case_id)) {
                        $transaction2->rollback();
                        continue;               
                    }
                    $case_count++;                         
                }
                //$sql = "INSERT INTO {ilms_grades} (courseid, caseid, userid, lastgrade, lastgrademax, lastgrademin, timemodified) VALUES ($c->courseid, $case_id, $c->userid, $c->grade, $c->grademax, $c->grademin, ".time().")";
                $gradeObject->courseid = $c->courseid;
                $gradeObject->caseid = $case_id;
                $gradeObject->userid = $c->userid;
                $gradeObject->lastgrade = $c->grade;
                $gradeObject->lastgrademax = $c->grademax;
                $gradeObject->lastgrademin = $c->grademin;
                $gradeObject->timemodified = time();
                if(!$DB->insert_record("ilms_grades", $gradeObject)) {
                    echo "    Fehler: Bewertung konnte in den Tracking-Daten von iLMS nicht gesichert werden\n";
                    $transaction2->rollback();
                    continue;
                }
                echo "    Schließe den Fall von Lernaktivität #$c->coursemoduleid ab.\n\n";
                //$sql = "DELETE FROM {ilms_new_cases} WHERE id = $c->id";
                if(!$DB->delete_records("ilms_new_cases", array("id"=>$c->id))) {
                    echo "    Fehler: Konnte den den neuen Fall #$c->id nicht schließen. \n";
                    $transaction2->rollback();
                    continue;
                }
                $transaction2->allow_commit();      		
        	}
        } else {
            echo("  Abbruch: Keine neuen Bewertungen gefunden\n");
        }
        cleanup();
        echo "  Ausführung beendet. $case_count Fälle sind in der Fallbasis vorhanden.\n";
    }
    
    /**
     * Entfernt überflüssige oder veraltete Datensätze aus der Datenbank.
     * Alle Datensätze, die länger als 1 Jahr in den Tabellen ilms_history oder ilms_grades bzw. länger als 10 Jahre in der Tabelle ilms_states oder ilms_history stehen werden entfernt,
     * da sie aufgrund des Alters voraussichtlich nicht mehr benötigt werden (Annahme). Dadurch wird Speicherplatz freigegeben und die Verarbeitungsgeschwindigkeit des Systems beschleunigt.
     * Außerdem werden alle Datensätzen von Benutzern, Lernaktivitäten oder Kursen entfernt, die nicht mehr existieren. 
     */
    function cleanup() {
    	global $CFG, $DB;
        $time1year = time() - 31557600; // 1 Jahr entspricht 31 557 600 SI-Sekunden
        $time10years = time() - 315576000;
        echo "  Entferne veraltete oder überflüssige Datensätze...\n";
    	$DB->delete_records_select("ilms_new_cases", "timemodified < $time1year");
        $DB->delete_records_select("ilms_grades", "timemodified < $time1year");
        $DB->delete_records_select("ilms_states", "timemodified < $time10years");
        $DB->delete_records_select("ilms_history", "timemodified < $time10years");
        $DB->delete_records_select("ilms_learnermeta", "userid NOT IN (SELECT id FROM {user})");
        $DB->delete_records_select("ilms_learner_knowledge", "userid NOT IN (SELECT id FROM {user})");
        $DB->delete_records_select("dasis_modmeta", "coursemoduleid NOT IN (SELECT id FROM {course_modules})");
        $DB->delete_records_select("ilms_history", "userid NOT IN (SELECT id FROM {user})");
        $DB->delete_records_select("ilms_history", "courseid NOT IN (SELECT id FROM {course})");
        $DB->delete_records_select("ilms_history", "coursemoduleid NOT IN (SELECT id FROM {course_modules})");
        $DB->delete_records_select("dasis_relations", "source NOT IN (SELECT id FROM {course_modules})");
        $DB->delete_records_select("dasis_relations", "target NOT IN (SELECT id FROM {course_modules})");
        $DB->delete_records_select("ilms_states", "userid NOT IN (SELECT id FROM {user})");
        $DB->delete_records_select("ilms_states", "coursemoduleid NOT IN (SELECT id FROM {course_modules})");
    }
    
    /**
     * Aktualisiert einen Fall zur Wiederverwendung.
     * @param int       $caseid ID des Falls, der wiederverwendet werden kann, in der Datenbank
     * @param Object    $g      Bewertungsinformationen des neuen Falls
     * @return bool true im Erfolgsfall, sonst false
     */
    function update_case($caseid, &$g) {
    	global $CFG, $DB;
        if(!$case = $DB->get_record("ilms_cases", array("id" => $caseid))) {
            echo "    Fehler: Fall konnte für die Wiederverwendung nicht ermittelt werden\n";
            return false;
        }
        $used_count_new = $case->used_count < 1 ? 1 : $case->used_count+1;
        echo "      Anzahl der durch den gespeicherten Fall repräsentierten Fälle: $case->used_count (vorher), $used_count_new (nachher)\n";
        if($case->appliance == null) {
            $appliance_new = normalize_grades($g->grade, $g->grademin, $g->grademax);
            echo "      Repräsentativität des Falls: $appliance_new\n";
        } else {
            // Wiederverwendung: Durch die Häufigkeit gewichtetes arithmetisches Mittel bilden
            echo "      Alte Repräsentativität des Falls: $case->appliance\n";
            $appliance_new = (normalize_grades($g->grade, $g->grademin, $g->grademax)+$case->used_count*$case->appliance)/$used_count_new;
            echo "      Neue Repräsentativität des Falls: $appliance_new\n";
        }
        //if(!execute_sql("UPDATE {ilms_cases} SET used_count = $used_count_new, appliance = $appliance_new WHERE id = $caseid", false)) {
        $caseObject->used_count = $used_count_new;
        $caseObject->appliance = $appliance_new;
        $caseObject->id = $caseid;
        if(!$DB->update_record("ilms_cases", $caseObject)) {
            echo "    Fehler: Fall konnte für die Wiederverwendung nicht aktualisiert werden\n";
            return false;
        }
        return true;
    }

    /**
     * Entfernt einen überflüssigen oder schlecht passenden Fall mit allen zugehörigen Daten, um Platz für neue Fälle 
     * in der Datenbank zu schaffen.
     * @param int   $caseid ID des Falls, der gelöscht werden soll, in der Datenbank
     * @return bool true im Erfolgsfall, sonst false
     */
    function remove_case($caseid) {
    	global $CFG, $DB;
        // Bestimme zu löschende Datensätze
        if(!$case = $DB->get_record("ilms_cases", array("id" => $caseid))) {
            echo "    Fehler: Kann den zu entfernenden Fall nicht bestimmen\n";
            return false;
        }
        // Entferne den Fall an sich
        if(!$DB->delete_record("ilms_cases", array("id" => $caseid))) {
            echo "    Fehler: Kann am schlechtesten passenden Fall nicht löschen, um Platz für den neuen Fall in der Datenbank zu schaffen\n";
            return false;
        }
        // Aktualisiere Referenz in Bewertungsdaten
        $gradeObject = $DB->get_record("ilms_grades", array("caseid" => $caseid));
        $gradeObject->caseid = NULL;
        //if(!execute_sql("UPDATE {ilms_grades} SET caseid = NULL WHERE caseid = $caseid", false)) {
        if(!$DB->update_record("ilms_grades", $gradeObject)) {
            echo "    Fehler: Kann veraltete Referenz auf den Fall in den Bewertungsdaten nicht aktualisieren\n";
            return false;
        }
        echo "    Unpassender Fall #$caseid wurde erfolgreich aus der Fallbasis entfernt.\n";
        return true;
    }
    
    /**
     * Fügt einen neuen Fall zur Fallbasis hinzu.
     * @param Object    $current_case   der hinzuzufügende Fall
     * @param g         $g              Objekt mit den Eigenschaften $g->grade, $g->grademin, $g->grademax (Benotungsinformationen)
     * @param int       $case_id        Referenz auf eine Variable, in welcher die ID des neu erzeugten Falls geschrieben werden soll
     * @return bool true im Erfolgsfall, sonst false
     */
    function add_new_case(&$current_case, &$g, &$case_id) {
    	global $CFG, $DB;
        // Erzeuge neuen Fall
        $c = new stdClass();
        $c->serialized_case = addslashes(serialize($current_case));
        $c->appliance = normalize_grades($g->grade, $g->grademin, $g->grademax);
        echo "    Normalisierte Bewertung: $c->appliance\n";
        if(!$case_id = $DB->insert_record('ilms_cases', $c)) {
            echo("    Fehler: Kann neuen Fall nicht erzeugen\n");
            return false;
        }
        return true;
    }

?>