<?php
    /* iLMS - Globale Konfigurationsseite für die Lerner-Adaption
     * 
 	 * Copyright (C) 2007, Gert Sauerstein
 	 * Edited by Andre Scherl, 17.09.2012
 	 * You should have received a copy of the GNU General Public License
 	 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
	 */
  
     error_reporting(E_ALL);
     //require_once('../../config.php');
     require(dirname(dirname(dirname(__FILE__))).'/config.php');
	 require_once($CFG->libdir.'/adminlib.php');
     require_once($CFG->libdir.'/dmllib.php');
     require_once($CFG->libdir.'/moodlelib.php');
     require_once($CFG->libdir.'/weblib.php');
     require_once($CFG->dirroot.'/blocks/case_repository/ilms_config.php');
     
     $BLOCK_NAME = "block_case_repository";
     
     admin_externalpage_setup('dasis_case_repository');
	 echo $OUTPUT->header();
                            
     // Aktuelle Werte anzeigen
     echo "<form action=\"{$CFG->wwwroot}/blocks/case_repository/store_settings.php\" method=\"post\">";
     echo "<fieldset>\n  <legend style=\"font-weight:bold;font-size:12pt\">".get_string('config_legend_general', $BLOCK_NAME)."</legend>\n  <table>\n";
     echo "    <tr><td>".get_string('ILMS_YELLOW_MARKUP_LIMIT', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"YELLOW_MARKUP_LIMIT\" value=\"$ILMS_YELLOW_MARKUP_LIMIT\"/></td></tr>\n";
     echo "    <tr><td colspan=\"2\"><p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_yellow_markup", $BLOCK_NAME)."</p></td></tr>\n";
     echo "    <tr><td>".get_string('MAX_ILMS_CASE_COUNT', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"MAX_ILMS_CASE_COUNT\" value=\"$MAX_ILMS_CASE_COUNT\"/></td></tr>\n";
     echo "    <tr><td colspan=\"2\"><p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_case_count", $BLOCK_NAME)."</p></td></tr>\n";
     echo "    <tr><td>".get_string('REPLACE_CASE_SIMILARITY_LIMIT', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"REPLACE_CASE_SIMILARITY_LIMIT\" value=\"$REPLACE_CASE_SIMILARITY_LIMIT\"/></td></tr>\n";
     echo "    <tr><td colspan=\"2\"><p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_replace", $BLOCK_NAME)."</p></td></tr>\n";
     echo "  </table>\n</fieldset>\n";
     echo "<br/>\n";
     
     echo "<fieldset>\n  <legend style=\"font-weight:bold;font-size:12pt\">".get_string('config_legend_similarity', $BLOCK_NAME)."</legend>\n  <table>\n";
     echo "    <tr><td>".get_string('WEIGHT_FACTOR_LEARNERMETA', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHT_FACTOR_LEARNERMETA\" value=\"$WEIGHT_FACTOR_LEARNERMETA\"/></td></tr>\n";
     echo "    <tr><td>".get_string('WEIGHT_FACTOR_ACTIVITYMETA', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHT_FACTOR_ACTIVITYMETA\" value=\"$WEIGHT_FACTOR_ACTIVITYMETA\"/></td></tr>\n";
     echo "    <tr><td>".get_string('WEIGHT_FACTOR_FOLLOW_RELATIONS', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHT_FACTOR_FOLLOW_RELATIONS\" value=\"$WEIGHT_FACTOR_FOLLOW_RELATIONS\"/></td></tr>\n";
     echo "    <tr><td>".get_string('WEIGHT_FACTOR_CURRENT_ACTIVITY', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHT_FACTOR_CURRENT_ACTIVITY\" value=\"$WEIGHT_FACTOR_CURRENT_ACTIVITY\"/></td></tr>\n";
     echo "    <tr><td>".get_string('WEIGHT_FACTOR_HISTORY', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHT_FACTOR_HISTORY\" value=\"$WEIGHT_FACTOR_HISTORY\"/></td></tr>\n";
     echo "    <tr><td>".get_string('WEIGHT_FACTOR_STATES', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHT_FACTOR_STATES\" value=\"$WEIGHT_FACTOR_STATES\"/></td></tr>\n";
     foreach($WEIGHTS_LEARNERMETA as $a => $v) {
         echo "    <tr><td>".get_string('WEIGHTS_LEARNERMETA', $BLOCK_NAME).get_string("learner_$a", 'block_user_preferences').":&nbsp;</td><td><input type=\"text\" name=\"WEIGHTS_LEARNERMETA_$a\" value=\"$v\"/></td></tr>\n";
     } 
     foreach($WEIGHTS_ACTIVITYMETA as $a => $v) {
         echo "    <tr><td>".get_string('WEIGHTS_ACTIVITYMETA', $BLOCK_NAME).get_string("activity_$a", $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"WEIGHTS_ACTIVITYMETA_$a\" value=\"$v\"/></td></tr>\n";
     }
     echo "    <tr><td colspan=\"2\"><p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_casecompare", $BLOCK_NAME)."</p></td></tr>\n";
     echo "  </table>\n  </fieldset>\n";
     echo "<br/>\n";
     
     echo "<fieldset>\n  <legend style=\"font-weight:bold;font-size:12pt\">".get_string('config_legend_adjust', $BLOCK_NAME)."</legend>\n";
     echo "  <p><input type=\"checkbox\" value=\"true\"".($ENABLE_REUSE_ADJUSTMENTS ? ' checked="checked"' : '')." name=\"ENABLE_REUSE_ADJUSTMENTS\"> ".get_string('ENABLE_REUSE_ADJUSTMENTS', $BLOCK_NAME)."</p>\n";
     echo "  <p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_adjust_checkbox", $BLOCK_NAME)."</p>\n";
     echo "  <table>\n";
     echo "  <tr><th></th><th>".get_string('config_legend_difftype', $BLOCK_NAME).":</th><th>".get_string("config_legend_limit", $BLOCK_NAME).":</th><th>".get_string('config_legend_compareto', $BLOCK_NAME).":</th><th>".get_string('config_legend_action', $BLOCK_NAME).":</th><th>".get_string("config_legend_strength", $BLOCK_NAME).":</th><tr>";
     foreach($REUSE_LEARNER_ACTIONS as $a => $v) {
         echo "    <tr><td>".get_string("learner_$a", 'block_user_preferences').":&nbsp;</td><td><select size=\"1\" name=\"REUSE_LEARNER_ACTIONS_DIFFTYPE_$a\">"
                 ."<option value=\"none\" ".($v['DIFFTYPE'] == 'none' || empty($v) ? "selected=\"selected\"" : '').">".get_string("config_difftype_none", $BLOCK_NAME)."</option>"
                 ."<option value=\"lt\" ".($v['DIFFTYPE'] == 'lt' ? "selected=\"selected\"" : '').">".get_string("config_difftype_lesser_than", $BLOCK_NAME)."</option>"
                 ."<option value=\"gt\" ".($v['DIFFTYPE'] == 'gt' ? "selected=\"selected\"" : '').">".get_string("config_difftype_greater_than", $BLOCK_NAME)."</option>"
                 ."<option value=\"diff\" ".($v['DIFFTYPE'] == 'diff' ? "selected=\"selected\"" : '').">".get_string("config_difftype_diff", $BLOCK_NAME)."</option>"
             ."</select>&nbsp;</td><td> <input type=\"text\" size=\"4\" name=\"REUSE_LEARNER_ACTIONS_LIMIT_$a\" value=\"".number_format($v['LIMIT'], 2)."\"/>&nbsp;</td>"
             ."<td><select size=\"1\" name=\"REUSE_LEARNER_ACTIONS_COMPARETO_$a\">";
                 if(array_key_exists($a, $WEIGHTS_ACTIVITYMETA)) {
                     echo "<option value=\"activity\" ".($v['COMPARETO'] == 'activity' ? "selected=\"selected\"" : '').">".get_string("legend_activity_meta", $BLOCK_NAME)."</option>";
                 }
                 echo "<option value=\"learner\" ".($v['COMPARETO'] == 'learner' ? "selected=\"selected\"" : '').">".get_string("legend_usermeta", $BLOCK_NAME)."</option>"
             ."</select>&nbsp;</td>"
             ."<td><select size=\"1\" name=\"REUSE_LEARNER_ACTIONS_ACTION_$a\">"
                 ."<option value=\"reject\" ".($v['ACTION'] == 'reject' ? "selected=\"selected\"" : '').">".get_string("config_action_reject", $BLOCK_NAME)."</option>"
                 ."<option value=\"adjust\" ".($v['ACTION'] == 'adjust' ? "selected=\"selected\"" : '').">".get_string("config_action_adjust", $BLOCK_NAME)."</option>"
             ."</select>&nbsp;</td><td><input type=\"text\" size=\"4\" name=\"REUSE_LEARNER_ACTIONS_STRENGTH_$a\" value=\"".(is_null($v['STRENGTH']) ? '' : number_format($v['STRENGTH'], 2))."\"/>&nbsp;</td>"
             ."</tr>";
     }
     echo "  </table>\n";
     echo "  <div class=\"helpdescription\"><p><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_adjust", $BLOCK_NAME)."</p>".get_string("config_description_adjust2", $BLOCK_NAME)."</div>\n";
     echo "  </fieldset>\n";
     echo "<br/>\n";
     
     echo "<fieldset>\n  <legend style=\"font-weight:bold;font-size:12pt\">".get_string('config_legend_autoadjust', $BLOCK_NAME)."</legend>\n  <table>\n";
     echo "    <tr><td>".get_string('HALF_VALUE_TIME', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"HALF_VALUE_TIME\" value=\"$HALF_VALUE_TIME\"/></td></tr>\n";
     echo "    <tr><td colspan=\"2\"><p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_half_value_time", $BLOCK_NAME)."</p></td></tr>\n";
     echo "    <tr><td>".get_string('LEAK_LIMIT', $BLOCK_NAME).":&nbsp;</td><td><input type=\"text\" name=\"LEAK_LIMIT\" value=\"$LEAK_LIMIT\"/></td></tr>\n";
     echo "    <tr><td colspan=\"2\"><p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_leak_limit", $BLOCK_NAME)."</p></td></tr>\n";
     echo "  </table>\n</fieldset>\n";
     echo "<br/>\n";
     
     echo "<fieldset>\n  <legend style=\"font-weight:bold;font-size:12pt\">".get_string('config_legend_defaults', $BLOCK_NAME)."</legend>\n";
     echo "  <p><a href=\"{$CFG->wwwroot}/course/format/ilms/configure_metadata_presets.php\">".get_string('edit_presets', $BLOCK_NAME)."</a></p>\n";
     echo "  <p class=\"helpdescription\"><img src=\"{$CFG->wwwroot}/pix/help.gif\" class=\"icon\" alt=\"description\" /> ".get_string("config_description_defaults", $BLOCK_NAME)."</p>\n";
     echo "  </fieldset>\n";
     echo "<p><input type=\"submit\" value=\"".get_string('config_submit', $BLOCK_NAME)."\"/></p>\n";
     echo "</form>";

echo $OUTPUT->footer();
