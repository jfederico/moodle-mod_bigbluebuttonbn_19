<?php
/**
 * Join a room.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/calendar/lib.php');


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $bigbluebuttonbn An object from the form in mod_form.php
 * @return int The id of the newly inserted bigbluebuttonbn record
 */
function bigbluebuttonbn_add_instance($bigbluebuttonbn) {

    $bigbluebuttonbn->timecreated = time();

	if (record_exists( 'bigbluebuttonbn', 'meetingID', $bigbluebuttonbn->name)) {
		error("A meeting with that name already exists.");
		return false;
	}

	$bigbluebuttonbn->moderatorpass = bigbluebuttonbn_rand_string( 16 );
	$bigbluebuttonbn->viewerpass = bigbluebuttonbn_rand_string( 16 );
	$bigbluebuttonbn->meetingid = bigbluebuttonbn_rand_string( 16 );
	
	if (! isset($bigbluebuttonbn->newwindow))   $bigbluebuttonbn->newwindow = 0;
	if (! isset($bigbluebuttonbn->wait))        $bigbluebuttonbn->wait = 0;
	if (! isset($bigbluebuttonbn->record))      $bigbluebuttonbn->record = 0;
	
	$returnid = insert_record('bigbluebuttonbn', $bigbluebuttonbn);
	
	if (isset($bigbluebuttonbn->timeavailable) && $bigbluebuttonbn->timeavailable ){
	    $event = NULL;
	    $event->name        = $bigbluebuttonbn->name;
	    $event->courseid    = $bigbluebuttonbn->course;
	    $event->groupid     = 0;
	    $event->userid      = 0;
	    $event->modulename  = 'bigbluebuttonbn';
	    $event->instance    = $returnid;
	    $event->timestart   = $bigbluebuttonbn->timeavailable;
	
	    if ( $bigbluebuttonbn->timedue ){
	        $event->timeduration = $bigbluebuttonbn->timedue - $bigbluebuttonbn->timeavailable;
	    } else {
	        $event->timeduration = 0;
	    }
	
	    calendar_event::create($event);
	}
	
	return $returnid;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $bigbluebuttonbn An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function bigbluebuttonbn_update_instance($bigbluebuttonbn) {

    $bigbluebuttonbn->timemodified = time();
    $bigbluebuttonbn->id = $bigbluebuttonbn->instance;

    if (! isset($bigbluebuttonbn->newwindow))   $bigbluebuttonbn->newwindow = 0;
    if (! isset($bigbluebuttonbn->wait))        $bigbluebuttonbn->wait = 0;
    if (! isset($bigbluebuttonbn->record))      $bigbluebuttonbn->record = 0;
    
    update_record('bigbluebuttonbn', $bigbluebuttonbn);
    
    if (isset($bigbluebuttonbn->timeavailable) && $bigbluebuttonbn->timeavailable ){
        $event = new stdClass();
        $event->name        = $bigbluebuttonbn->name;
        $event->courseid    = $bigbluebuttonbn->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'bigbluebuttonbn';
        $event->instance    = $bigbluebuttonbn->id;
        $event->timestart   = $bigbluebuttonbn->timeavailable;

        if ( $bigbluebuttonbn->timedue ){
            $event->timeduration = $bigbluebuttonbn->timedue - $bigbluebuttonbn->timeavailable;
            
        } else {
            $event->timeduration = 0;
            
        }

        if ($event->id = get_field('event', 'id', array('modulename'=>'bigbluebuttonbn', 'instance'=>$bigbluebuttonbn->id))) {
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
            
        } else {
            calendar_event::create($event);
            
        }
        
    } else {
        delete_records('event', array('modulename'=>'bigbluebuttonbn', 'instance'=>$bigbluebuttonbn->id));
        
    }
    
    return true;
   
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function bigbluebuttonbn_delete_instance($id) {
    global $CFG;

    if (! $bigbluebuttonbn = get_record('bigbluebuttonbn', 'id', $id)) {
        return false;
    }

    $result = true;

    //
    // End the session associated with this instance (if it's running)
    //
    $meetingID = $bigbluebuttonbn->meetingid.'-'.$bigbluebuttonbn->course.'-'.$bigbluebuttonbn->id;
    
    $modPW = $bigbluebuttonbn->moderatorpass;
    $url = trim(trim($CFG->BigBlueButtonBNServerURL),'/').'/';
    $salt = trim($CFG->BigBlueButtonBNSecuritySalt);

    //if( bigbluebuttonbn_isMeetingRunning($meetingID, $url, $salt) )
    //    $getArray = bigbluebuttonbn_doEndMeeting( $meetingID, $modPW, $url, $salt );
    	
    if (! delete_records('bigbluebuttonbn', 'id', $bigbluebuttonbn->id)) {
        $result = false;
    }
    
    if (! delete_records('event', array('modulename'=>'bigbluebuttonbn', 'instance'=>$bigbluebuttonbn->id))) {
        $result = false;
    }
    
    return $result;
}


/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function bigbluebuttonbn_user_outline($course, $user, $mod, $bigbluebuttonbn) {
    return true;
}


/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function bigbluebuttonbn_user_complete($course, $user, $mod, $bigbluebuttonbn) {
    return true;
}


/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in bigbluebuttonbn activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function bigbluebuttonbn_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}


/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function bigbluebuttonbn_cron () {
    return true;
}


/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of bigbluebuttonbn. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $bigbluebuttonbnid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function bigbluebuttonbn_get_participants($bigbluebuttonbnid) {
    return false;
}

/**
 * Returns all other caps used in module
 * @return array
 */
function bigbluebuttonbn_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * Checks if scale is being used by any instance of bigbluebuttonbn.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any bigbluebuttonbn
 */
function bigbluebuttonbn_scale_used_anywhere($scaleid) {
    if ($scaleid and record_exists('bigbluebuttonbn', 'grade', -$scaleid)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function bigbluebuttonbn_install() {
    return true;
}


/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function bigbluebuttonbn_uninstall() {
    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return object|null
 */
function bigbluebuttonbn_get_coursemodule_info($coursemodule) {
    global $CFG;
    
    if (! $bigbluebuttonbn = get_record('bigbluebuttonbn', 'id', $coursemodule->instance) ) {
        $info = null;
    } else {
        $info = new object();
        $info->name  = $bigbluebuttonbn->name;
        
        if ( $bigbluebuttonbn->newwindow == 1 ){
            $info->extra = "onclick=\"window.open('".$CFG->wwwroot."/mod/bigbluebuttonbn/view.php?id=".$coursemodule->id."&amp;redirect=1'); return false;\"";
        
        }
        
    }
    
    return $info;

}

?>
