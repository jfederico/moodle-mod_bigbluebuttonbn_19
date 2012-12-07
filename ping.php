<?php
/**
 * Ping the BigBlueButton server to see if the meeting is running
 *
 * Authors:
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)    
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2012 Blindside Networks Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$callback = optional_param('callback', "", PARAM_TEXT);
$meetingID = optional_param('meetingid', 0, PARAM_TEXT);
    
if (!$meetingID) {
    $error = 'You must specify a meetingid';
}

//if (!$callback) {
//    $error = 'This call must include a javascript callback';
//}

//header('Content-Type: application/json; charset=utf-8');
header('Content-Type: text/plain; charset=utf-8');
if ( !isset($error) ) {

    //if (!isloggedin() && $PAGE->course->id == SITEID) {
    //    $userid = guest_user()->id;
    //} else {
    //    $userid = $USER->id;
    //}
    //$hascourseaccess = ($PAGE->course->id == SITEID) || can_access_course($PAGE->course, $userid);

    $hascourseaccess = true;
    
    if( !$hascourseaccess ){
        header("HTTP/1.0 401 Unauthorized");
        //http_response_code(401);
    } else {
        $salt = trim($CFG->bigbluebuttonbnSecuritySalt);
        $url = trim(trim($CFG->bigbluebuttonbnServerURL),'/').'/';

        try{
            $ismeetingrunning = (bigbluebuttonbn_isMeetingRunning( $meetingID, $url, $salt )? 'true': 'false');
            //echo $callback.'({ "status": "'.$ismeetingrunning.'" });';
            echo '{ status : '.$ismeetingrunning.' }';
            //echo 'helloworld';
        }catch(Exception $e){
            header("HTTP/1.0 502 Bad Gateway. ".$e->getMessage());
        }
        
    }

} else {
    header("HTTP/1.0 400 Bad Request. ".$error);
    //http_response_code(400);
}