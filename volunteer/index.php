<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * View, change, and use a volunteer's record.
 *
 * $Id: index.php,v 1.14 2003/11/14 17:17:45 andrewziem Exp $
 *
 */

ob_start();
session_start();

//if (!empty($_POST))
//    header("Pragma: no-cache");

define('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'functions/html.php');
require_once (SOS_PATH . 'functions/forminput.php');
require_once (SOS_PATH . 'functions/formmaker.php');

$db = new voldbMySql();

if ($db->get_error())
{
    process_system_error(_("Unable to establish database connection."), array('debug'=>$db->get_error()));    
    die();	
}

if (array_key_exists('vid', $_REQUEST))
{
    $volunteer_name = make_volunteer_name(volunteer_get(intval($_REQUEST['vid'])));

}
else
{
    $volunteer_name = "";
}

make_html_begin(_("Volunteer account: ").$volunteer_name, array());

is_logged_in();

make_nav_begin();

  if (array_key_exists('add_skill', $_POST))
  {
    //user pushed a button
    include('skills.php');
    volunteer_skill_add();
  }
  else  if (array_key_exists('button_delete_volunteer_skill', $_POST))
  {
    //user pushed a button
    include('skills.php');
    volunteer_delete_skill();
  }
  else  if (array_key_exists('availability_add', $_POST))
  {
    //user pushed a button
     include('availability.php');
     volunteer_availability_add();
     volunteer_view_availability();
  }
  else if (array_key_exists('button_delete_availability', $_POST))
  {
    //user pushed a button
    include('availability.php');
    volunteer_delete_availability();
  }
  else
  if (array_key_exists('button_add_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_save('add');
    stats_update_volunteer($db, intval($_POST['vid']));
    volunteer_view_work_history();
    work_history_addedit('add');
  }
  else if (array_key_exists('button_update_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_save('update');
    stats_update_volunteer($db, intval($_POST['vid']));
    volunteer_view_work_history(); // show history
    work_history_addedit('add');  // show form
  }
  else
  if (array_key_exists('button_delete_work_history', $_POST))
  {
    include('workhistory.php');
    volunteer_work_history_delete();
    stats_update_volunteer($db, intval($_POST['vid']));
    volunteer_view_work_history();
    work_history_addedit('add');  // show form
  }
  else
  if (array_key_exists('button_edit_work_history', $_POST))
  {
    include('workhistory.php');
    work_history_addedit('edit');
  }  
  else
  if (array_key_exists('volunteer_delete', $_POST))
  {
    volunteer_delete();
  }
  else
  if (array_key_exists('button_add_note', $_POST))
  {
    include('notes.php');
    note_add();
  }
  else 
  {
    $found = FALSE;
    foreach ($_POST as $pk => $pv)
    {
	if (preg_match('/add_relationship/', $pk))
	{
	    $found = TRUE;
	    include('relationships.php');
	    relationship_add();
	    relationships_view();
	    relationships_add_form();

	}
	else if (preg_match('/delete_relationship_/', $pk))
	{
	    $found = TRUE;
	    include('relationships.php');
	    relationship_delete();
	    relationships_view();
	    relationships_add_form();	    
	}
    }
    if (!$found)
    {
	volunteer_view();  
    }
} 


function volunteer_delete()
{
    global $db;
    
    
    // validate form input
    
    $vid = intval($_POST['vid']);

    if (!preg_match("/^[0-9]+$/", $_POST['vid']))
    {
	process_system_error(_("Bad form input:").' vid');
	die();
    }

    if (array_key_exists('delete_confirm', $_POST) and 'on' == $_POST['delete_confirm'])
    {
      
	// delete related records

	echo ("<P>Deleting related records...</P>\n");
	echo ("<UL>\n");
        echo (" <LI>Deleting notes..\n");
        $result = $db->query("DELETE FROM notes WHERE vid=$vid");
        echo " <LI>Deleting work history..\n";
        $result = $db->query("DELETE FROM work WHERE vid=$vid");
        echo " <LI>Deleting availability..\n";
        $result = $db->query("DELETE FROM availability WHERE vid=$vid");      
        echo (" <LI>Deleting skills..\n");
        $result = $db->query("DELETE FROM volunteer_skills WHERE vid=$vid");            
	echo (" <LI>Deleting relationships..\n");
        $result = $db->query("DELETE FROM relationships WHERE volunteer1_id = $vid OR volunteer2_id = $vid");            	
	echo " </UL>\n";
      
        // delete primary record

	echo ("<P>Deleting primary record...</P>\n");

        $result = $db->query("DELETE FROM volunteers WHERE volunteer_id=$vid LIMIT 1");

        if ($result)
	{
	    echo ("<P>Volunteer permanently deleted.</P>\n");
        }
	else
        {
    	    process_system_error(_("Error deleting data from database."), array('debug' => $db->get_error()));
	}
    }
    else
    {
	echo ("<P class=\"instructionstext\">Are you sure you want to permanently delete this volunteer and all his related records (work history, notes, reminders, etc.)?  If not, simply click a menu option: General, Skills, etc.</P>\n");

	$volunteer = volunteer_get($vid);
     
	echo ("<PRE>\n");
	echo $volunteer['first']. " " . $volunteer['middle'] . " " . $volunteer['last'] . " (".$volunteer['organization'].")\n";
	echo $volunteer['street'] . "\n";
	echo $volunteer['city'] . ", " . $volunteer['state']. " ". $volunteer['postal_code']." ". $volunteer['country']."\n";
	echo "</PRE>";

     ?>

<FORM method="post" action=".">
<INPUT type="hidden" name="vid" value="<?php echo $vid;?>">          

<input type="submit" name="volunteer_delete" value="<?php echo _("Delete volunteer"); ?>">
<?php echo _("Confirm"); ?> <input type="checkbox" name="delete_confirm"> 


<?php

   }


} /* volunteer_delete() */




function volunteer_save()
{

global $db;
global $volunteer;

// to do: validate

// sanitize input

$organization = $db->escape_string(htmlentities($_POST['organization']));

$prefix = $db->escape_string(htmlentities($_POST['prefix']));
$first = $db->escape_string(htmlentities($_POST['first']));
$middle = $db->escape_string(htmlentities($_POST['middle']));
$last = $db->escape_string(htmlentities($_POST['last']));
$suffix = $db->escape_string(htmlentities($_POST['suffix']));

$street = $db->escape_string(htmlentities($_POST['street']), TRUE);
$city = $db->escape_string(htmlentities($_POST['city']), TRUE);
$state = $db->escape_string(htmlentities($_POST['state']), TRUE);
$postal_code = $db->escape_string(htmlentities($_POST['postal_code']));
$country = $db->escape_string(htmlentities($_POST['country']));

$email_address = $db->escape_string(htmlentities($_POST['email_address']));

$phone_home = $db->escape_string(htmlentities($_POST['phone_home']));
$phone_work = $db->escape_string(htmlentities($_POST['phone_work']));
$phone_cell = $db->escape_string(htmlentities($_POST['phone_cell']));

if (array_key_exists('wants_monthly_information', $_POST))
$wants_monthly_information = $db->escape_string($_POST['wants_monthly_information']);
else
$wants_monthly_information = 'N';

$vid = intval($_POST['vid']);

$sql = "UPDATE volunteers SET " .
	"organization='$organization', ".
	"prefix='$prefix', " .
	"first='$first', " .
	"middle='$middle', " .
	"last='$last', " .
	"suffix='$suffix', " .
	"street='$street', " .
	"city='$city', " .
	"state='$state', " .
	"postal_code='$postal_code', " .
	"country='$country', " .	
	"email_address='$email_address', " .
	"phone_home='$phone_home', " .
	"phone_cell='$phone_cell', " .
	"phone_work='$phone_work', " .
	"wants_monthly_information='$wants_monthly_information' ".
	"WHERE volunteer_id=$vid LIMIT 1";

// update primary volunteer record

$success_primary = FALSE != $db->query($sql);

if (!$success_primary)
{
    process_system_error(_("Error updating primary volunteer record."), array('debug'=>$db->error()));
}

// gather custom fields from POST

$custom = array();

foreach ($_POST as $key => $value)
{
    if (preg_match('/^custom_(\w{1,})$/', $key, $matches))
    {
	$custom[$matches[1]] = array('value' => $value, 'save' => FALSE);	
    }
}

// sanitize and validate custom fields

// get extended fields data from database

$result_meta = $db->query("SELECT * FROM extended_meta");

if ($result_meta)
{
    while (FALSE != ($row_meta = $db->fetch_array($result_meta)))
    {
        if (array_key_exists($row_meta['code'], $custom))
	{
	    switch ($row_meta['fieldtype'])
	    {
		case 'date':			
		    $new_value = sanitize_date($custom[$row_meta['code']]['value']);		  



		    if ($new_value)
		    {
		    	$custom[$row_meta['code']]['save'] = TRUE;
			$custom[$row_meta['code']]['value'] = "'$new_value'";
		    }
		    elseif (empty($custom[$row_meta['code']]['value']))
		    {
			$custom[$row_meta['code']]['value'] = "NULL";
		    }
		    else
		    {
			process_user_error("Bad date format.");
			$custom[$row_meta['code']]['value'] = "NULL";
		    }

		break;
		    
		case 'string':		
		case 'textarea':		
	    	    $custom[$row_meta['code']]['value'] = "'".$db->escape_string(htmlentities($custom[$row_meta['code']]['value']))."'";
	    	    $custom[$row_meta['code']]['save'] = TRUE;
		break;

		case 'integer':		
	    	    $custom[$row_meta['code']]['value'] = intval($custom[$row_meta['code']]['value']);
	    	    $custom[$row_meta['code']]['save'] = TRUE;
		break;
	    
	    }    
	}
    }
}
else
{
    process_system_error(_("Error querying database."), array('debug' => $db->get_error()));
}

$db->free_result($result_meta);

// save extended data

// build SQL

$sql = 'REPLACE into extended ';
$sql_names = '(volunteer_id';
$sql_values = "($vid";
$extended_count = 0;
foreach ($custom as $key => $value)
{
    if ($value['save'])
    {
	$sql_names .= ", $key";
	$sql_values .= ", ".$value['value'];
	$extended_count++;
    }
}

$sql_names .= ') ';
$sql_values .= ') ';

$sql .= " $sql_names VALUES $sql_values";

// save if extended fields exist

if ($extended_count > 0)
{
    $success_extended = (FALSE != $db->query($sql));        
    
    if (!$success_extended)
    {
        process_system_error(_("Error updating extended volunteer record."), array('debug' => $db->error()));    
    }
}
else
{
    // no extended fields
    $success_extended = TRUE;
}

// redisplay volunteer record

if ($success_primary and $success_extended)
{
    echo("<P>"._("Updated.")."</P>\n");
    $volunteer = volunteer_get($vid);
    volunteer_view_general();
}


} /* volunteer_save() */


function volunteer_view_general()
{
global $db;
global $volunteer;

$vid = intval($_REQUEST['vid']);

//    echo ("<H3>General information</H3>\n");

$prefix = $volunteer['prefix'];
$first = $volunteer['first'];
$middle = $volunteer['middle'];
$last = $volunteer['last'];
$suffix = $volunteer['suffix'];

$organization = $volunteer['organization'];

$street = $volunteer['street'];
$city = $volunteer['city'];
$state = $volunteer['state'];
$postal_code = $volunteer['postal_code'];
$country = $volunteer['country'];

$email_address = $volunteer['email_address'];
$phone_home = $volunteer['phone_home'];
$phone_work = $volunteer['phone_work'];
$phone_cell = $volunteer['phone_cell'];

?>


<form method="post" action=".">

<table border="0" cellspacing="0" cellpadding="0" table="60%" class="form">
<tr>
 <th class="vert"><?php echo _("Prefix");?></th>
 <td><input type="Text" name="prefix" value="<?php echo $prefix ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("First name");?></th>
 <td><input type="Text" name="first" value="<?php echo $first ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert">Middle name</th>
 <td><input type="Text" name="middle" value="<?php echo($middle); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Last name");?></th>
 <td><input type="Text" name="last" value="<?php echo ($last); ?>" size="40"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Suffix");?></th>
 <td><input type="Text" name="suffix" value="<?php echo $suffix ?>" size="10"></td>
 </tr>

<tr>
 <th class="vert"><?php echo _("Organization");?></th>
 <td><input type="Text" name="organization" value="<?php echo $organization ?>" size="40"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Street");?></th>
 <td><input type="Text" name="street" value="<?php echo ($street); ?>" size="40"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("City");?></th>
 <td><input type="Text" name="city"  value="<?php echo ($city); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("State/Province");?></th>
 <td><input type="Text" name="state"  value="<?php echo ($state); ?>" size="2"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Zip/Postal code");?></th>
 <td><input type="Text" name="postal_code"  value="<?php echo ($postal_code); ?>" size="10"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Country");?></th>
 <td><input type="Text" name="country"  value="<?php echo ($country); ?>" size="30"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Home phone");?></th>
 <td><input type="Text" name="phone_home" value="<?php echo ($volunteer["phone_home"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Work phone");?></th>
 <td><input type="Text" name="phone_work" value="<?php echo ($volunteer["phone_work"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Cell phone");?></th>
 <td><input type="Text" name="phone_cell" value="<?php echo ($volunteer["phone_cell"]); ?>" size="20"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("E-mail");?></th>
 <td><input type="Text" name="email_address" value="<?php echo ($volunteer["email_address"]); ?>" size="40"></td>
 </tr>
<tr>
 <th class="vert">Monthly mail</th>
 <td>
   <INPUT type="radio" name="wants_monthly_information" <?php echo(display_position("p", $volunteer["wants_monthly_information"])); ?>> Postal mail
   <INPUT type="radio" name="wants_monthly_information" <?php echo(display_position("e", $volunteer["wants_monthly_information"])); ?>><?php echo _("E-mail");?>
   <INPUT type="radio" name="wants_monthly_information" <?php echo(display_position("n", $volunteer["wants_monthly_information"])); ?>>None
   </TD>
 </tr>
<?php
// show custom fields
// to do: SQL_CACHE

$result_ext = $db->query("SELECT * FROM extended WHERE volunteer_id = $vid");
if ($result_ext)
{
    $row_ext = $db->fetch_array($result_ext);
}
else 
{
    $row_ext = array();
}

$result_meta = $db->query("SELECT * FROM extended_meta");
if ($result_meta)
{
    while (FALSE != ($row_meta = $db->fetch_array($result_meta)))
    {
	echo ("<TR>\n");
	echo ("<TH class=\"vert\">".$row_meta['label']."</TH>\n");		
	echo ("<TD>");
	switch ($row_meta['fieldtype'])
	{
	    case 'string':
		$attributes = array('length' => $row_meta['size1']);
		break;
	    case 'date':
	    case 'integer':
		$attributes = array();
		break;		
	    case 'textarea':
		$attributes = array('rows' => $row_meta['size3'], 'cols' => $row_meta['size2']);
		break;		
	    default:
		process_system_error("Unexpected type in extended_meta");
		break;
	}
	$value = $row_ext[$row_meta['code']];
	render_form_field($row_meta['fieldtype'], 'custom_'.$row_meta['code'], $attributes, $value);
	echo ("<TD>\n");
	echo ("</TR>\n");	
    }
}
$db->free_result($result_meta);
?>
</table>

<INPUT type="hidden" name="vid" value="<?php echo $vid; ?>">
<INPUT type="submit" name="volunteer_save" value="<?php echo _("Save"); ?>">
<INPUT type="submit" name="volunteer_delete" VALUE="<?php echo _("Delete volunteer"); ?>">
</FORM>
<?

} /* volunteer_view_general() */


function volunteer_view()
{

global $db;
global $volunteer;
global $vid;

if (!array_key_exists('vid', $_REQUEST))
{
	process_system_error(_("You have reached this page incorrectly."));
	die();
}

$vid = intval($_REQUEST['vid']);

$volunteer = volunteer_get($vid);

// keep an array of recently opened volunteers
if (!array_key_exists('recent_vid', $_SESSION))
	$_SESSION['recent_vid']  = array();

if (!array_search($vid, $_SESSION['recent_vid']))
{
	array_pop($_SESSION['recent_vid']);
	$vname = $volunteer['first']. " ".$volunteer['middle']. " ".$volunteer['last'];
	if (!empty($volunteer['organization']))
	$vname .= "(".$volunteer['organization'].")";
	array_unshift($_SESSION['recent_vid'],array('vid'=>$vid, 'name'=>$vname));

}

// execute requested action
if (array_key_exists('volunteer_save', $_POST))
	volunteer_save();
else
{

if (array_key_exists('menu', $_GET))
{
	if ('workhistory' == $_GET['menu'])
	{
	include ('workhistory.php');
	volunteer_view_work_history();
	work_history_addedit('add');
	}
	else if ('skills' == $_GET['menu'])
	{
		include('skills.php');
		volunteer_view_skills();
	}
	else if ('availability' == $_GET['menu'])
	{
		include('availability.php');
		volunteer_view_availability();
	}
	else if ('notes' == $_GET['menu'])
	{
		include('notes.php');
		volunteer_view_notes();
		volunteer_add_note_form();
	}
	else if ('relationships' == $_GET['menu'])
	{
		include('relationships.php');
		relationships_view();
		relationships_add_form();		
	}
	else if ('general' == $_GET['menu'])
	{
		volunteer_view_general();
	}
	else
		process_system_error(_("Bad form input:").' GET[menu]');


}
	else
	{
		include('summary.php');
		volunteer_summary();
	}
//	    volunteer_view_general();
}

} /* volunteer_view() */


make_html_end();

?>
