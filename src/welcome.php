<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 * 
 *
 * $Id: welcome.php,v 1.12 2003/12/07 00:40:27 netgamer7 Exp $
 *
 */

session_start();
ob_start();

define('SOS_PATH', '../');

require_once(SOS_PATH . 'include/config.php');
require_once(SOS_PATH . 'include/global.php');
require_once(SOS_PATH . 'functions/html.php');
require_once(SOS_PATH . 'functions/access.php');
require_once(SOS_PATH . 'functions/db.php');

$db = connect_db();

if ($db->_connectionID == '')
{
    die_message(MSG_SYSTEM_ERROR, _("Unable to establish database connection."), __FILE__, __LINE__);
}

is_logged_in();

make_html_begin("Welcome", array());

make_nav_begin();

if (isset($_SESSION['user']['personalname']) and $_SESSION['user']['personalname'])
    $username = $_SESSION['user']['personalname'];
    else
    $username = $_SESSION['user']['username'];

$result = $db->Execute("SELECT note_id FROM notes WHERE reminder_date >= now() and uid_assigned = ".intval($_SESSION['user_id']));

$reminders = $result->RecordCount();

echo ("<P>Welcome, $username.  You have <A href=\"reminders.php\">$reminders reminders</A> waiting.</P>\n");



make_html_end();

?>
