<?php
    /* iLMS - Definition der Vorgabe-Konfigurationswerte, 
     *        Funktionen zum Laden und Speichern der konfigurierbaren Parameter aus der Konfigurationsdatei
     * 
	 * Copyright (C) 2007, Gert Sauerstein
	 * Edited by Andre Scherl, 17.09.2012
	 * You should have received a copy of the GNU General Public License
	 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
	 */

    define('ILMS_CONFIG_PATH',  $CFG->dataroot.'/ilms');
    define('ILMS_CONFIG_FILE',  ILMS_CONFIG_PATH.'/config.php');

    $ILMS_YELLOW_MARKUP_LIMIT = 0.6;
    $MAX_ILMS_CASE_COUNT = 1000;
    $REPLACE_CASE_SIMILARITY_LIMIT = 0.9;

    $WEIGHT_FACTOR_LEARNERMETA = 0.3;      // Lerner-Metadaten: 30%
    $WEIGHT_FACTOR_ACTIVITYMETA = 0.15;     // Lernaktivitäten-Metadaten: 15%  
    $WEIGHT_FACTOR_FOLLOW_RELATIONS = 0.05; // Beziehungen: 5%
    $WEIGHT_FACTOR_CURRENT_ACTIVITY = 0.30; // Aktuelle / letzte Lernaktivität: 30%
    $WEIGHT_FACTOR_HISTORY = 0.10;          // History: 10%   
    $WEIGHT_FACTOR_STATES = 0.10;           // Zustände: 10%

    $WEIGHTS_LEARNERMETA =
        array(
                'spoken_language' => 0.02,
                'reading' => 0.02,
                'writing' => 0.02,
                'linguistic_requirement' => 0.10, // 3 Intelligenz-Ausprägungen -> 3 * 0.10 = 30%
                'logical_requirement' => 0.10,
                'social_requirement' => 0.10,
                'pc_knowledge' => 0.02, // Allgemeinwissen und Sprachkenntnisse 5 * 0.02 = 10%
                'general_knowledge' => 0.02,
                'learningstyle_perception' => 0.06, // Lerntypen der Lerner: 5 * 0.06 = 30%
                'learningstyle_organization' => 0.06,
                'learningstyle_perspective' => 0.06,
                'learningstyle_input' => 0.06,
                'difficulty' => 0.04, // Fachspezifisches Wissen pauschal 4%
                'motivation' => 0.02, // Motivation pauschal 2 %
                'qualification' => 0.025,  // Diverse Qualifikationen und Fertigkeiten: 6 * 0.025 = 15%
                'license' => 0.025,
                'aim' => 0.025,
                'expected_grade' => 0.025, // Lernziele: 2 * 0.025 = 5%
                'certificate' => 0.025,
                'ability' => 0.025,
                'interest' => 0.025,
                'hobby' => 0.025,
                'learningstyle_processing' => 0.06,
                'age' => 0.04, // Alter pauschal 4%
        );

    $WEIGHTS_ACTIVITYMETA =
        array(
            'linguistic_requirement' => 0.10, // 3 Anforderungen: 3 * 0.10 = 30%
            'logical_requirement' => 0.10,
            'social_requirement' => 0.10,
            'learningstyle_perception' => 0.06, // 5 Lerntyp-Ausprägungen: 5 * 0.06 = 30%
            'learningstyle_organization' => 0.06,
            'learningstyle_perspective' => 0.06,
            'learningstyle_input' => 0.06,
            'difficulty' => 0.10,        // Schwierigkeit (wichtig!) pauschal 10%
            'learningstyle_processing' => 0.06,
            'learning_time' => 0.05, // Zeitbedarf pauschal 5%
            'module' => 0.175, // Lernmodul (sehr wichtig!) pauschal 10%
            'id' => 0.025, // 1%: Exakter Vergleich über die ID der Lernaktivität, damit nur ein und dieselbe Lernaktivität identisch zu sich selbst ist
            'language' => 0.05,
        );
        
    $ENABLE_REUSE_ADJUSTMENTS = true;
    if($ENABLE_REUSE_ADJUSTMENTS) {
        $REUSE_LEARNER_ACTIONS = array(
                'linguistic_requirement' => array('DIFFTYPE' => 'lt', 'LIMIT' => 0.5, 'COMPARETO' => 'activity', 'ACTION' => 'reject', 'STRENGTH' => null),
                'logical_requirement' => array('DIFFTYPE' => 'lt', 'LIMIT' => 0.5, 'COMPARETO' => 'activity', 'ACTION' => 'reject', 'STRENGTH' => null),
                'social_requirement' => array('DIFFTYPE' => 'lt', 'LIMIT' => 0.5, 'COMPARETO' => 'activity', 'ACTION' => 'reject', 'STRENGTH' => null),
                'pc_knowledge' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'general_knowledge' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'learningstyle_perception' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'learningstyle_organization' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'learningstyle_perspective' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'learningstyle_input' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'difficulty' => array('DIFFTYPE' => 'diff', 'LIMIT' => 0.5, 'COMPARETO' => 'activity', 'ACTION' => 'reject', 'STRENGTH' => null),
                'motivation' => array('DIFFTYPE' => 'lt', 'LIMIT' => 0.5, 'COMPARETO' => 'learner', 'ACTION' => 'adjust', 'STRENGTH' => 0.1),
                'expected_grade' => array('DIFFTYPE' => 'lt', 'LIMIT' => 0.5, 'COMPARETO' => 'learner', 'ACTION' => 'adjust', 'STRENGTH' => 0.2),
                'learningstyle_processing' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
                'age' => array('DIFFTYPE' => 'none', 'LIMIT' => 0.0, 'COMPARETO' => 'learner', 'ACTION' => '', 'STRENGTH' => 0.0),
        );
    }

    /** Halbwertszeit für die Sicherheit, Vorgabewert: 1/4 Jahr (7889400 SI-Sekunden) */
    $HALF_VALUE_TIME = 7889400; 
    /** Prozentsatz der Sicherheit, ab dem Attributwerte ganz entfernt werden sollen ("leaky integrator"-Prinzip) */
    $LEAK_LIMIT = 0.1; 

    if(file_exists(ILMS_CONFIG_FILE) && is_file(ILMS_CONFIG_FILE) && is_readable(ILMS_CONFIG_FILE)) {
    	//echo "\n<!-- Lade iLMS-Konfiguration aus Datei (".ILMS_CONFIG_FILE.") -->\n";
        require_once(ILMS_CONFIG_FILE);
    } else {
        //echo "\n<!-- Verwendet Standard-Konfiguration für iLMS -->\n";
    }
	
	
    /**
     * Speichert die aktuelle iLMS-Konfiguration.
     * @return bool     true, falls die Konfiguration erfolgreich geschrieben werden konnte, false sonst
     */
    function save_iLMS_config_file($HALF_VALUE_TIME, $LEAK_LIMIT, $MAX_ILMS_CASE_COUNT, $REPLACE_CASE_SIMILARITY_LIMIT, $ILMS_YELLOW_MARKUP_LIMIT, $WEIGHT_FACTOR_LEARNERMETA, $WEIGHT_FACTOR_ACTIVITYMETA, $WEIGHT_FACTOR_FOLLOW_RELATIONS, $WEIGHT_FACTOR_CURRENT_ACTIVITY, $WEIGHT_FACTOR_HISTORY, $WEIGHT_FACTOR_STATES, $WEIGHTS_LEARNERMETA, $WEIGHTS_ACTIVITYMETA, $ENABLE_REUSE_ADJUSTMENTS = false, $REUSE_LEARNER_ACTIONS = null) {
        if(!is_dir(ILMS_CONFIG_PATH)) {
        	mkdir(ILMS_CONFIG_PATH, 0777, true);
        }
        if(!$file = fopen(ILMS_CONFIG_FILE, "w")) {
            return false;
        }
        $content = "<?php \n".'$MAX_ILMS_CASE_COUNT'." = $MAX_ILMS_CASE_COUNT; \n"
            .'$REPLACE_CASE_SIMILARITY_LIMIT'." = $REPLACE_CASE_SIMILARITY_LIMIT; \n"
            .'$WEIGHT_FACTOR_LEARNERMETA'." = $WEIGHT_FACTOR_LEARNERMETA; \n"
            .'$WEIGHT_FACTOR_ACTIVITYMETA'." = $WEIGHT_FACTOR_ACTIVITYMETA; \n"
            .'$WEIGHT_FACTOR_FOLLOW_RELATIONS'." = $WEIGHT_FACTOR_FOLLOW_RELATIONS; \n"
            .'$WEIGHT_FACTOR_CURRENT_ACTIVITY'." = $WEIGHT_FACTOR_CURRENT_ACTIVITY; \n"
            .'$WEIGHT_FACTOR_HISTORY'." = $WEIGHT_FACTOR_HISTORY; \n"
            .'$WEIGHT_FACTOR_STATES'." = $WEIGHT_FACTOR_STATES; \n"
            .'$HALF_VALUE_TIME'." = $HALF_VALUE_TIME; \n"
            .'$LEAK_LIMIT'." = $LEAK_LIMIT; \n"
            .'$WEIGHTS_LEARNERMETA'." = array(\n";
        foreach($WEIGHTS_LEARNERMETA as $a => $v) {
        	$content = $content."  '$a' => $v,\n";
        }
        $content = $content
            .");\n"
            .'$WEIGHTS_ACTIVITYMETA = array ('."\n";
        foreach($WEIGHTS_ACTIVITYMETA as $a => $v) {
            $content = $content."  '$a' => $v,\n";
        }
        $content = $content.");\n"
            .'$ENABLE_REUSE_ADJUSTMENTS = '.($ENABLE_REUSE_ADJUSTMENTS ? 'true' : 'false').";\n"
            .'$REUSE_LEARNER_ACTIONS = array ('."\n";
        foreach($REUSE_LEARNER_ACTIONS as $a => $v) {
            $content = $content."  '$a' => array(";
            foreach($v as $property => $value) {
                if(empty($value) || ($property == 'STRENGTH' && $v['ACTION'] == 'reject')) {
                    $content = $content."'$property' => null, ";
                    continue;
                }
            	if(is_numeric($value)) {
                    $content = $content."'$property' => $value, ";
                    continue;
                }
                $content = $content."'$property' => '$value', ";
            }
            $content = $content."),\n";
        }
        $content = $content.");\n";
	
        $content = $content.'$ILMS_YELLOW_MARKUP_LIMIT'." = $ILMS_YELLOW_MARKUP_LIMIT; \n ?> \n";
        $result = fwrite($file, $content) or die("cant write config file");
        fclose($file);
        if($result < count($content)) {
        	unlink(ILMS_CONFIG_FILE);
            return false;
        }
        chmod(ILMS_CONFIG_FILE, 0775);
        return true;
    }
?>