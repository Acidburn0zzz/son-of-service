<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 * Mangages a user's reminders (special kind of notes).
 *
 * $Id: reminders.php,v 1.2 2003/11/28 16:25:48 andrewziem Exp $
 *
 */
 
// todo: reminders not attached to volunteers

session_start();
ob_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');
require_once(SOS_PATH . 'functions/html.php');


$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection."), array ('debug' => $db->get_error()));    
    die();	
}

is_logged_in();


function show_reminders()
{
    global $db;
    
    // todo: method for getting volunteer name is slow because
    // volunteer_get may be called many times
    
    $sql = "SELECT note_id, dt, reminder_date, volunteer_id, message ".
	"FROM notes ".
	"WHERE uid_assigned = ".intval($_SESSION['user_id'])." AND acknowledged = 1 ".
	"ORDER BY reminder_date DESC";
    
    $result = $db->query($sql);
    
    if (!$result)
    {
	process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
    }
    elseif (0 == $db->num_rows($result))
    {
	process_user_notice(_("No reminders."));
    }
    else
    {
	display_messages();
    
	require_once(SOS_PATH . 'functions/formmaker.php');    
	require_once(SOS_PATH . 'functions/table.php');
	
	// todo: pagination
	
	$form = new formMaker();
	$form->open(_("Reminders"), 'post', 'reminders.php', FS_PLAIN);

	$table = new DataTableDisplay();

	// todo: display message on second row	
	$fieldnames['note_id']['checkbox'] = TRUE;
	$fieldnames['reminder_date']['label'] = _("Reminder date");		
	$fieldnames['dt']['label'] = _("Creation date");	
	$fieldnames['volunteer']['link'] = SOS_PATH . "volunteer/?vid=#volunteer_id#";
	$fieldnames['message'] = array();
	
	$table->setHeaders($fieldnames);
	$table->begin();
	while (FALSE != ($row = $db->fetch_array($result)))
	{
	    $v = volunteer_get($row['volunteer_id']);
	    $row['volunteer'] = make_volunteer_name($v);
	    $table->addRow($row);
	}
	$table->end();
	
	// todo: edit via volunteers/notes?
	
	$form->addButton('button_acknowledge_reminder', _("Acknowledge"));
	$form->close();
    }
}


if (array_key_exists('button_acknowledge_reminder', $_POST))
{
    $note_ids = array();
    
    foreach ($_POST as $k => $v)
    {
	if (preg_match('/^note_id_(\d+)/', $k, $matches))
	{
	    $note_ids[intval($matches[1])] = intval($matches[1]);
	}
    }    
    
    if (0 == count($note_ids))
    {
	save_message(_("Select one or more options."), MSG_USER_ERROR);
    }
    else
    {
	$c = 0;
	
	$sql = 'UPDATE notes SET acknowledged = 0 WHERE uid_assigned = '.intval($_SESSION['user_id']).' AND (';
	foreach ($note_ids as $nid)
	{
	    if ($c > 0)
	    {
		$sql .= ' OR ';
	    }
	    $sql .= ' note_id = '.$nid;
	    $c++;
	}
	$sql .= ')';
	
	$result = $db->query($sql);
	
	if (!$result)
	{
	    save_message(_("Error updating data in database."), MSG_SYSTEM_ERROR, array('debug' => $db->get_error()));	
	}
    }
        
    // todo: relative path violates HTTP standards?
    header("Location: reminders.php");

}
else
{	
    make_html_begin(_("Reminders"), array());

    make_nav_begin();

    show_reminders();
    
    make_html_end();
}


?>
