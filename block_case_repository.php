<?php
    /* iLMS - Block mit Standardfunktionen für Fallbibliothek und Lerner-Adaption
     *        (der Block erzeugt beim Installieren außerdem das Datenmodell für iLMS)
     * 
     * Details zur Funktionsweise von Blöcken siehe entsprechende Moodle-Developer-Dokumentation.
     * 
	 * Copyright (C) 2007, Gert Sauerstein
	 * Edited by Andre Scherl, 17.09.2012
	 * You should have received a copy of the GNU General Public License
	 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
	 */

//require_once($CFG->libdir.'/weblib.php');
//require_once($CFG->libdir.'/accesslib.php');

class block_case_repository extends block_list {
	
    /**
     * Initialisiert diesen Block.
     * Definiert die aktuelle Version, den angezeigten Namen des Blocks und das Ausführungsintervall des Cron-Jobs.
     */
    function init() {
        $this->title = get_string('pluginname', 'block_case_repository');
    }
    
    function specialization() {
    }

    /** Liefert den Inhalt dieses Blocks als HTML-Fragment.
     *  Funktionsweise (insbesondere Caching) siehe Moodle-Doku.
     */
    function get_content() {
    	global $CFG, $USER;
        if ($this->content !== NULL) {
            return $this->content;
        }
        $course_id = required_param('id', PARAM_INT);
        $context = get_context_instance(CONTEXT_COURSE, $course_id);

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = get_string('footer', 'block_case_repository');

        // List items
        if(has_capability('block/case_repository:view_repository', $context, $USER->id)) {
            $this->content->items['case_repository'] = '<a href="'.$CFG->wwwroot.'/blocks/case_repository/view.php">'.get_string('menu_case_repository', 'block_case_repository').'</a>';
            $this->content->icons['case_repository'] = '<img src="'.$CFG->wwwroot.'/blocks/case_repository/pix/button_case_repository.gif" class="icon" alt="icon for case repository" />';
        }
        $this->content->items['info'] = '<a href="'.$CFG->wwwroot.'/blocks/case_repository/info.php">'.get_string('menu_info', 'block_case_repository').'</a>';
        $this->content->icons['info'] = '<img src="'.$CFG->wwwroot.'/pix/docs.gif" class="icon" alt="icon for iLMS documentation" />';
		
        return $this->content;
    }

    /** Die Titelleiste des Blocks wird immer angezeigt */
    function hide_header() {
        return false;
    }

    /** Optimale Breite dieses Blocks: 200 Pixel */
    function preferred_width() {
        return 200; // Default values: 180~210 px
    }
    
    /** Gibt an, dass dieser Block eine globale Konfigurationsseite besitzt (Seite mit den Einstellungen der Adaptionsparameter) */
    function has_config() {
        //return true;
        return false;
    }    

    /**
     * Führt den CRON-Job für die Fallblibliothek aus.
     * Verarbeitet alle noch nicht verarbeiteten Bewertungen und speichert alle noch nicht verarbeiteten Fälle in der Fallbasis.
     */
    function cron(){
        global $CFG;
        global $REPLACE_CASE_SIMILARITY_LIMIT, $MAX_ILMS_CASE_COUNT;
        global $ENABLE_REUSE_ADJUSTMENTS, $REUSE_LEARNER_ACTIONS;
        global $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $WEIGHT_FACTOR_LEARNERMETA, $WEIGHT_FACTOR_ACTIVITYMETA, $WEIGHT_FACTOR_FOLLOW_RELATIONS, $WEIGHT_FACTOR_CURRENT_ACTIVITY, $WEIGHT_FACTOR_HISTORY, $WEIGHT_FACTOR_STATES;
        require_once($CFG->dirroot.'/blocks/case_repository/ilms_store.php');
        mtrace("iLMS Cron Job ------------------------------------ \n  iLMS Plugins  (c) 2007 Gert Sauerstein \n  See documentation/info page for more details. \n");
        mtrace("  ATTENTION: iLMS logfile output (for debugging and testing) is in German ONLY!!!\n");
        mtrace("iLMS: <- Beginne Verarbeitung neuer Fälle ----\n");
        process_grades();
        mtrace("iLMS: -- Beende Verarbeitung neuer Fälle ----> ");
    }
     
}
