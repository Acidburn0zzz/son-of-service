<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: summary.php,v 1.7 2003/12/21 23:39:06 andrewziem Exp $
 *
 */

require_once(SOS_PATH .'functions/table.php');

if (preg_match('/summary.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}


function volunteer_summary()
{
    global $volunteer, $db, $vid;

    // show contact card

    $contact_card = '';
    if ($volunteer['first'] or $volunteer['last'])
    {
	$contact_card .= $volunteer['prefix'].' '.$volunteer['first'].' '.$volunteer['middle'].' '.$volunteer['last'].' '.$volunteer['suffix'].' (#'.$volunteer['volunteer_id'].")\n";
    }
    if (!empty($volunteer['organization']))
    {
	$contact_card .= $volunteer['organization']."\n";
    }
    $address = $volunteer['street']."\n".$volunteer['city'].', '.$volunteer['state'].' '.$volunteer['postal_code'].' '.$volunteer['country']." \n";
    if (strlen(trim($address))>2)
    {
	$contact_card .= $address;
    }
    // get phone numbers
    $sql = "SELECT number, memo FROM phone_numbers WHERE volunteer_id = $vid";
    $phone_result = $db->Execute($sql);
    if (!$phone_result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying data from database."), __FILE__, __LINE__, $sql);
    }
    else
    while (!$phone_result->EOF)
    {
	$phone = $phone_result->fields;
	$contact_card .= _("Phone:").' '.$phone['number'].' '.$phone['memo'] ."\n";
	$phone_result->MoveNext();
    }
/*
    $tab = new DataTableDisplay();
    $tab->begin();
    $tab->addRow(array(nl2br($contact_card)));
    $tab->end();
*/
    echo ("<P>".nl2br($contact_card)."</P>\n");

    // show notes

    // code to consolidate notes and work history:
    // select concat(year(date),'-',month(date),'-',dayofmonth(date)) as date, work_id, 'workhistory', concat(hours,' ',if(isnull(memo),'',memo)) from work union select year(dt), month(dt), dayofmonth(dt), note_id, 'notes', message from notes;

    include_once('notes.php');

    volunteer_view_notes(TRUE);

    // show work history

    include_once('workhistory.php');

    volunteer_view_work_history(TRUE);

    // show skills

    include_once('skills.php');

    volunteer_view_skills(TRUE);

    // show availability

    include_once('availability.php');

    volunteer_view_availability(TRUE);

    // show custom fields

    // todo

    // show relationships

    include_once('relationships.php');

    relationships_view(TRUE);

}

?>
