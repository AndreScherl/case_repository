<?php
/*
 * Parse the current case
 *
 * Copyright (C) 2012, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

 require_once('../../config.php');
 require_once($CFG->libdir.'/weblib.php');
 require_once($CFG->libdir.'/dmllib.php');
 require_once($CFG->libdir.'/datalib.php');
 require_once("ilms_case_retrieval.php");
 
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
 
 print(json_encode($current_case));
 
?>
 