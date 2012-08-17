<?php
/* iLMS - Funktionsbibliothek zum Fallvergleich
 * 
 * Enthält Funktionen mit denen Fälle und ihre Bestandteile (Attribute, Attributgruppen)
 * nach bestimmten Kriterien verglichen werden können.
 *
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
  
error_reporting(E_ALL);

require_once($CFG->dirroot.'/blocks/case_repository/ilms_queues.php');
require_once($CFG->dirroot.'/blocks/case_repository/ilms_config.php');

/**
 * Limit für Partner-Beziehungen zwischen Lernaktivitäten, bis zu dem beide nur gering ähnlichen Lernaktivitäten als 
 * absolut verschieden angenommen werden. Dadurch werden nur geringfügig ähnliche Lernaktivitäten bei der Bestimmung
 * der Lösung, beim Vergleich der Zustände, beim Vergleich der semantischen Beziehungen use. nicht berücksichtigt.
 */
define('ILMS_EDGE_LIMIT', 0.5);

class CompareResult {
    var $similarity;
    var $similarity_learner_meta;
    var $similarity_activity_meta;
    var $similarity_history;
    var $similarity_relations;
    var $similarity_states;
    var $mapping12;
    var $mapping21;
    var $history_mapping;
}

/**
 * Vergleicht zwei Fälle.
 * @param   Object  Erster Fall (ein iLMSCase-Objekt)
 * @param   Object  Zweiter Fall, mit dem der erste verglichen werden soll (auch ein iLMSCase-Objekt)
 * @return  Object Ein Objekt mit den beiden Attributen similarity (gibt die Übereinstimmung der Fälle an) und mapping (liefert das beste Matching für die Lernaktivitäten)
 */
function compare_cases(&$case1, &$case2) {
	global $CFG;
	global $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $WEIGHT_FACTOR_LEARNERMETA, $WEIGHT_FACTOR_ACTIVITYMETA, $WEIGHT_FACTOR_FOLLOW_RELATIONS, $WEIGHT_FACTOR_CURRENT_ACTIVITY, $WEIGHT_FACTOR_HISTORY, $WEIGHT_FACTOR_STATES;
    global $ENABLE_REUSE_ADJUSTMENTS, $REUSE_LEARNER_ACTIONS;
    
    $result = new CompareResult();
    // Lese Metadaten aus und vergleiche sie
    $result->similarity_learner_meta = compare_learner_meta(analyze_learner_meta($case1->learner_meta), analyze_learner_meta($case2->learner_meta));
    // Vergleiche die Metadaten und Zustände der Lernaktivitäten im Kurs
    if(count($case1->activity_meta) < 1 || count($case2->activity_meta) < 1) {
    	$result->mapping12 = array();
        $result->mapping21 = array();
    } else {
        assert(count($case1->activity_meta) > 0);
        $r = compare_best_matching_activities($case1->activity_meta, $case2->activity_meta);
        $result->similarity_activity_meta = $r->sum/max(min(count($case1->activity_meta), count($case2->activity_meta)), 1);
        $result->mapping12 = array();
        $result->mapping21 = array();
        foreach($r->partner as $r1 => $r2) {
        	$result->mapping12[$r1] = $r2;
        	$result->mapping21[$r2] = $r1;
        }
    }
    
    // Vergleiche die Metadaten und Zustände der Lernaktivitäten im Kurs
    $r2 = compare_history(array_values($case1->history), array_values($case2->history), $result->mapping12);
    $result->similarity_history = $r2->sum/max(count($case1->history), count($case2->history), 1.0);
    $result->similarity_current_activity = compare_current_activity($case1->history, $case2->history, $result->mapping12);
    $result->history_mapping = $r2->mapping; 
    // Vergleiche im semantischen Netz
    $result->similarity_relations = compare_activity_relations($case1->relations, $case2->relations, $result->mapping12);
    // Vergleiche der Zustände
    $result->similarity_states = compare_states($case1->activity_meta, $case2->activity_meta, $result->mapping12);
    // Gib das Ergebnis zurück
    $result->similarity = $WEIGHT_FACTOR_LEARNERMETA*$result->similarity_learner_meta+$WEIGHT_FACTOR_ACTIVITYMETA*$result->similarity_activity_meta+$WEIGHT_FACTOR_CURRENT_ACTIVITY*$result->similarity_current_activity+$WEIGHT_FACTOR_HISTORY*$result->similarity_history+$WEIGHT_FACTOR_FOLLOW_RELATIONS*$result->similarity_relations+$WEIGHT_FACTOR_STATES*$result->similarity_states;
    return $result;
}

/**
 * Analysiert die Metadaten aus der Tabelle ilms_case_usermeta und gruppiert sie 
 * für den späteren Vergleich nach Attributtypen/Attributen.
 * @param   Array   $learner_meta   Array mit den Metadaten-Sätzen aus der Tabelle ilms_case_usermeta
 * @return  Array Ein Array, das allen vorhandenen Attributtypen ein weiteres Array der Form Attribut => Wert mit allen Metadateninformationen der Eingabe zuweist.
 */
function analyze_learner_meta($learner_meta) {
	$result = array();
    foreach($learner_meta as $u) {
        if(array_key_exists($u->attribute, $result)) {
            // (GS) Annahme, dass es zu jedem Attribut nur einen Wert gibt
            // -> in current_case() findet eine SQL-Aggregation statt, damit dies für den Vergleichsfall sichergestellt wird
            $result[$u->attribute][$u->subtype] = $u->value;
        } else {
            $result[$u->attribute] = array($u->subtype => $u->value);
        }
    }
    return $result;
}

/**
 * Vergleicht die Metadaten der Lerner aus zwei Fällen.
 * @param   Object  $learner_meta1 Objekt mit den Metadaten der Lerner aus Fall 1 als Attribute
 * @param   Object  $learner_meta2 Objekt mit den Metadaten der Lerner aus Fall 1 als Attribute
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function compare_learner_meta($learner_meta1, $learner_meta2) {
    global $WEIGHTS_LEARNERMETA;    	
	$result = 0.0;
	foreach($WEIGHTS_LEARNERMETA as $t => $w) {
		// Für jeden Attributtyp: Mengenvergleich
        $exists1 = array_key_exists($t, $learner_meta1);
        $exists2 = array_key_exists($t, $learner_meta2); 
        if($exists1 == $exists2) {
            if(!$exists1 && !$exists2 || (count($learner_meta1[$t]) == 0 && count($learner_meta2[$t]) == 0)) {
                // (GS) Falls in beiden Metadaten keine Attribute für diesen Typ vorhanden sind, sind beide gleich -> HIER Gleichheit und weiter, da sonst später Division durch 0
                $result += $w;

                continue;
            }

        	$schnittmenge_count = 0;
            $schnittmenge_value = 0;
            foreach($learner_meta1[$t] as $a => $v) {
            	if(array_key_exists($a, $learner_meta2[$t])) {
                    // (GS) LOP-1 geändert: Differenzvergleich im Intervall [0,1] statt Identitätsvergleich

            		$schnittmenge_count += 1;
                    if(is_null($v) || is_null($learner_meta2[$t][$a])) {
                       $schnittmenge_value += 1;	
                    } else {
                        if($t == 'age') {
                            $schnittmenge_value += 1-abs(log($v)-log($learner_meta2[$t][$a]))/(log(100)-log(5));
                        } else {
                            $schnittmenge_value += 1-abs($v-$learner_meta2[$t][$a]);
                        }
                    }
            	}
        	}

            $result += $w*$schnittmenge_value/(count($learner_meta1[$t])+count($learner_meta2[$t])-$schnittmenge_count);
        }
	}
    return $result;
}    

/**
 * Vergleicht die Metadaten zweier konkreter Lernaktivitäten.
 * @param   Object  $activity1  Erste zu vergleichende Lernaktivität (mit den üblichen Attributen der Tabelle ilms_activitymeta)
 * @param   Object  $activity2  Zweite zu vergleichende Lernaktivität (dito)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function compare_activity($activity1, $activity2) {
    global $WEIGHTS_ACTIVITYMETA;
    // Vergleiche die einzelnen Attribute     
    $result = 0.0;
    if($activity1 == null || $activity2 == null) {
        return $activity1 == null && $activity2 == null;
    }



    foreach($WEIGHTS_ACTIVITYMETA as $t => $w) {
        if(is_null($activity1->{$t}) || is_null($activity2->{$t})) {
        	$result += $w;
            continue;
        }
        switch($t) {
            case 'learning_time': 
                $result += $w*sim_lower_bound($activity1->{$t}, $activity2->{$t});
                break;
            case 'age': //! age no longer supported for learning acitities 
                $result += $w*sim_logarithm($activity1->{$t}, $activity2->{$t}, 5.0, 100.0);
                break;
            case 'language':
            case 'id':
        	case 'module':
            case 'state':

                if($activity1->{$t} === $activity2->{$t}) {
                    $result += $w;
                }
                break;
            // (GS) LOP-1 geändert: Indentitätsvergleiche nur noch bei "module", standardmäßig Differenzvergleich im Intervall [0,1]
            default: {

            	assert($activity1->{$t} >= 0.0);
                assert($activity1->{$t} >= 0.0);
                assert($activity2->{$t} <= 1.0);
                assert($activity2->{$t} <= 1.0);
                $result += $w*(1-abs($activity1->{$t} - $activity2->{$t}));	
            }
        }

    }
    return $result;
}

/**
 * Vergleicht zwei Mengen von Lernaktivitäten nach dem MAXIMUM SET SIMILARITY und
 * bestimmt das bestmögliche Matching.
 * @param   Array   $activities1 Ein Array (nullbasierter Index => Objekt) mit Lernaktivitäts-Metadaten aus dem ersten zu vergleichenden Fall
 * @param   Array   $activities2 Ein Array (nullbasierter Index => Objekt) mit Lernaktivitäts-Metadaten aus dem zweiten zu vergleichenden Fall
 * @return  Object Ein Objekt mit den beiden Attributen sum (gibt die Übereinstimmung der Fälle an) und partner (liefert das beste Matching für die Lernaktivitäten)
 */
function compare_best_matching_activities(&$activities1, &$activities2) {
    // Initialisieren Teil 1
    $result = new stdClass();
    $result->sum = 0.0;
    $result->partner = array();
    
    // Arrays mit id's der Lernaktivitäten als Key und Value für späteres Mapping erstellen
    $activity_ids1 = array();
    foreach($activities1 as $activity){
    	$activity_ids1[$activity->id] = $activity->id;
    }
    $activity_ids2 = array();
    foreach($activities2 as $activity){
    	$activity_ids2[$activity->id] = $activity->id;
    }
    // Schnittmenge der Lernaktivitäten ermitteln und das Mapping für diese Aktivitäten schon mal setzen
    $activity_intersection = array_intersect_assoc($activity_ids1, $activity_ids2);
    $result->sum += count($activity_intersection); // Alle Kanten werden mit 1 bewertet
    $result->partner = $activity_intersection; // Die Zuordnungen sind die Identitäten der Schnittmenge
    
    // Differenzen der Aktivitäten der Fälle zu deren Schnittmenge ermitteln
    $diff1 = array_diff_assoc($activity_ids1, $activity_intersection);
    $diff2 = array_diff_assoc($activity_ids2, $activity_intersection);
    
    
    // --- Nun den Algorithmus von Gert Sauerstein auf die Differenzen anwenden ---
    
    // Initialisieren Teil 2
    $V = count($diff1)+count($diff2);
    $diff1_activities = array();
    foreach($diff1 as $d1){
    	$diff1_activities[] = $activities1[$d1];
    }
    $diff2_activities = array();
    foreach($diff2 as $d2){
    	$diff1_activities[] = $activities2[$d2];
    }
    $adjacence = get_adjacence_matrix($diff1_activities, $diff2_activities);
    $temp_partner = array();
    
    for($i = 0; $i < $V; ++$i) {
        $temp_partner[$i] = -1;
    }
    
    // Alle Knoten aus S1 durchlaufen: Für jeden Knoten aus S1 kann es höchstens 1 maximalen augmentierenden Pfad geben
    for($vStart = 0; $vStart < count($diff1); ++$vStart) {
        // Initialisiere Kanten-Markierungs-Array
        $scanned = array();
        for($i = 0; $i < $V; ++$i) {
            $scanned[$i] = array();
            for($j = 0; $j < $V; ++$j) {
                $scanned[$i][$j] = false;
            }
        }
        // Suche nach maximalem augmentierendem Pfad (Breitensuche mit Prioritätswarteschlange)
        $found = new PriorityQueue();
        $queue = new Queue();
        $queue->insert(new AugmentingPath($vStart));
        while(count($queue->elements) > 0) {
            $path = $queue->delete_next();
            $v = $path->vertex;
            // Suche alle Nachfolge-Knoten und füge sie zur Queue hinzu
            for($u = 0; $u < $V; ++$u) {
                if($u == $v || $scanned[$v][$u] || $adjacence[$v][$u] == 0 || (($path->size % 2) == 1) == ($temp_partner[$v] == $u)) {
                    continue; // Kante wurde schon besucht und wird übersprungen
                }
                $scanned[$v][$u] = true;
                // Pfad mit neuer Kante für nächste Rekursionsebene erstellen und zur Queue hinzufügen
                $newPath = new AugmentingPath($u, $path->size+1, $path->sum+$adjacence[$v][$u], $path);
                $queue->insert($newPath);
                // Prüfe, ob der Zielknoten frei ist -> es wurde ein vergrößernder Pfad gefunden
                if($temp_partner[$u] < 0) {
                    $found->insert($newPath);
                }
            }
        }
        $path = $found->delete_max();

        if($path != null) {
            // Falls der Pfad die Zuordnung vergrößert, wird er übernommen
            if($path->sum > 0.0) {
                $result->sum += $path->sum;
                // Übernahme des Pfads durch Umwandeln von GEBUNDENEN in FREIE KANTEN (und umgekehrt)
                $newPartner = array(); 
                for($i = 0; $i < $V; ++$i) {
                    $newPartner[$i] = $temp_partner[$i];
                }
                for($v = $path; $v->next != null; $v = $v->next) {
                    $newPartner[$v->vertex] = -1;
                    $newPartner[$v->next->vertex] = -1;
                }
                for($v = $path; $v->next != null; $v = $v->next) {
                    if($temp_partner[$v->vertex] < 0 || $temp_partner[$v->vertex] != $v->next->vertex) {
                        $newPartner[$v->vertex] = $v->next->vertex;
                        $newPartner[$v->next->vertex] = $v->vertex;
                    }
                }
                $temp_partner = $newPartner;
            }
        }

    }
    
    foreach($temp_partner as $tp_key => $tp_val) {
    	$result->partner[$tp_key] = $tp_val;
    }
    
    return $result;
}

/**
 * Subroutine für compare_best_matching_activities:
 * Bestimmt die Adjazenzmatrix für den MAXIMUM SET SIMILARITY Algorithmus.
 * @param   Array   $activities1 Ein Array (nullbasierter Index => Objekt) mit Lernaktivitäts-Metadaten aus dem ersten zu vergleichenden Fall
 * @param   Array   $activities2 Ein Array (nullbasierter Index => Objekt) mit Lernaktivitäts-Metadaten aus dem zweiten zu vergleichenden Fall
 * @return  Array Ein zweidimensionales Array mit den Kantengewichten (Ähnlichkeitsmaße zwischen jeweils zwei Lernaktivitäten)
 */
function get_adjacence_matrix(&$activities1, &$activities2) {
	$result = array();
    $V1 = count($activities1); // !(AS) Schreibe die Anzahl der Aktivitäten hier in eine Variable, um sie in den Schleifendurchläufen nicht immer wieder neu zu berechnen.
    $V2 = count($activities2);
    $V = $V1 + $V2;
    $i = 0;
    for($i = 0; $i < $V; ++$i) {
    	$result[$i] = array();
        for($j = 0; $j < $V; ++$j) {
        	$result[$i][$j] = 0.0;
            if($i < $V1 && $j >= $V1) {
                $result[$i][$j] = compare_activity($activities1[$i], $activities2[$j-$V1]);
                if($result[$i][$j] < ILMS_EDGE_LIMIT) {
                	$result[$i][$j] = 0;
                }
                continue;
            }
            if($i >= $V1 && $j < $V1) {
                $result[$i][$j] = -$result[$j][$i]; // -compare_activity($activities1[$j], $activities2[$i-count($activities1)]);
            }
        }
    }
    /* (GS) TESTCASE, max. similarity 1.5 / matching  0 => 6, 1 => 4, 2 => 5, 3 => NULL
     * return array (
        //         10     11     12     13     20     21     22
        0 => array( 0.00, +0.00, +0.00, +0.00, +0.75, +0.00, +0.50), // 10
        1 => array(-0.00,  0.00, +0.00, +0.00, +0.50, +0.25, +0.00), // 11
        2 => array(-0.00, -0.00,  0.00, +0.00, +0.00, +0.50, +0.25), // 12
        3 => array(-0.00, -0.00, -0.00,  0.00, +0.00, +0.00, +0.25), // 13
        4 => array(-0.75, -0.50, -0.00, -0.00,  0.00, +0.00, +0.00), // 20
        5 => array(-0.00, -0.25, -0.50, -0.00, -0.00,  0.00, +0.00), // 21
        6 => array(-0.50, -0.00, -0.25, -0.25, -0.00, -0.00,  0.00), // 22
    );*/
    return $result;
}

/**
 * Vergleicht die aktuelle (letzte) Lernaktivität beider Fälle nach einem Identitätsvergleich miteinander.
 * @param   Array   $history1   Historie des ersten Falls, welcher die aktuelle Lernaktivität als Schlüssel mit höchstem Wert enthält
 * @param   Array   $history2   Historie des ersten Falls, welcher die aktuelle Lernaktivität als Schlüssel mit höchstem Wert enthält
 * @param   Array   $mapping12  Bestmögliches Mapping der Lernaktivitäten des ersten Falls auf Lernaktivitäten des zweiten Falls. Ordnet die Lernaktivitäts-IDs des zweiten Falls den Lernaktivitäts-IDs des ersten Falls zu oder enthält -1, falls es für eine Lernaktivität keinen Partner gibt.
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function compare_current_activity(&$history1, &$history2, &$mapping12) {
	$c1 = count($history1);
    $c2 = count($history2);
	if($c1 < 1 && $c2 < 1) {
		return 1.0;
	}
    if($c1 < 1 || $c2 < 1) {
        return 0.0;
    }
    $activity1 = end($history1);
    $activity2 = end($history2);
    if(array_key_exists($activity1->id, $mapping12) && $mapping12[$activity1->id] >= 0) {
    	return $mapping12[$activity1->id] == $activity2->id ? 1.0 : 0.0;
    } else {
    	return 0.0;
    }
}

/**
 * Vergleicht die Beziehungen zwischen den Folgelernaktivitäten und der jeweils aktuellen Lernaktivität im semantischen Netz.
 * @param   Array   $relations1     Ein Array mit Daten für bei der aktuellen Lernaktivität des ersten Falls beginnenden Beziehungen
 * @param   Array   $relations2     Ein Array mit Daten für bei der aktuellen Lernaktivität des zweiten Falls beginnenden Beziehungen
 * @param   Array   $mapping12        Über MAXIMUM SET SIMILARITY bestimmtes bestmögliches Mapping der Folgelernaktivitäten (Schlüssel und Werte sind IDs der Lernaktivitäten)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function compare_activity_relations(&$relations1, &$relations2, &$mapping12) {
    $rel1 = get_relation_array($relations1);
    $rel2 = get_relation_array($relations2);
    if((count($rel1) < 1 && count($rel2) < 1)) {
    	return 1.0;
    }
    $schnittmenge = 0;
    $r1_count = 0;
    $r2_count = 0;
    // Durchlaufe alle semantische Beziehungen aus Fall 1 und prüfe auf Entsprechungen in Fall 2
    foreach($rel1 as $a => $relations) {
        if(!array_key_exists($a, $mapping12) || ($a2 = $mapping12[$a]) < 0) {

    		continue;
    	}

        $c1 = count($relations);
        $r1_count += $c1;
        $c2 = array_key_exists($a2, $rel2) ? count($rel2[$a2]) : 0;
        $r2_count += $c2;
        if($c1 > 0 && $c2 > 0) {
            foreach($relations as $r) {
                if(in_array($r, $rel2[$a2])) {
            		++$schnittmenge;
            	}
            }

        }
    }
    // Normalisiere die Übereinstimung in Abhängigkeit der Anzahl Lernaktivitäten im Matching
    return $schnittmenge == 0 ? 0.0 : $schnittmenge/($r1_count+$r2_count-$schnittmenge);        	
}    


/**
 * Analysiert ein Array mit semantischen Beziehungen (wird intern in compare_activity_relations() benutzt).
 * @param Array $relations  Ein Array mit Daten für bei der aktuellen Lernaktivität des Falls beginnenden semantischen Beziehungen. Jedes Element ist ein Objekt mit den Eigenschaften activityid (ID der Ziel-Lernaktivität) und semantic_type (Art der Beziehung). 
 * @return Array Ein assoziatives Array, das der ID jeder Lernaktivität des Kurses ein (nicht-assoziatives) Array mit alle semantischen Beziehungen zuweist, die zwischen der aktuellen Lernaktivität und der jeweiligen Lernaktivität bestehen.
 */
function get_relation_array(&$relations) {
    $result = array();
    foreach($relations as $r) {
        if(array_key_exists($r->activityid, $result)) {
            $result[$r->activityid][] = $r->semantic_type;
        } else {
            $result[$r->activityid] = array($r->semantic_type);
        }
    }
    return $result;
}

/**
 * Vergleicht die Zustände der aktuellen und Folge-Lernaktivitäten zwischen zwei Fällen.
 * @param   Array   $activities1    Array mit Objekten, die im Attribut state den Zustand der Lernaktivitäten des ersten Falls enthalten
 * @param   Array   $activities2    Array mit Objekten, die im Attribut state den Zustand der Lernaktivitäten des zweiten Falls enthalten
 * @param   Array   $mapping12      Über MAXIMUM SET SIMILARITY bestimmtes bestmögliches Mapping der Lernaktivitäten (Schlüssel und Werte sind IDs der Lernaktivitäten)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function compare_states(&$activities1, &$activities2, &$mapping12) {
	$result = 0.0;
    $activity_count = 0;
    if(count($activities1) < 1 && count($activities2) < 1) {
    	return 1.0;
    }
   	foreach($activities1 as $a) {
      	if(($a2 = $mapping12[$a->id]) < 0) {
       		continue;
        }
        ++$activity_count;
        
        if(is_null($a->state) || is_null($activities2[$mapping12[$a->id]]->state) || $a->state === $activities2[$mapping12[$a->id]]->state) {
            $result += 1.0;
        }
    }
    return $activity_count == 0 ? 0.0 : $result/$activity_count;
}

/**
 * Vergleicht die Liste der bereits besuchten Lernaktivitäten nach dem HISTORY COMPARE-Algorithmus.
 * @param Array $history1       Nach nullbasierten Schlüssel sortierte Liste mit History-Einträgen des 1. Falls
 * @param Array $activities1    Liste mit Meta- und Zustandsdaten der Lernaktivitäten des 1. Falls // !(AS) Wird in der Funktion nicht verwendtet und deswegen von mir gelöscht
 * @param Array $history2       Nach nullbasierten Schlüssel sortierte Liste mit History-Einträgen des 2. Falls
 * @param Array $activities2    Liste mit Meta- und Zustandsdaten der Lernaktivitäten des 2. Falls // !(AS) Wird in der Funktion nicht verwendtet und deswegen von mir gelöscht
 * @param Array $mapping12      Eine Mapping-Liste, welche den IDs von Lernaktivitäten aus dem 1. Fall IDs von Lernaktivitäten des 2. Falls zuweist, wenn sich diese ähnlich sind. Der Wert -1 wird verwendet, wenn die Lernaktivität keinen Partner besitzt, mit dem sie in Beziehung steht.
 * @return Object  Ein Objekt mit den Eigenschaften sum (Anzahl der übereinstimmenden Lernaktivitäten in der History) und mapping (Gefundene Zuordnung von Lernaktivitäten in der History).
 */
function compare_history($history1, $history2, $mapping12) {
    $result = new stdClass;
    $solutions = array();
    $max_solution_index = null; 
    if(count($history1) == 0 && count($history2) == 0) {
        $result->mapping = array();
        $result->sum = 1.0;
        return $result;
    }
    foreach($history1 as $h1) {
    	if(!(array_key_exists($h1->id, $mapping12)) || $mapping12[$h1->id] < 0) {
    		// Die Lernaktivität besitzt keine Entsprechung im 2. Fall -> ist irrelevant, überspringen
            continue;
    	}
    	if(count($solutions) < 1) {
    		// Es wurde überhaupt noch keine Lösung gefunden -> Suche passende Lösung in Lernaktiväten des 2. Falls
            foreach($history2 as $h2) {
            	if($h2->id == $mapping12[$h1->id]) {
            		// Teillösung gefunden -> hinzufügen und weiter mit nächster Aktivität $h1
                    $solutions[0] = array($h1->idx => $h2->idx);
                    $max_solution_index = 0;
                    break;
            	}
            }
    	} else {
    		$min = null;
            $found = false;
    		// Falls schon einer oder mehrere Lösungen vorhanden sind, betrachte jede einzeln nacheinander
            foreach($solutions as $sol_index => $sol) {
                // Suche passende Lernaktivität, welche NACH der letzten gefundenen Lernaktivität liegt
                $last = end($sol);
                if($min == null || $last < $min) {
                    $min = $last;
                }
                for($h2 = $last + 1; $h2 < count($history2); $h2++) { //! (AS) hier war ein "<=". Änderung könnte große Folgen haben!
                	if($history2[$h2]->id == $mapping12[$h1->id]) {
                		// Gefundene Teillösung kann verbessert werden -> Erhöhe um 1 Kante und weiter mit nächster Aktivität $h1
                        $solutions[$sol_index][$h1->idx] = $h2;
                        if(is_null($max_solution_index) || count($solutions[$sol_index]) > count($solutions[$max_solution_index])) {
                        	$max_solution_index = $sol_index;
                        }
                        $found = true;
                        break;
                	}
                }
    		}
            // Falls überhaupt keine Lösung gefunden wird, suche nach einer neuen Lösung
            if(!$found && !is_null($min)) {
            	for($h2 = 1; $h2 < $min; $h2++) { //! (AS) hier war ein "<=". Änderung könnte große Folgen haben!
            		if($history2[$h2]->id == $mapping12[$h1->id]) {
            			// Neue Lösung erstellen
                        $solutions[][$h1->idx] = $h2; // (GS) Der Maximum-Zähler braucht nicht erhöht zu werden, da die neue lediglich Lösung 1 zählt und damit zunächst kein Maximum wird
                        break;
            		}
            	}
            }
    	}
    }
    if(is_null($max_solution_index)) {
        $result->mapping = array();
        $result->sum = 0;
        return $result;
    }
    $result->mapping = $solutions[$max_solution_index];
    $result->sum = count($solutions[$max_solution_index]);
    return $result;
}

// Ähnlichkeitsfunktionen //////////////////////////////////////////////////////

/**
 * Bestimmt die logarithmische Ähnlichkeit zweier numerischer Werte
 * @param   float   $value1 Erster zu vergleichender Wert
 * @param   float   $value1 Zweiter zu vergleichender Wert
 * @param   float   $a      Untere Schranke des Intervalls zulässiger Werte
 * @param   float   $b      Obere Schranke des Intervalls zulässiger Werte
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function sim_logarithm($value1, $value2, $a, $b) {
    assert($value1 > 0.0);
    assert($value2 > 0.0);
    assert($a > 0.0);
    assert($b > $a);
    assert($a != $b);
	return 1.0-abs(log($value1)-log($value2))/(log($b)-log($a));
}

/**
 * Bestimmt die numerische Ähnlichkeit mit unterer Grenze a.
 * @param   float   $value1 Erster zu vergleichender Wert
 * @param   float   $value1 Zweiter zu vergleichender Wert
 * @param   float   $a      Untere Schranke des Intervalls zulässiger Werte (Vorgabe: 0.0)
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function sim_lower_bound($value1, $value2, $a = 0.0) {
	$max = max($value1, $value2);
    //assert($max > $a); //assert()-Fkt. füllt das Server Error Log zu sehr.
    //! Änderung von Andre Scherl: vermeide Division durch Null
    if($max-$a == 0){
    	return 1.0;
    }else{
    	return 1.0-abs($value1-$value2)/($max-$a);
    }
}

/**
 * Bestimmt die numerische Ähnlichkeit mit unterer Grenze a.
 * @param   float   $value1 Erster zu vergleichender Wert
 * @param   float   $value1 Zweiter zu vergleichender Wert
 * @param   float   $b      Obere Schranke des Intervalls zulässiger Werte
 * @return  float   Ähnlichkeit als numerischer Wert im Intervall [0,1]
 */
function sim_upper_bound($value1, $value2, $b) {
    $min = min($value1, $value2);
    //assert($min < $b);// assert()-Fkt. füllt das Server Error Log zu sehr.
    //! Änderung von Andre Scherl: vermeide Division durch Null
    if($b-$min == 0){
    	return 1.0;
    }else{
    	return 1.0-abs($value1-$value2)/($b-$min);
    }
}
?>
