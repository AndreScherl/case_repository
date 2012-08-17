<?php

/* iLMS - Abfrage- / Anzeige-Seite der Fallbibliothek mit allen dort enthaltenen Fällen 
 * 
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

error_reporting(E_ALL);

global $CFG, $DB;
global $USER;

require_once ('../../config.php');
require_once ($CFG->libdir . '/weblib.php');
require_once ($CFG->libdir . '/dmllib.php');
require_once ($CFG->libdir . '/accesslib.php');
require_once ($CFG->dirroot . '/blocks/case_repository/dmllib2.php');
require_once ($CFG->dirroot . '/blocks/case_repository/ilms_cases.php');

function format_learner_value($u, $value) {
	global $BLOCK_USER_PREFS;
	if (is_null($value)) {
		return "NULL";
	}
	if ($u == 'age') {
		echo number_format($u->value, 1);
	} else {
		echo get_string("learner_{$u}_level" . ($value == 0.0 ? 1 : ceil($value * 5)), $BLOCK_USER_PREFS) . " (" . number_format($value, 3) . ")";
	}
}

function format_activity_value($a, $value) {
	global $BLOCK_CASE_REPO, $modnames;
	if (is_null($value)) {
		return "NULL";
	}
	switch ($a) {
		case 'age' :
			echo number_format($value, 1);
			break;
		case 'learning_time' :
			echo number_format($value, 2);
			break;
		case 'module' :
			echo $modnames[$value];
			break;
		default :
			echo get_string("activity_{$a}_level" . ($value == 0.0 ? 1 : ceil($value * 5)), $BLOCK_CASE_REPO) . " (" . number_format($value, 3) . ")";
	}
}

/** Größe des Blocks aus Fall-Datensätzen, der gleichzeitig in den Hauptspeicher gelesen werden soll */
define("CASES_BLOCK_COUNT", 500);

$user_id = optional_param('user', $USER->id, PARAM_INT);
$switchrole = optional_param('switchrole', -1, PARAM_INT);

$BLOCK_CASE_REPO = "block_case_repository";
$BLOCK_USER_PREFS = "block_user_preferences";
$FORMAT_ILMS = "format_ilms";

// Parameter prüfen
if (!$user = $DB->get_record('user', array('id' => $user_id))) {
	error(get_string('error_invalid_user', $BLOCK_CASE_REPO));
}

$navigation = array (
	array (
		'name' => get_string('case_repository',
		$BLOCK_CASE_REPO
	),
	'link' => "$CFG->wwwroot/block/case_repository/view.php",
	'type' => 'config'
),);
print_header(get_string('case_repository', $BLOCK_CASE_REPO), get_string('case_repository', $BLOCK_CASE_REPO), build_navigation($navigation));

// Anmeldung prüfen, gegebenenfalls anmelden
if ($switchrole == 0) {
	role_switch($switchrole, $context);
}
require_login($course->id);
if ($switchrole > 0) {
	role_switch($switchrole, $context);
	require_login($course->id);
}

$context = get_context_instance(CONTEXT_SYSTEM, null);
require_capability('block/case_repository:view_repository', $context, $USER->id);

$user_attribs = array (
	'spoken_language',
	'reading',
	'writing',
	'linguistic_requirement',
	'logical_requirement',
	'social_requirement',
	'pc_knowledge',
	'general_knowledge',
	'learningstyle_perception',
	'learningstyle_organization',
	'learningstyle_perspective',
	'learningstyle_input',
	'difficulty',
	'motivation',
	'qualification',
	'license',
	'aim',
	'expected_grade',
	'certificate',
	'ability',
	'interest',
	'hobby',
	'learningstyle_processing',
	'age',
	
);
$activity_meta = array (
	'linguistic_requirement',
	'logical_requirement',
	'social_requirement',
	'learningstyle_perception',
	'learningstyle_organization',
	'learningstyle_perspective',
	'learningstyle_input',
	'difficulty',
	'learningstyle_processing',
	'age',
	'learning_time',
	'module',
	'state'
);
$modnames = array ();
if ($allmods = $DB->get_records("modules")) {
	foreach ($allmods as $mod) {
		$modnames[$mod->id] = get_string("modulename", "$mod->name");
	}
}

// (GS) TODO Die Seite unterstützt momentan keine Suche in der Fallbasis, sondern listet nur alle Fälle tabellarisch auf

// Suchformular
/*echo "\n<h4><a name=\"filter\"></a>".get_string('header_searchform', $BLOCK_CASE_REPO)."</h4>";
echo "\n<div><form method=\"post\" action=\"view.php#results\">";
echo "\n  <fieldset><legend>".get_string('legend_usermeta', $BLOCK_CASE_REPO)."</legend><table>";
foreach($user_attribs as $u) {
    echo "\n    <tr>";
    echo "\n      <td class=\"attrib_name\">".get_string("learner_$u", $BLOCK_USER_PREFS)."</td>";
    if(strncmp(get_string("title_subtype_$u", $BLOCK_USER_PREFS), '[[', 2) != 0) { 
        echo "\n      <td class=\"attrib_value\">&nbsp;".get_string("title_subtype_$u", $BLOCK_USER_PREFS).":&nbsp;</td><td><input type=\"text\" name=\"learner_{$u}_subtype\"/></td>";
    } else {
    	echo "<td colspan=\"2\"> </td>";
    }
    switch($u) {
        case 'age':
            echo "\n      <td class=\"attrib_value\">&nbsp;".get_string('search_min', $BLOCK_CASE_REPO).":&nbsp;</td><td><input type=\"text\" value=\"5\" maxlength=\"3\" size=\"3\" name=\"learner_{$u}_min\"/></td><td class=\"attrib_value\">&nbsp;".get_string('search_max', $BLOCK_CASE_REPO).":&nbsp;</td><td><input value=\"100\" maxlength=\"3\" size=\"3\" type=\"text\" name=\"learner_{$u}_max\"/></td>";
            break;
        default:
            echo "\n      <td class=\"attrib_value\">&nbsp;".get_string('search_min', $BLOCK_CASE_REPO).":&nbsp;</td><td><select size=\"1\" name=\"learner_{$u}_min\">";
            for($i = 1; $i < 6; $i++) {
    	       echo "<option value=\"".($i/5-0.2)."\" ".($i == 1 ? "selected=\"selected\" " : '').">".get_string("learner_{$u}_level{$i}", $BLOCK_USER_PREFS)."</option>";
            }
            echo "</select></td>";
            echo "\n      <td class=\"attrib_value\">&nbsp;".get_string('search_max', $BLOCK_CASE_REPO).":&nbsp;</td><td><select size=\"1\" name=\"learner_{$u}_max\">";
            for($i = 1; $i < 6; $i++) {
                echo "<option value=\"".($i/5)."\" ".($i == 5 ? "selected=\"selected\" " : '').">".get_string("learner_{$u}_level{$i}", $BLOCK_USER_PREFS)."</option>";
            }
            echo "</select></td>";
            echo "\n    </tr>";
    }
}
echo "\n  </table></fieldset>";
echo "\n  <fieldset><legend>".get_string('legend_appliance', $BLOCK_CASE_REPO)."</legend><table>";
echo "\n    <tr>";
echo "\n      <td class=\"attrib_name\">".get_string("appliance", $BLOCK_CASE_REPO)."</td>";
echo "\n      <td class=\"attrib_value\">&nbsp;".get_string('search_min', $BLOCK_CASE_REPO).":&nbsp;</td><td><input type=\"text\" name=\"appliance_min\" value=\"0.0\" maxlength=\"5\" size=\"5\"/></td><td class=\"attrib_value\">&nbsp;".get_string('search_max', $BLOCK_CASE_REPO).":&nbsp;</td><td><input type=\"text\" name=\"appliance_max\" value=\"1.0\" maxlength=\"5\" size=\"5\"/></td>";
echo "\n    </tr>";
echo "\n    <tr>";
echo "\n      <td class=\"attrib_name\">".get_string("used_count", $BLOCK_CASE_REPO)."</td>";
echo "\n      <td class=\"attrib_value\">&nbsp;".get_string('search_min', $BLOCK_CASE_REPO).":&nbsp;</td><td><input type=\"text\" name=\"used_count_min\" value=\"1\" maxlength=\"5\" size=\"5\"/></td><td class=\"attrib_value\">&nbsp;".get_string('search_max', $BLOCK_CASE_REPO).":&nbsp;</td><td><input type=\"text\" name=\"used_count_max\" value=\"10000\" maxlength=\"8\" size=\"8\"/></td>";
echo "\n    </tr>";
echo "\n  </table></fieldset>";
echo "\n  <p><input type=\"submit\" value=\"".get_string('submit', $BLOCK_CASE_REPO)."\"/></p>";
echo "\n</form></div>";*/

// Daten als Tabelle anzeigen
echo "\n<h4><a name=\"results\"></a>" . get_string('header_results', $BLOCK_CASE_REPO) . "</h4>";
echo "\n<table border=\"1\" class=\"case_repo\">";
echo "\n  <tr>";
echo "\n    <th class=\"case_repo\">ID</th>";
echo "\n    <th class=\"case_repo\" colspan=\"2\"></th>";
echo "\n    <th class=\"case_repo\">" . get_string('title_attribute', $BLOCK_USER_PREFS) . "</th>";
echo "\n    <th class=\"case_repo\">" . get_string('title_value2', $BLOCK_USER_PREFS) . "</th>";
echo "\n  </tr>";
$sql = "SELECT * FROM {$CFG->prefix}ilms_cases c";
if ($cases = $DB->get_records_sql($sql, null, $recno, CASES_BLOCK_COUNT)) {
	foreach ($cases as $c) {
		$case = unserialize(stripslashes($c->serialized_case));
		$rows = 2 + count($user_attribs) + (1 + count($activity_meta)) + count($case->activity_meta) + (2 + count($activity_meta)) * count($case->history);
		$first_row = true;
		// Lerner-Metadaten anzeigen
		foreach ($user_attribs as $u) {
			echo "\n  <tr>";
			if ($first_row) {
				echo "\n    <td rowspan=\"$rows\" class=\"case_repo\">$c->id</td>";
			}
			if ($u == 'spoken_language') { // (GS) Annahme, dass 'spoken_language' das erste Attribut ist
				echo "\n    <td colspan=\"2\" rowspan=\"" . count($user_attribs) . "\" class=\"case_repo\">" . get_string('legend_usermeta', $BLOCK_CASE_REPO) . ":&nbsp;</td>";
			}
			echo "\n    <td class=\"case_repo\">" . get_string("learner_$u", $BLOCK_USER_PREFS) . "</td>";
			echo "\n    <td class=\"case_repo\"><table style=\"width:100%\">";
			$empty = true;
			foreach ($case->learner_meta as $l) {
				if ($l->attribute != $u) {
					continue;
				}
				echo "\n  <tr>";
				if ($l->subtype != '') {
					echo "<td>$l->subtype:&nbsp;</td>";
				}
				echo "<td style=\"text-align:right\">";
				echo format_learner_value($u, $l->value);
				echo "</td></tr>";
				$empty = false;
			}
			if ($empty) {
				echo "<tr><td>N/A</td></tr>";
			}
			echo "</table></td>";
			echo "\n  </tr>";
			$first_row = false;
		}
		// History
		$first_row = true;
		foreach ($case->history as $h) {
			echo "\n  <tr>";
			if ($first_row) {
				echo "<td rowspan=\"" . ((2 + count($activity_meta)) * count($case->history)) . "\" class=\"case_repo\">" . get_string('legend_history', $BLOCK_CASE_REPO) . ":&nbsp;</td>";
			}
			echo "<td rowspan=\"" . (2 + count($activity_meta)) . "\" class=\"case_repo\">$h->idx</td><td class=\"case_repo\">ID:&nbsp;</td><td class=\"case_repo\">$h->id</td>";
			echo "</tr>";
			echo "\n  <tr><td class=\"case_repo\">" . get_string('time_visited', $BLOCK_CASE_REPO) . ":&nbsp;</td><td class=\"case_repo\">" . userdate($h->timemodified) . "<br/>(" . format_time(time() - $h->timemodified) . ")</td></tr>";
			foreach ($activity_meta as $a) {
				if ($a == 'state') {
					echo "<tr><td class=\"case_repo\">" . get_string("type_state", $BLOCK_CASE_REPO) . ":&nbsp;</td><td class=\"case_repo\">";
					echo is_null($case->activity_meta[$h->id]->state) ? "NULL" : get_string($case->activity_meta[$h->id]->state, $BLOCK_CASE_REPO);
				} else {
					echo "<tr><td class=\"case_repo\">" . get_string("activity_$a", $BLOCK_CASE_REPO) . ":&nbsp;</td><td class=\"case_repo\">";
					echo format_activity_value($a, $case->activity_meta[$h->id]-> {
						$a });
				}
				echo "</td></tr>";
			}
			$first_row = false;
		}
		// Sematische Beziehungen
		$first_row = true;

		foreach ($case->activity_meta as $a) {
			echo "\n  <tr>";
			if ($first_row) {
				echo "<td colspan=\"2\" rowspan=\"" . count($case->activity_meta) . "\" class=\"case_repo\">" . get_string('legend_semantic_net', $BLOCK_CASE_REPO) . ":&nbsp;</td>";
			}
			echo "<td class=\"case_repo\">ID: $a->id</td><td class=\"case_repo\">";
			$empty = true;
			foreach ($case->relations as $r) {
				if ($r->activityid == $a->id) {
					echo ($empty ? '' : ",<br/>") . get_string($r->semantic_type, $BLOCK_CASE_REPO);
					$empty = false;
				}
			}
			if ($empty) {
				echo "&ndash;";
			}
			echo "</td></tr>";
			$first_row = false;
		}
		// Lösung
		echo "\n  <tr><td colspan=\"2\" rowspan=\"" . (1 + count($activity_meta)) . "\" class=\"case_repo\">" . get_string('legend_solution', $BLOCK_CASE_REPO) . ":&nbsp;</td><td class=\"case_repo\">ID:&nbsp;</td><td class=\"case_repo\">$case->solution_activityid</td></tr>";
		foreach ($activity_meta as $a) {
			if ($a == 'state') {
				echo "<tr><td class=\"case_repo\">" . get_string("type_state", $BLOCK_CASE_REPO) . ":&nbsp;</td><td class=\"case_repo\">";
				echo is_null($case->activity_meta[$case->solution_activityid]->state) ? "NULL" : get_string($case->activity_meta[$case->solution_activityid]->state, $BLOCK_CASE_REPO);
			} else {
				echo "<tr><td class=\"case_repo\">" . get_string("activity_$a", $BLOCK_CASE_REPO) . ":&nbsp;</td><td class=\"case_repo\">";
				echo format_activity_value($a, $case->activity_meta[$case->solution_activityid]-> {
					$a });
			}
			echo "</td></tr>";
		}
		// Bewertung und Relevanz
		echo "<tr><td colspan=\"3\" class=\"case_repo\">" . get_string('appliance', $BLOCK_CASE_REPO) . "</td><td class=\"case_repo\">$c->appliance</td></tr>";
		echo "<tr><td colspan=\"3\" class=\"case_repo\">" . get_string('used_count', $BLOCK_CASE_REPO) . "</td><td class=\"case_repo\">$c->used_count</td></tr>";
		echo "<tr><td colspan=\"5\" class=\"linebreaker\"></td></tr>";
	}
	$reccount = count($cases);
} else {
	$reccount = 0;
}
echo "\n</table>";
echo "\n<p><cite>$reccount " . get_string('count_records', $BLOCK_CASE_REPO) . "</cite>";
if ($reccount >= CASES_BLOCK_COUNT) {
	echo "<br/><cite>" . get_string('more_records', $BLOCK_CASE_REPO) . "</cite>";
}
echo "</p>";
print_footer(null);
?>
