<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * $Id: add_volunteer.php,v 1.1 2003/10/05 16:14:35 andrewziem Exp $
 *
 */

ob_start();
session_start();

define('SOS_PATH', '../');

require_once (SOS_PATH . 'include/global.php');
require_once (SOS_PATH . 'include/config.php');
require_once (SOS_PATH . 'functions/html.php');


make_html_begin(_("Add a volunteer"), array());

is_logged_in();

make_nav_begin();

echo ("<H3>Add a volunteer</H3>\n");

function volunteer_add()
{
    global  $base_url;
    
    // validate form input

    $errors_found = 0;

    if (!isset($_POST['first']) or 0 == strlen(trim($_POST['first'])))
    {
       process_user_error("Please return to the form and give volunteer a first name.");
       $errors_found++;
    }

    if (!isset($_POST['last']) or 0 == strlen(trim($_POST['last'])))
    {
       process_user_error("Please return to the form and give volunteer a last name.");
       $errors_found++;
    }

    if ($errors_found)
    {    
	  echo ("<P>Try <A href=\"add_volunteer.php\">again</A>.</P>\n");
	  die();
    }
    
    // init database

    $db = new voldbMySql();

    if ($db->get_error())
    {
	process_system_error("Unable to establish database connection: ".$db->get_error());    
	die();	
    }
    
    $organization = $db->escape_string($_POST['organization']);

    $prefix = $db->escape_string($_POST['prefix']); 
   $first = $db->escape_string($_POST['first']);
   $middle = $db->escape_string($_POST['middle']);      
   $last = $db->escape_string($_POST['last']);      
   
   $street = $db->escape_string($_POST['street']);         
   $city = $db->escape_string($_POST['city']);            
   $state = $db->escape_string($_POST['state']);
   $zip = $db->escape_string($_POST['zip']);   
   
   $email_address = $db->escape_string($_POST['email_address']);      
   
   $phone_home = $db->escape_string($_POST['phone_home']);
   $phone_work = $db->escape_string($_POST['phone_work']);   
   $phone_cell = $db->escape_string($_POST['phone_cell']);      

   //$wants_monthly_information = $_POST['wants_monthly_information'];             

    $sql = 'INSERT INTO volunteers '.
	    '(prefix, first,middle,last,organization,street,city,state,zip,phone_home,phone_work,phone_cell,email_address, dt_added, uid_added) '.
	    "VALUES ('$prefix', '$first', '$middle', '$last', '$organization', '$street', '$city', '$state', '$zip', '$phone_home', '$phone_work', '$phone_cell', '$email_address', now(), ".$_SESSION['user_id'].")";

    $result = $db->query($sql);

    if (!$result) { // unsuccessful save
	    // fixme: put mysql_Error seperatly for security
	    process_system_error(_("Error adding volunteer to database."), array('debug' => mysql_error()));
            exit();
    }
    
    $vid = mysql_insert_id();

    echo ("<P>You have added <A href=\"${base_url}volunteer/?vid=$vid\">" . $first . " " . $last . "</A>.</P>\n");


} /* add_volunteer() */


function volunteer_add_form()
{
    
?>    
    <form method="post" action="add_volunteer.php">

<table border="0" width="50%" cellspacing="0" cellpadding="0">
<tr>
 <th class="vert"><?php echo _("Prefix"); ?></th>
 <td><input type="Text" name="prefix"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("First name"); ?></th>
 <td><input type="Text" name="first"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Middle name"); ?></th>
 <td><input type="Text" name="middle"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Last name"); ?></th>
 <td><input type="Text" name="last"></td>
 </tr>
<tr>
 <th class="vert"><?php echo _("Organization"); ?></th>
 <td><input type="text" name="organization"></td>
 </tr> 
<tr>
 <th class="vert"><?php echo _("Street"); ?></th>
 <td><input type="Text" name="street"></td>
 </tr>
<tr>
 <th class="vert">City</th>
 <td><input type="Text" name="city"></td>
 </tr>
<tr>
 <th class="vert">State</th>
 <td><input type="Text" name="state"></td>
 </tr>
<tr>
 <th class="vert">Zip</th>
 <td><input type="Text" name="zip"></td>
 </tr>
<tr>
 <th class="vert">Home Phone</th>
 <td><input type="Text" name="phone_home"></td>
 </tr>
<tr>
 <th class="vert">Work Phone</th>
 <td><input type="Text" name="phone_work"></td>
 </tr>
<tr>
 <th class="vert">Cell Phone</th>
 <td><input type="Text" name="phone_cell"></td>
 </tr>
<tr>
 <th class="vert">Email Address</th>
 <td><input type="Text" name="email_address"></td>
 </tr>


</table>
<input type="submit" name="button_add_volunteer" value="<?php echo _("Save new volunteer");?>">
<input type="reset" value="Erase form">

</form>
<?php

} /* volunteer_add_form() */

if (array_key_exists('button_add_volunteer', $_POST)) 
  volunteer_add();
  else 
  volunteer_add_form();  
  
  

make_html_end();

?>

