<?php 

/* Extension library for standard database access library (dmllib.php).
 * Fixes bug MDL-10787.
 * 
 * (c) 2007 Gert Sauerstein
 *
 * Edited by Andre Scherl to use in moodle 2.0 at 20.01.2011
 */

if(!function_exists("recordset_to_array2")) {

/**
 * Utility function used by <code>get_records_sql_by_field()</code>.
 * A column in the recordset containing unique values can be specified to use it as the key to the associative array. If there is no key column, NULL can be specified to generate self-increagins keys for the associative array.
 *
 * @param object $rs            An ADODB RecordSet object.
 * @param String $key_field     Name of a field in the result set that sould be use for the array key. If null (the default) is specified, a generated self-incrementing key is used rather than a field value.
 * @return mixed mixed an array of objects, or false if an error occured or the RecordSet was empty.
 */

/* old function by Gert Sauerstein (won't work with moodle 2.0)
function recordset_to_array2($rs, $key_field = null) {
    global $CFG;
    if ($rs && $rs->RecordCount() > 0) {
        if ($records = $rs->GetRows()) {
            foreach ($records as $record) {
                if ($CFG->dbfamily == 'oracle') {
                    array_walk($record, 'onespace2empty'); // dirty hack for Oracle, @see recordset_to_array()
                }
                if($key_field) {
                	$key = $record[$key_field];
                    $objects[$key] = (object) $record; /// To object
                } else {
                    $objects[] = (object) $record;
                }
            }
            return $objects;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
*/

function recordset_to_array2($rs, $key_field=null) {
    global $CFG;
    if ($rs && count($rs)>0) {
    	$objects = array();
        foreach ($rs as $record) {
            if ($CFG->dbfamily == 'oracle') {
                array_walk($record, 'onespace2empty'); // dirty hack for Oracle, @see recordset_to_array()
            }
            if($key_field) {
            	echo "key_field: $key_field \n";
            	$key = $record->$key_field;
                $objects[$key] = (object)$record;
            } else {
                $objects[] = (object)$record;
            }
        }
        $rs->close();
        return $objects;
    } else {
    	$rs->close();
        return false;
    }
}

}

if(!function_exists("get_records_sql_by_field")) {

/**
 * Get a number of records as an array of objects.
 *
 * @param string    $sql        the SQL select query to execute
 * @param array		$params 	(AS) added params array to work with moodle standards
 * @param String    $key_field 	Name of a field in the result set that should be used for the (array) keys. If null (the default) is specified, a generated self-incrementing key is used rather than a field value.
 * @param int       $limitfrom 	return a subset of records, starting at this point (optional, required if $limitnum is set).
 * @param int       $limitnum 	return a subset comprising this many records (optional, required if $limitfrom is set).
 * @return mixed 	an array of objects, or false if no records were found or an error occured.
 */
function get_records_sql_by_field($sql, $params = null, $key_field = null, $limitfrom=0, $limitnum=0) {
	global $DB;
    $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
    return recordset_to_array2($rs, $key_field);
}

}

?>