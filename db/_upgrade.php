<?php
/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
//$Id: upgrade.php,v 1.0 2007/05/18 16:34:00 gert Exp $

// This file keeps track of upgrades to 
// the case_repository block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_case_repository_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;
    
    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // Version 2007110400 (Build 0.5.10): Spalte "appliance_user" zu den Tabellen "ilms_cases" und "ilms_new_cases" hinzugefügt
    if ($oldversion < 2011012500) {
        $table = new xmldb_table('ilms_new_cases');
        $field = new xmldb_field('appliance_user');
        $field->set_attributes(XMLDB_TYPE_NUMBER, '12, 8', null, XMLDB_NOTNULL, false, 1.0, 'timemodified');
        $dbman->add_field($table, $field);
        
        $table = new xmldb_table('ilms_cases');
        $field = new xmldb_field('appliance_user');
        $field->set_attributes(XMLDB_TYPE_NUMBER, '12, 8', null, XMLDB_NOTNULL, false, 1.0, 'appliance');
        $dbman->add_field($table, $field);
        
        upgrade_mod_savepoint(true, 20011012500, 'block_case_repository');
    }
}

?>
