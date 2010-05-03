<?php
/**
 * Part of the "Downloads Manager" Plugin for MyBB
 *
 * @access public
 * @author Jan Malte Gerth
 * @category MyBB Plugin
 * @copyright Copyright (c) 2009 Jan Malte Gerth (http://www.malte-gerth.de)
 * @license http://mybb.malte-gerth.de/license.html MyBBCoder License v1
 * @package Downloads Manager
 * @since Version 1.0
 * @subpackage Admin
 * @todo see below
 * @version $Id: categories.php 202 2009-03-28 09:39:05Z JanMalte $
 *
 * DONE fixed unescaped input for SQL queries
 * DONE add function to add sub categories
 * DONE optimize displaying sub categories
 * TODO check and improve security
 * TODO optomize functions
 * TODO clarify variable and function names
 * TODO comment code better
 * DONE old version of the file; jan malte gerth 2009-03-17
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// load language strings
$lang->load("downloads");

// add our page to the navigation links
$page->add_breadcrumb_item($lang->downloads_dlmngr_cats, "index.php?module=dlmngr/cats");

if($mybb->input['action'] == "delete")
{	// remove the entry from the database
$catid = $mybb->input['catid'];
$db->delete_query("downloads_catlist","catid='$catid'");
$db->delete_query("downloads_catlist","parentID='$catid'");
}

if($mybb->input['action'] == "save")
{	// update the entry
$catid = $mybb->input['catid'];
$update = array("title" => $db->escape_string($mybb->input['title']));
$db->update_query("downloads_catlist", $update, "catid='$catid'");
}

if($mybb->input['action'] == "edit")
{
	$form = new Form("index.php?module=dlmngr/cats&amp;action=save&amp;catid=".$mybb->input['catid'], "post", "", 1);
}
elseif($mybb->input['action'] == "addsub")
{
	$form = new Form("index.php?module=dlmngr/cats&amp;action=savesub", "post", "", 1);
}
else
{
	$form = new Form("index.php?module=dlmngr/cats&amp;action=add", "post", "", 1);
}

if($mybb->input['action'] == "add")
{
	$newcat = array(
		"title"	=> $mybb->input['title']
	);
	$db->insert_query("downloads_catlist", $newcat);
}
else if($mybb->input['action'] == "savesub")
{
	$newcat = array(
		"title"	=> $mybb->input['title'],
		"parentID"	=> $mybb->input['parentcat'],
	);
	$db->insert_query("downloads_catlist", $newcat);
}
// output standard page stuff, with our title
$page->output_header($lang->downloads_dlmngr_cats);

// create the page table, and add the column headers

$table = new Table;
$table->construct_header($lang->downloads_dlmngr_cat);
$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));

// function to get all sub categories
function getSubCat($id = false)
{
	global $db, $mybb, $table, $form, $lang, $popup, $subdeepth;
	// get the downloads
	if($id == false)
	{
		$query = $db->simple_select("downloads_catlist", "*", "parentID IS NULL", array("order by" => "catid"));
	}
	else
	{
		$query = $db->simple_select("downloads_catlist", "*", "parentID=$id");
		$subdeepth .= "<img src=\"/admin/styles/default/images/nav_bit.gif\" /> ";
	}

	while($cat = $db->fetch_array($query))
	{	// display each category

		// get some info about the category
		$catname = $cat['title'];
		$catid = $cat['catid'];

		// create the info cell
		// construct_cell(content, array(html modifiers))

		if(($mybb->input['action'] == "addsub") && ($catid == $mybb->input['catid']))
		{
			$table->construct_cell("<strong><a href=\"index.php?module=dlmngr/cats&amp;action=edit&amp;catid={$catid}\">{$catname}</a></strong>",array("colspan" => "2"));
			// output the row
			$table->construct_row();
			$table->construct_cell($subdeepth."<img src=\"/admin/styles/default/images/nav_bit.gif\" /> ".$form->generate_text_box('title', "Sub - $catname", array('id' => 'title')));
			$table->construct_cell($form->generate_hidden_field('parentcat', $catid, array('id' => 'parentcat')).$form->generate_submit_button($lang->downloads_dlmngr_save, array('name' => 'savesub')), array("class" => "align_center"));	
		}
		else if(($mybb->input['action'] != "edit") || ($catid != $mybb->input['catid']))
		{
			$table->construct_cell($subdeepth."<strong><a href=\"index.php?module=dlmngr/cats&amp;action=edit&amp;catid={$catid}\">{$catname}</a></strong>");	
			$popup = new PopupMenu("cat_$catid", $lang->options);
			$popup->add_item($lang->downloads_dlmngr_edit, "index.php?module=dlmngr/cats&amp;action=edit&amp;catid=$catid");
			$popup->add_item($lang->downloads_dlmngr_delete, "index.php?module=dlmngr/cats&amp;action=delete&amp;catid=$catid");
			$popup->add_item($lang->downloads_dlmngr_addsub, "index.php?module=dlmngr/cats&amp;action=addsub&amp;catid=$catid");
			// create the menu cell
			$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		}
		else
		{
			$table->construct_cell($form->generate_text_box('title', $catname, array('id' => 'title')));
				
			$table->construct_cell($form->generate_submit_button($lang->downloads_dlmngr_save, array('name' => 'save')), array("class" => "align_center"));
		}

		// output the row
		$table->construct_row();
		getSubCat($catid);
	}
}

getSubCat();

if(($mybb->input['action'] != "edit") && ($mybb->input['action'] != "addsub"))
{
	// create the info cell
	// construct_cell(content, array(html modifiers))
	$table->construct_cell("<strong>{$lang->downloads_dlmngr_addnew}</strong><br/>".$form->generate_text_box('title', $lang->downloads_dlmngr_addnew, array('id' => 'title')));

	// create the menu cell
	$table->construct_cell($form->generate_submit_button($lang->downloads_dlmngr_save, array('name' => 'add')), array("class" => "align_center"));

	// output the row
	$table->construct_row();
}

// display the table with our title
$table->output($lang->downloads_dlmngr_cats);

$form->end();

// wrap up the page with the standard footer
$page->output_footer();

?>