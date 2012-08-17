<?php
/*
 * Copyright (C) 2007, Gert Sauerstein
 * Edited by Andre Scherl, 17.09.2012
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */
// Capability definitions for the case_repository block (and iLMS course format).
//
// The capabilities are loaded into the database table when the block is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to modules and blocks is as follows:
//   [mod/block]/<component_name>:<capabilityname>
//
// The variable name for the capability definitions array follows the format
//   $<componenttype>_<component_name>_capabilities

/** Definiert die Berechtigungen für die einzelnen Menüpunkte des Blocks "Lerneradaption" und für den Zugriff auf die Fallbasis */
$capabilities = array(

    /** Das Recht, die Fallbasis einsehen oder abfragen zu können
     *  Berechtigte: Administratoren
     */
    'block/case_repository:view_repository' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        )
    ),

    /** Das Recht, die Webseite "Semantische Beziehungen" mit der Übersichtstabelle der semantischen Beziehungen zwischen den Lernaktivitäten eines Kurses abrufen zu dürfen
     *  Berechtigte: Administratoren, Kursverwalter, Tutoren
     */
    'block/case_repository:view_dependencies' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),
    
    /** Die Fähigkeit, die Benutzer ausgewählte Lernaktivitäten durch Tracking als neue Fälle in der Fallbasis abzulegen
     *  Berechtigte: Nur die angemeldeten Lerner/Studenten 
     */
    'block/case_repository:store' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_ALLOW,
            // (GS) Administratoren, Kursverwalter und Tutoren dürfen dieses Recht nicht besitzen, da für sie keine Fälle erfasst werden sollen!
            // WICHTIG: Beim Aufruf von has_capability() darf das Flag $doanything NICHT gesetzt werden, sonst erhalten Administratoren diese Berechtigung immer automatisch !
            'teacher' => CAP_PROHIBIT, 
            'editingteacher' => CAP_PROHIBIT,
            'admin' => CAP_PROHIBIT
        )
    ),    

    /** Das Recht, die Metadaten aller Lernaktivitäten durch ihre Vorgabewerte zu ersetzen bzw. mit den Vorgabewerten zu initialisieren
     *  Berechtigte: Administratoren, Kursverwalter, Tutoren mit Editierberechtigung
     */
    'block/case_repository:apply_metadata_presets' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),    

    /** Das Recht, die Standardwerte für Metadaten ändern zu können
     *  Berechtigte: Administratoren 
     */
    'block/case_repository:configure_metadata_presets' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'guest' => CAP_PREVENT,
            'student' => CAP_PREVENT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        )
    ),    

);

?>