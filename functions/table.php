<?php

/*
 * Son of Service
 * Copyright (C) 2003 by Andrew Ziem.  All rights reserved.
 * Licensed under the GNU General Public License.  See COPYING for details.
 *
 * Generates an HTML table from a set of data.
 *
 * $Id: table.php,v 1.5 2003/12/20 23:48:41 andrewziem Exp $
 *
 */

if (preg_match('/table.php/i', $_SERVER['PHP_SELF']))
{
    die('Do not access this page directly.');
}

// todo: printable (supress INPUT) [test]
// todo: break row [test]
// todo: htmlspecialchars 
// todo: same function names as for tab, csv class
// todo: localize date, datetime per user
// todo: resortable

define('TT_STRING', 1);
define('TT_NUMBER', 2);
define('TT_DATE', 3);
define('TT_INPUT', 4);

class DataTableDisplay
{
    var $headers;	// information about columns
    var $printable;	// boolean


    function DataTableDisplay()
    {
	$this->printable = FALSE;
	$this->headers = NULL;
    }

    function setHeaders($headers)
    // $headers: an array
    //  key: field name (for addRow())
    //  subkey: label
    //  subkey: checkbox
    //  subkey: link
    //  subkey: type
    {
	assert(is_array($headers));
	$this->headers = $headers;
    }
    
    function setPrintable($b = TRUE)
    {
	assert(is_bool($b));
	$this->printable = $b;
    }

    function begin()
    {
	echo ("<TABLE border=\"1\">\n");
	if (isset($this->headers) and is_array($this->headers))
	{
	    echo ("<TR>\n");
	    foreach ($this->headers as $k => $v)
	    {
		if (array_key_exists('label', $v))
		{
		    // explicit column label
		    $label = $v['label'];
		}
		else
		{
		    // implicit column label		
		    $label = ucfirst($k);
		}
		
		if (array_key_exists('checkbox', $this->headers[$k]) and $this->printable)
		{
		}
		else
		{
		    echo ("<TH>$label</TH>\n");
		}
//	if (array_key_exists('sortable', $v) and $v['sortable'])
//	    echo ("<A href=\"\">[Sort Asc]</A>\n");//remove

	    }
	    echo ("</TR>\n");    
	}
    }

    function end()
    {
	echo ("</TABLE>\n");
    }

    function addRow($row)
    {
	assert(is_array($row));
	if (!is_array($row))
	{
	    return;
	}
	echo ("<TR>\n");
	if (isset($this->headers) and is_array($this->headers))
	{
	    foreach ($this->headers as $k => $v)
	    {
		if (array_key_exists('break_row', $v))
		{
		    // break row
		    echo ("</TR>\n");
		    echo ("<TR>\n");
		}
		else if (array_key_exists('radio',$v) and $v['radio'])
		{
		    // make radio
		    // todo: revisit value=
		    if (!$this->printable)
		    {
			echo ("<TD><INPUT type=\"radio\" name=\"$k\" value=\"".$row[$k]."\"></TD>\n");
		    }
		}
		else if (array_key_exists('checkbox',$v) and $v['checkbox'])
		{
		    // make checkbox
		    // todo: revisit value=
		    if (!$this->printable)
		    {
		        echo ("<TD><INPUT type=\"checkbox\" name=\"${k}_".$row[$k]."\" value=\"".$row[$k]."\"></TD>\n");
		    }
		}
		else
		{
		    // display cell data
		    echo ("<TD>");

		    if (0 == strlen(trim($row[$k])))
		    {
			// blank cell
			$c = "&nbsp;";
		    }
		    else
		    {
		        $c = $row[$k];
		    }

		    if (array_key_exists('link', $v) and $v['link'])
		    {
			if (preg_match_all("/\#(\w+)\#/", $v['link'], $tagss))
			foreach ($tagss as $tags)
			{
			    $tag = $tags[0];
			    if (!preg_match("/#/", $tag))
			    {
				if (array_key_exists($tag, $row))
				{
				    $v['link'] = preg_replace("/#$tag#/", $row[$tag], $v['link']);
				}
			        else
				{
				    process_system_warning("$tag not in row");
				}
			    }
			}
			echo ("<A href=\"".$v['link']."\">$c</A>");
		    }
		    else
		    {
			echo $c;
		    }
		    echo ("</TD>\n");
	    
		}
/*		
	    else
	    {
		foreach ($row as $c)
		{
	    	    echo ("<TD>$c</TD>");
		}
	    }
*/
	    }	    
    	}

	echo ("</TR>\n");

    } /* addRow() */

} /* class DataTableDisplay */


class DataTablePager extends DataTableDisplay
// for use with database queries
{
    var $offset;	// NULL or integer, for pagintaion
    var $rows_per_page; // NULL or integer, for pagination
    var $db;		// ADOdb database connection
    var $db_result;	// ADOdb database result
    
    
    function DataTablePager()
    {
	$this->db = NULL;
	$this->db_result = NULL;
    }

    function setDatabase(&$db, $db_result)
    {
	$this->db = $db;
	$this->db_result = $db_result;
	$this->offset = 0;
	$this->rows_per_page = NULL;
    }
    
    function setPagination($rows_per_page = 10, $offset = NULL)
    {
	assert(is_numeric($rows_per_page));
	if (NULL == $offset and array_key_exists('offset', $_GET))
	{
	    $this->offset = intval($_GET['offset']);
	}
	else if (NULL != $offset)
	{
	    $this->offset = $offset;
	}
	$this->rows_per_page = intval($rows_per_page);
    }
    
    function printNavigation()
    {
	$url = make_url($_GET, 'offset');
	
	echo ("<P>");
	// first, previous
	if ($this->offset > 0)
	{
	    // first, previous exist
	    echo ("<A href=\"$url&offset=0\">|&lt;</A> ");
	    $previous = $this->offset - $this->rows_per_page;
	    if ($previous < 0)
	    {
		$previous = 0;	
	    }
	    echo ("<A href=\"$url&offset=$previous\">&lt;&lt;</A> ");	    
	    
	}
	else
	{
	    // at first: no previous records
	    echo ("|&lt &lt;&lt;\n");
	
	}
	
	// next, last
	if ($this->offset + $this->rows_per_page < $this->db_result->RecordCount())
	{
	    // next
	    echo ("<A href=\"$url&offset=".($this->offset + $this->rows_per_page)."\">&gt&gt;;</A> ");
	    // last
	    echo ("<A href=\"$url&offset=".($this->db_result->RecordCount() - ($this->db_result->RecordCount() % $this->rows_per_page))."\">&gt;|</A> ");	    
	
	}
	else
	{
	    // last page, no more
	    echo ("&gt;&gt; &gt;|\n");	
	}
	echo ("</P>\n");	
    }
    
    function render()
    // creates the whole table including page navigation commands
    {
	assert($this->db != NULL);
	assert($this->db_result != NULL);	
	if (NULL != $this->offset)
	{
	    $this->db_result->Move($this->offset);
	}
	if (NULL == $this->rows_per_page)
	{
	    $c = 0;
	}
	else
	{
	    $c = $this->rows_per_page;	
	}
	if ($this->db_result->RecordCount() > $this->rows_per_page and !$this->printable)
	{
	    echo ("<TABLE border=\"1\" class=\"pagination\"><TR><TD class=\"pagination\">\n");
	    // print navigation
	    $this->printNavigation();
	}	
	$this->begin();
	while (!$this->db_result->EOF and ($c > 0 or NULL == $this->rows_per_page))
	{
	    $fields  = $this->db_result->fields;
	    assert(is_array($fields));
	    $this->addRow($fields);
	    $this->db_result->MoveNext();
	    $c--;
	}
	$this->end();
	if ($this->db_result->RecordCount() > $this->rows_per_page and !$this->printable)
	{
	    // navigation
	    echo ("<P>Page ".intval(1 + ($this->offset / $this->rows_per_page))." of ".ceil($this->db_result->RecordCount() / $this->rows_per_page)."</P>\n");
	    echo ("</TD></TR></TABLE>\n");
	}
    }
} /* class DataTablePager */


?>
