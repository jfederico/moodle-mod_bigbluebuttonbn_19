<?php
/**
 * View all BigBlueButtonBN instances in this course.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = required_param('id', PARAM_INT);      // Course Module ID, or
$a  = optional_param('a', 0, PARAM_INT);    // bigbluebuttonbn instance ID
$g  = optional_param('g', 0, PARAM_INT);    // group instance ID

if (! $course = get_record('course', 'id', $id)) error('Course ID is incorrect');
require_course_login($course);

$coursecontext = get_context_instance(CONTEXT_COURSE, $id);
$moderator = has_capability('mod/bigbluebuttonbn:moderate', $coursecontext);

add_to_log($course->id, 'bigbluebuttonbn', 'view all', "index.php?id=$course->id", '');

/// Get all required stringsbigbluebuttonbn
$strbigbluebuttonbns = get_string('modulenameplural', 'bigbluebuttonbn');
$strbigbluebuttonbn  = get_string('modulename', 'bigbluebuttonbn');

/// Print the header
$navlinks = array();
$navlinks[] = array('name' => $strbigbluebuttonbns, 'link' => '', 'type' => 'activity');
$navigation = build_navigation($navlinks);

print_header_simple($strbigbluebuttonbns, '', $navigation, '', '', true, '', navmenu($course));

/// Get all the appropriate data
if (! $bigbluebuttonbns = get_all_instances_in_course('bigbluebuttonbn', $course)) {
    notice('There are no instances of bigbluebuttonbn', "../../course/view.php?id=$course->id");
}

/// Print the list of instances (your module will probably extend this)
$timenow            = time();
$timenow            = time();
$strweek            = get_string('week');
$strtopic           = get_string('topic');
$heading_name       = get_string('index_heading_name', 'bigbluebuttonbn' );
$heading_group      = get_string('index_heading_group', 'bigbluebuttonbn' );
$heading_users      = get_string('index_heading_users', 'bigbluebuttonbn');
$heading_viewer     = get_string('index_heading_viewer', 'bigbluebuttonbn');
$heading_moderator  = get_string('index_heading_moderator', 'bigbluebuttonbn' );
$heading_actions    = get_string('index_heading_actions', 'bigbluebuttonbn' );
$heading_recording  = get_string('index_heading_recording', 'bigbluebuttonbn' );

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $heading_name, $heading_group, $heading_users, $heading_viewer, $heading_moderator, $heading_recording, $heading_actions );
    $table->align = array ('center', 'left', 'center', 'center', 'center',  'center', 'center' );
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

$url = trim(trim($CFG->bigbluebuttonbnServerURL),'/').'/';
$salt = trim($CFG->bigbluebuttonbnSecuritySalt);
$logoutURL = $CFG->wwwroot;

if( isset($_POST['submit']) && $_POST['submit'] == 'end' && $moderator) { 
	//
	// A request to end the meeting
	//
	if (! $bigbluebuttonbn = get_record('bigbluebuttonbn', 'id', $a)) {
        	error("BigBlueButton ID $a is incorrect");
	}
	print get_string('index_ending', 'bigbluebuttonbn');

	$meetingID = $bigbluebuttonbn->meetingid.'-'.$course->id.'-'.$bigbluebuttonbn->id;
	
	$modPW = $bigbluebuttonbn->moderatorpass;
	if( $g != '0'  ) {
	    $getArray = bigbluebuttonbn_wrap_simplexml_load_file( bigbluebuttonbn_getEndMeetingURL( $meetingID.'['.$g.']', $modPW, $url, $salt ) );
	} else {
	    $getArray = bigbluebuttonbn_wrap_simplexml_load_file(bigbluebuttonbn_getEndMeetingURL( $meetingID, $modPW, $url, $salt ));
	}
	redirect('index.php?id='.$id);
	
}

foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
    $cm = get_coursemodule_from_id('bigbluebuttonbn', $bigbluebuttonbn->coursemodule);

    if ( groups_get_activity_groupmode($cm) > 0 ){
        $groups = groups_get_activity_allowed_groups($cm);
        if( isset($groups)) {
            foreach( $groups as $group){
                $table->data[] = displayBigBlueButtonRooms($url, $salt, $moderator, $course, $bigbluebuttonbn, $group);
            }
        }
    } else {
        $table->data[] = displayBigBlueButtonRooms($url, $salt, $moderator, $course, $bigbluebuttonbn);
    }
    
}

print_heading($strbigbluebuttonbns);
print_table($table);
print_footer($course);


function displayBigBlueButtonRooms($url, $salt, $moderator, $course, $bigbluebuttonbn, $groupObj = null ){
    $joinURL = null;
    $user = null;
    $result = null;
    $group = "-";
    $users = "-";
    $running = "-";
    $actions = "-";
    $viewerList = "-";
    $moderatorList = "-";
    $recording = "-";


    if ( !$bigbluebuttonbn->visible ) {
        // Nothing to do
    } else {
        $modPW = $bigbluebuttonbn->moderatorpass;
        $attPW = $bigbluebuttonbn->viewerpass;

        $meetingID = $bigbluebuttonbn->meetingid.'-'.$course->id.'-'.$bigbluebuttonbn->id;
        //
        // Output Users in the meeting
        //
        if( $groupObj == null ){
            $getArray = bigbluebuttonbn_getMeetingInfoArray( $meetingID, $modPW, $url, $salt );
            if ( $bigbluebuttonbn->openoutside == 1 )
                $joinURL = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.'" target="_blank">'.format_string($bigbluebuttonbn->name).'</a>';
            else
                $joinURL = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.'">'.format_string($bigbluebuttonbn->name).'</a>';
        } else {
            $getArray = bigbluebuttonbn_getMeetingInfoArray( $meetingID.'['.$groupObj->id.']', $modPW, $url, $salt );
            if ( $bigbluebuttonbn->openoutside == 1 )
                $joinURL = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.'&group='.$groupObj->id.'" target="_blank">'.format_string($bigbluebuttonbn->name).'</a>';
            else
                $joinURL = '<a href="view.php?id='.$bigbluebuttonbn->coursemodule.'&group='.$groupObj->id.'">'.format_string($bigbluebuttonbn->name).'</a>';
            $group = $groupObj->name;
        }

        if (!$getArray) {
            //
            // The server was unreachable
            //
            print_error( get_string( 'index_error_unable_display', 'bigbluebuttonbn' ));
            return;
        }

        if (isset($getArray['messageKey'])) {
            //
            // There was an error returned
            //
            if ($getArray['messageKey'] == "checksumError") {
                print_error( get_string( 'index_error_checksum', 'bigbluebuttonbn' ));
                return;
            }

            if ($getArray['messageKey'] == "notFound" || $getArray['messageKey'] == "invalidMeetingId") {
                //
                // The meeting does not exist yet on the BigBlueButton server.  This is OK.
                //
            } else {
                //
                // There was an error
                //
                $users = $getArray['messageKey'].": ".$getArray['message'];
            }
        } else {
            //
            // The meeting info was returned
            //
            if ($getArray['running'] == 'true') {
                if ( $moderator ) {
                    if( $groupObj == null ) {
                        $actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="'.$course->id.'"><INPUT type="hidden" name="a" value="'.$bigbluebuttonbn->id.'"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\''. get_string('index_confirm_end', 'bigbluebuttonbn' ).'\')"></form>';
                    } else {
                        $actions = '<form name="form1" method="post" action=""><INPUT type="hidden" name="id" value="'.$course->id.'"><INPUT type="hidden" name="a" value="'.$bigbluebuttonbn->id.'"><INPUT type="hidden" name="g" value="'.$groupObj->id.'"><INPUT type="submit" name="submit" value="end" onclick="return confirm(\''. get_string('index_confirm_end', 'bigbluebuttonbn' ).'\')"></form>';
                    }
                }
                if ( isset($getArray['recording']) && $getArray['recording'] == 'true' ){ // if it has been set when meeting created, set the variable on/off
                    $recording = get_string('index_enabled', 'bigbluebuttonbn' );
                }
                 
                $xml = $getArray['attendees'];
                if (count( $xml ) && count( $xml->attendee ) ) {
                    $users = count( $xml->attendee );
                    $viewer_count = 0;
                    $moderator_count = 0;
                    foreach ( $xml->attendee as $attendee ) {
                        if ($attendee->role == "MODERATOR" ) {
                            if ( $viewer_count++ > 0 ) {
                                $moderatorList .= ", ";
                            } else {
                                $moderatorList = "";
                            }
                            $moderatorList .= $attendee->fullName;
                        } else {
                            if ( $moderator_count++ > 0 ) {
                                $viewerList .= ", ";
                            } else {
                                $viewerList = "";
                            }
                            $viewerList .= $attendee->fullName;
                        }
                    }
                }
            }
        }

        return array ($bigbluebuttonbn->section, $joinURL, $group, $users, $viewerList, $moderatorList, $recording, $actions );

    }

}

?>
