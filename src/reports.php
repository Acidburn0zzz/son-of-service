<?php

/*
 * Son of Service
 * Copyright (C) 2003-2004 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: reports.php,v 1.11 2004/08/28 15:04:58 andrewziem Exp $
 *
 */

// todo: build better report framework
// todo: make each report like a plugin

session_start();

define('SOS_PATH', '../');

require_once(SOS_PATH.'include/global.php');
require_once(SOS_PATH.'functions/html.php');
require_once(SOS_PATH.'functions/forminput.php');
require_once(SOS_PATH.'functions/textwriter.php');

is_logged_in();

if (array_key_exists('download', $_REQUEST))
{
    ob_start();
}
else
{
    make_html_begin(_("Reports"), array());

    make_nav_begin();
}    

$db = connect_db();

if (!$db)
{
    die_message(MSG_SYSTEM_ERROR, _("Error establishing database connection."), __FILE__, __LINE__);    
}



if (array_key_exists('report_hours', $_REQUEST))
    report_hours();
else
if (array_key_exists('report_active_volunteers', $_REQUEST))
    report_active_volunteers();
else
if (array_key_exists('report_volunteers_by_skill', $_REQUEST))
    report_volunteers_by_skill();
else
reports_menu();


$steps = array('Day', 'Week', 'Month', 'Year');


function report_display($title, $result, $type)
// type = 'html', 'csv'
// todo: add XML
{
    global $db;


    $nfields = $result->FieldCount();
    $fields = array();
    
    for ($n = 0; $n < $nfields; $n++)
    {
	$fld = $result->FetchField($n);
	$fields[]  .= $fld->name;
    }
    
    if ('html' == $type)
    {
	echo ("<TABLE border=\"1\">\n");
	echo ("<CAPTION>$title</CAPTION>\n");
    
	echo ("<TR>\n");
	foreach ($fields as $field)
	    echo ("<TH>$field</TH>\n");
	echo ("</TR>\n");
    }
    elseif ('csv'  == $type)
    {
	header("Content-disposition: attachment; filename=\"$title.csv\"");
	header("Pragma: no-cache");
	header("Content-type: text/csv");
	
	$writer = new textDbWriter('csv');
	$writer->setFieldNames($fields);
	//$writer->open();
    }
    
    while (!$result->EOF)
    {
	$row = $result->fields;
	if ('html' == $type)
	{
	    echo ("<TR>\n");
	    foreach ($fields as $field)
	    {
		echo ("<TD>");
		if ('volunteer_id' == $field)
		{
		    echo ("<a href=\"../volunteer/?vid=" . $row[$field] . "\">");
		}
		if (0 == strlen(trim($row[$field])))
		{
		    $row[$field] = '&nbsp';
		}
		echo ($row[$field]);
		if ('volunteer_id' == $field)
		{
		    echo ("</a>");
		}
		
		echo ("</TD>\n");
	    }
	    echo ("</TR>\n");
	} elseif ('csv' == $type)
	{
	    $writer->addRow($row);
	}
	$result->MoveNext();
    }

    if ($type == 'html')
    {
	echo ("</TABLE>\n");
	$url = make_url($_REQUEST, array());
	// todo: request gives too much
	
	echo ("<P><A href=\"$url&download=1\">Download CSV</A>\n");
    }
} /* report_display() */

function report_hours()
{
    global $db;
    global $steps;
    

    // validate
    
    $errors_found = 0;
    
    if (!in_array($_REQUEST['step'], array('Day', 'Week', 'Month', 'Year')))
    {
	process_user_error("Please select a step for this report.");
	//print_r($_POST);
	$errors_found++;
    }
    
    if ($errors_found)
    {
	reports_menu();
	return;
    }
    
    // query
    
    switch ($_REQUEST['step'])
    {
	case 'Day':
	    $dateselect = 'date';
	    $dategroup = 'group by date';
	    break;
         case 'Week';
	    $dateselect = 'year(date) as Year, week(date) as Week';
	    $dategroup = 'group by year(date), week(date)';
	    break;
         case 'Month';
	    $dateselect = 'year(date) as Year, month(date) as Month';
	    $dategroup = 'group by year(date), month(date)';
	    break;
         case 'Year';
	    $dateselect = 'year(date) as Year';
	    $dategroup = 'group by year(date)';
	    break;
	    
	    
	 default:
	    process_system_error("Unexpected step ".$_REQUEST['step']);
	    break;
	    
    }
    
    $sql = "SELECT $dateselect, sum(Hours) as Hours FROM work ";
    
    $category_id = intval($_REQUEST['category_id']);
    if ($category_id > 0)
    {
	$sql .= "WHERE category_id = $category_id";
    }
    
    $sql .= " $dategroup ";
    
    $result = $db->Execute($sql);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    } else if (0 == $result->RecordCount())
    {
	process_user_notice(("No data available for given critiera."));
    }
    else
    {
	// display

	if (array_key_exists('download', $_REQUEST))
	{
	    report_display('AggregateHours', $result, 'csv');
	}
	else
	{
	    report_display(_("Aggregate hours"), $result, 'html');
	}
    }
}


function report_active_volunteers()
{
    global $db;
    global $steps;
    
    
    // validate
    
    $errors_found = 0;
    
    $d1 = sanitize_date($_REQUEST['beginning_date']);
    $d2 = sanitize_date($_REQUEST['ending_date']);    
    
    if (!$d1 or !$d2)
    {
	process_user_error("Please enter a valid date in the format YYYY-MM-DD or MM/DD/YYYY.");
    }
        
    if ($errors_found)
    {
	reports_menu();
	return;
    }
    
    // query
    
    //$sql = "SELECT volunteer_id, last, hours_life FROM volunteers ORDER BY hours_life DESC";
    $d1 = str_replace('-','', $d1);
    $d2 = str_replace('-','', $d2);    
    $sql = "SELECT volunteers.volunteer_id, concat_ws(' ',volunteers.first, volunteers.middle, volunteers.last, volunteers.organization) as Volunteer_Name, sum(hours) as Total_Hours FROM work LEFT JOIN volunteers ON work.volunteer_id = volunteers.volunteer_id WHERE work.date between $d1 and $d2 GROUP BY volunteer_id ORDER BY Total_Hours DESC";
    $result = $db->SelectLimit($sql, 30);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    } elseif (0 == $result->RecordCount())
    {
	process_user_notice("No data available for given critiera.");
    }
    else
    {
	// display
	if (array_key_exists('download',$_REQUEST))
	{
    	    report_display("Most active volunteers between $d1 and $d2", $result, 'csv');
	}
	else
	{
    	    report_display("Most active volunteers between " . htmlentities($d1) . " and " . htmlentities($d2), $result, 'html');	    
	}
    }
    
} /* report_active_volunteers() */

function report_volunteers_by_skill()
// this is fairly similar to just searching for volunteers by a skill
{
    global $db;
    
    
    // validate
    
    $errors_found = 0;
    
    $string_id = intval($_REQUEST['string_id']);
    if ('any' == $_REQUEST['string_id'])
    {
	$string_id = 'any';
    }
    else
    {
        if (!$string_id > 0)
        {
    	    process_user_error(_("Please choose a skill."));
	    $errors_found++;
        }
    }
        
    if ($errors_found)
    {
	reports_menu();
	return;
    }
    
    // query
    
    if (is_integer($string_id))
    {
	// one skill
        $sql = "SELECT volunteers.volunteer_id, concat_ws(' ',volunteers.first, volunteers.middle, volunteers.last, volunteers.organization) as Volunteer_Name, volunteers.email_address as email_address, strings.s as skill " .
	"FROM volunteers " .
	"LEFT JOIN volunteer_skills ON volunteers.volunteer_id = volunteer_skills.volunteer_id " .
	"LEFT JOIN strings ON strings.string_id = volunteer_skills.string_id " .
	"WHERE volunteer_skills.string_id = $string_id " .
	"ORDER BY volunteers.volunteer_id";
    }
    else
    {
	// all skills
	// todo: how to get multiple skill names in one SQL records?
	process_system_error("Not yet implemented");
    }
    $result = $db->SelectLimit($sql, 30);
    
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    } elseif (0 == $result->RecordCount())
    {
	process_user_notice("No data available for given critiera.");
    }
    else
    {
	// display
	if (array_key_exists('download',$_REQUEST))
	{
    	    report_display("Volunteers by skill", $result, 'csv');
	}
	else
	{
    	    report_display("Volunteers by skill", $result, 'html');	    
	}
    }
    
} /* report_volunteers_by_skill() */


function reports_menu()
{
    global $db;
    

    echo ("<H2>"._("Reports")."</H2>\n");

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Aggregate hours</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    echo ("<SELECT name=\"step\">\n");
    echo ("<OPTION>--Step</OPTION>\n");
    echo ("<OPTION>"._("Day")."</OPTION>\n");    
    echo ("<OPTION>"._("Week")."</OPTION>\n");    
    echo ("<OPTION>"._("Month")."</OPTION>\n");
    echo ("<OPTION>"._("Year")."</OPTION>\n");    
    echo ("</SELECT>\n");
    $sql = "SELECT * FROM strings WHERE type = 'work'";
    $result = $db->Execute($sql);
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    {
	echo ("<SELECT name=\"category_id\">\n");
	echo ("<OPTION>--Project</OPTION>\n");
	echo ("<OPTION value=\"any\">"._("Any")."</OPTION>\n");	
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    echo ("<OPTION value=\"".$row['string_id']."\">".$row['s']."</OPTION>\n");
	    $result->MoveNext();
	}
	echo ("</SELECT>\n");
    }
    echo ("<BR><INPUT type=\"submit\" name=\"report_hours\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");

    echo ("<FIELDSET>\n");
    echo ("<LEGEND>Most active volunteers</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    echo ("Beginning <INPUT type=\"text\" name=\"beginning_date\" value=\"2000-01-01\" size=\"10\">\n");
    echo ("Ending <INPUT type=\"text\" name=\"ending_date\" value=\"".date('Y-m-d')."\" size=\"10\">\n");
    echo ("<BR><INPUT type=\"submit\" name=\"report_active_volunteers\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET>\n");


    echo ("<FIELDSET>\n");
    echo ("<LEGEND>List of volunteers by skill</LEGEND>\n");
    echo ("<FORM method=\"get\" action=\"reports.php\">\n");
    $sql = "SELECT * FROM strings WHERE type = 'skill'";
    $result = $db->Execute($sql);
    if (!$result)
    {
	die_message(MSG_SYSTEM_ERROR, _("Error querying database."), __FILE__, __LINE__, $sql);
    }
    else
    {
	echo ("<SELECT name=\"string_id\">\n");
	echo ("<OPTION>--Skill</OPTION>\n");
	echo ("<OPTION value=\"any\">"._("Any")."</OPTION>\n");	
	while (!$result->EOF)
	{
	    $row = $result->fields;
	    echo ("<OPTION value=\"".$row['string_id']."\">".$row['s']."</OPTION>\n");
	    $result->MoveNext();
	}
	echo ("</SELECT>\n");
    }
    echo ("<BR><INPUT type=\"submit\" name=\"report_volunteers_by_skill\" value=\""._("Make report")."\">\n");
    echo ("</FORM>\n");
    echo ("</FIELDSET\n");
     
   

    if (!array_key_exists('download', $_REQUEST))
    {
	make_html_end();
    }
}
?>

