<?php
/*
 * Parse all cases of database
 *
 * Copyright (C) 2012, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

 require_once('../../config.php');
 require_once($CFG->libdir.'/weblib.php');
 require_once($CFG->libdir.'/dmllib.php');
 require_once($CFG->libdir.'/datalib.php');
 require_once($CFG->dirroot."/blocks/case_repository/ilms_case_retrieval.php");
 require_once($CFG->dirroot."/blocks/case_repository/ilms_cases.php");
 
 global $CFG, $DB;
 $sql = "SELECT * FROM {ilms_cases} c ORDER BY id";   
 $all_cases = $DB->get_records_sql($sql);
 
 foreach($all_cases as $case){
 	$unserialized_case = unserialize(stripslashes($case->serialized_case));
 	$case->serialized_case = json_encode($unserialized_case);
 }
 
 print(json_encode($all_cases));
 
?>
 