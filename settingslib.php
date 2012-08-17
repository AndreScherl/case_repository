<?php

/**
 * Library for customized admin config objects
 *
 * @author	Andre Scherl
 * @version	1.0 - 04.02.2011
 * @package	DASIS -> Case Repository
 *
 * Copyright (C) 2011, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with DASIS.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}


/**
 * class to parse html strings
 */
class admin_setting_myhtml extends admin_setting {

    public function __construct($name, $visiblename, $description, $html) {
        
        parent::__construct($name, $visiblename, $description, null);
        $this->html = $html;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }
    
    public function output_html() {
    	return $this->html;
    }
}