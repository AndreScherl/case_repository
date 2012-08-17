<?php
    /* iLMS - Klassendefinition für Fälle
     * 
	 * Copyright (C) 2007, Gert Sauerstein
	 * Edited by Andre Scherl, 17.09.2012
	 * You should have received a copy of the GNU General Public License
	 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
	 */

    class iLMSCase {
    
        /** @var int (optionale) ID des Falls, falls dieser in der Datenbank gespeichert ist */
        var $id;
        /** @var Array Metadaten des Lerners des repräsentierten Falls */
        var $learner_meta;
        /** @var int ID der aktuellen Lernaktivität des repräsentierten Falls */
        var $current_activityid;
        /** @var Array Meta- und Zustandsdaten der Lernaktivitäten im Kurs des repräsentierten Falls */
        var $activity_meta;
        /** @var Array Beziehungen zwischen der aktuellen und möglichen Folge-Lernaktivitäten des Falls */
        var $relations;
        /** @var int ID der Lösung des repräsentierten Falls */
        var $solution_activityid;
        /** @var float (optionales Attribut) Ähnlichkeit dieses Falls mit dem Referenzfall nach einem Fallvergleichs */
        var $similarity = null;
        
        var $similarity_learner_meta = null;
        var $similarity_activity_meta = null;
        var $similarity_history = null;
        var $similarity_relations = null;
        var $similarity_states = null;
        var $similarity_current_activity = null;
        
        /**
         * @var String (nur für Debugging) Entweder 'reject' oder 'adjust', falls die entsprechende Anpassung im REUSE-Schritt vorgenommen wurde, andernfalls null
         */
        var $action = null;
        
        /** @var Array (optionales Attribut) Mapping der Lernaktivitäten dieses Falls (Schlüssel) auf die Lernaktivitäten des Referenzfalls (Werte) nach einem Fallvergleichs */
        var $mapping = null;
        /** @var Array (optionales Attribut) Umgekehrte Zuordnung des Mappings: Von den Lernaktivitäten des Referenzfalls (Schlüssel) auf die Lernaktivitäten dieses Falls (Werte) */
        var $reverse_mapping = null;
        /** @var Array (optionales Attribut) Mapping in der History-Liste dieses Falls mit dem Referenzfall */
        var $history_mapping = null;
    
        /**
         * Erzeugt ein neues Objekt, das einen iLMS Fall repräsentiert.
         * @param Array     $learner_meta       Metadaten des Lerners des repräsentierten Falls
         * @param Array     $activities         Meta- und Zustandsdaten aller (sichtbaren) Lernaktivitäten im Kurs des repräsentierten Falls
         * @param Array     $history            Geordnete Liste mit Tracking-Informationen aller bisher besuchter Lernaktvitäten (Schlüssel ist der nullbasierte Index, 0 ist die zuerstbesuchte Lernaktivität, Werte sind Objekte mit den Eigenschaften idx, id und timemodified)
         * @param Array     $relations          Beziehungen zwischen der aktuellen (zuletzt besuchten) Lernaktivität und allen anderen Lernaktivitäten des Kurses
         * @param int       $current_activityid ID der aktuellen Lernaktivität des repräsentierten Falls (entspricht einem Schlüssel in $activities)
         * @param int       $solution_activityid (optional, Vorgabe NULL:) ID der Lernaktivität, welche letztendlich durch den Lerner gewählt wurde
         * @param int       $case_id            (optional, Vorgabe NULL:) ID des Falls, falls dieser in der Datenbank gespeichert ist
         * @return iLMSCase der erzeugte Fall
         */
        function iLMSCase($learner_meta, $activities, $history, $relations, $current_activityid, $solution_activity=null, $case_id=null) {
            $this->learner_meta = $learner_meta;
            $this->current_activityid = $current_activityid;
            $this->activity_meta = $activities;
            $this->history = $history;
            $this->relations = $relations;
            $this->id = $case_id;
            $this->solution_activityid = $solution_activity;
        }
    
    }
    
    class iLMSActivityMeta {
         var $linguistic_requirement = null;
         var $logical_requirement = null;
         var $social_requirement = null;
         var $learningstyle_perception = null;
         var $learningstyle_organization = null;
         var $learningstyle_perspective = null;
         var $learningstyle_input = null;
         var $difficulty;
         var $learningstyle_processing;
         //var $age;
         var $learning_time;
         var $module;
         var $state;
         var $id;
         var $language = "en";
         
         function iLMSActivityMeta() {
            $this->state = 'state_not_attempted';
         }
    }

?>
