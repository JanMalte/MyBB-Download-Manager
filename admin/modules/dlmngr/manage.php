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
 * @todo 
 * @version $Id: manage.php 210 2009-04-04 16:36:05Z JanMalte $
 * 
 * DONE fixed unescaped input for SQL queries
 * DONE delete files when the download is beeing deleted
 * TODO set the filename to a generic filename to pretend to overwrite files with the same name not just a random number
 * Done set the filename to a generic filename to pretend to overwrite files with the same name
 * DONE add function to set usergroups
 * TODO check and improve security
 * TODO optomize functions
 * TODO clarify variable and function names
 * TODO comment code better
 * DONE old version of the file; jan malte gerth 2009-03-17
 * DONE added a tab menu; wasn't neccessary
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// load language strings
$lang->load("downloads");

// add our page to the navigation links
$page->add_breadcrumb_item($lang->downloads_dlmngr_manage, "index.php?module=dlmngr/manage");

if($mybb->input['action']=="" || $mybb->input['action']=="delete")
{	// we're not editing an entry

	if($mybb->input['action']=="delete")
	{	// remove the entry from the database
		$dlid = $mybb->input['dlid'];
		// get the download
		$query = $db->simple_select("downloads", "*", "dlid='$dlid'");
	
		while($dlitem = $db->fetch_array($query))
		{
			$filename = $dlitem['filename'];
			$previewname = $dlitem['preview'];
		}
		$db->delete_query("downloads","dlid='$dlid'");
		// remove the download file
		// hide errors if file doesn't exist
		@unlink(MYBB_ROOT."/uploads/downloads/".$filename);
		@unlink(MYBB_ROOT."/uploads/downloads/previews/".$previewname);
	}
	
	// output standard page stuff, with our title
	$page->output_header($lang->downloads_dlmngr_manage);
	
	// not needed; just for having it as an option
	// take a look at it for other plugins
	function navTabsDlmngr()
	{
		global $page, $lang, $db;
		$sub_tabs['manage'] = array(
			'title' => $lang->downloads_dlmngr_manage,
			'link' => "index.php?module=dlmngr/manage",
			#'description' => $lang->system_health_desc
		);
		// get the link for the plugin settings
		$gid = $db->fetch_field($db->simple_select("settings", "gid", "name='downloads_isactive'"), "gid");
		$sub_tabs['settings'] = array(
			'title' => $lang->downloads_dlmngr_settings,
			'link' => "index.php?module=config/settings&action=change&gid=$gid",
			#'description' => $lang->utf8_conversion_desc2
		);	
		$page->output_nav_tabs($sub_tabs, 'manage');
	}
	#navTabsDlmngr();
	// well, now go on with the real code
	
	// create the page table, and add the column headers
	$table = new Table;
	$table->construct_header($lang->downloads_dlmngr_project);
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));
	
	// get the downloads
	$query = $db->simple_select("downloads", "dlid, title, desc_short, preview, public", "1=1 ORDER BY 'dlid' DESC");

	while($dlitem = $db->fetch_array($query))
	{	// display each download
	
		// get some info about the download
		$projectname = $dlitem['title'];
		$dlid = $dlitem['dlid'];
		$desc_short = $dlitem['desc_short'];
		
		// create the "Edit/Delete" popup menu
		$popup = new PopupMenu("project_$dlid", $lang->options);
		// Add the items
		$popup->add_item($lang->downloads_dlmngr_edit, "index.php?module=dlmngr/manage&amp;action=edit&amp;dlid=$dlid");
		$popup->add_item($lang->downloads_dlmngr_delete, "index.php?module=dlmngr/manage&amp;action=delete&amp;dlid=$dlid");
		if($dlitem['public'] == 1)
		{
			$popup->add_item($lang->downloads_dlmngr_reject, "index.php?module=dlmngr/manage&amp;action=reject&amp;dlid=$dlid");
		}
		else
		{
			$popup->add_item($lang->downloads_dlmngr_accept, "index.php?module=dlmngr/manage&amp;action=accept&amp;dlid=$dlid");
		}
		
		// create the info cell
		// construct_cell(content, array(html modifiers))
		$table->construct_cell("<a href=\"index.php?module=dlmngr/manage&amp;action=edit&amp;dlid=$dlid\"><strong>".$projectname."</strong></a><br /><span style=\"font-size: 75%;\">".$desc_short."</span>");
		// create the menu cell
		$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		
		// output the row
		$table->construct_row();
	}

	// display the table with our title
	$table->output($lang->downloads_dlmngr_manage);
	
	// wrap up the page with the standard footer
	$page->output_footer();
}
else if($mybb->input['action']=="reject" || $mybb->input['action']=="accept")
{	// we're not editing an entry

	if($mybb->input['action']=="reject")
	{	// reject this download
		$dlid = $mybb->input['dlid'];
		// write stuff to database
		$update_array = array(
			"public"		=> 0,
		);
		$db->update_query("downloads", $update_array, "dlid='$dlid'");
		
		// tell the user success and continue editing
		flash_message($lang->downloads_dlmngr_rejected, 'success');
	}
	else if($mybb->input['action']=="accept")
	{	// accept this download
		$dlid = $mybb->input['dlid'];
		// write stuff to database
		$update_array = array(
			"public"		=> 1,
		);
		$db->update_query("downloads", $update_array, "dlid='$dlid'");
		// tell the user success and continue editing
		flash_message($lang->downloads_dlmngr_accepted, 'success');
	}
	
	// output standard page stuff, with our title
	$page->output_header($lang->downloads_dlmngr_manage);
	
	// not needed; just for having it as an option
	// take a look at it for other plugins
	function navTabsDlmngr()
	{
		global $page, $lang, $db;
		$sub_tabs['manage'] = array(
			'title' => $lang->downloads_dlmngr_manage,
			'link' => "index.php?module=dlmngr/manage",
			#'description' => $lang->system_health_desc
		);
		// get the link for the plugin settings
		$gid = $db->fetch_field($db->simple_select("settings", "gid", "name='downloads_isactive'"), "gid");
		$sub_tabs['settings'] = array(
			'title' => $lang->downloads_dlmngr_settings,
			'link' => "index.php?module=config/settings&action=change&gid=$gid",
			#'description' => $lang->utf8_conversion_desc2
		);	
		$page->output_nav_tabs($sub_tabs, 'manage');
	}
	#navTabsDlmngr();
	// well, now go on with the real code
	
	// create the page table, and add the column headers
	$table = new Table;
	$table->construct_header($lang->downloads_dlmngr_project);
	$table->construct_header($lang->controls, array("class" => "align_center", "width" => 150));
	
	// get the downloads
	$query = $db->simple_select("downloads", "dlid, title, desc_short, preview", "1=1 ORDER BY 'dlid' DESC");

	while($dlitem = $db->fetch_array($query))
	{	// display each download
	
		// get some info about the download
		$projectname = $dlitem['title'];
		$dlid = $dlitem['dlid'];
		$desc_short = $dlitem['desc_short'];
		
		// create the "Edit/Delete" popup menu
		$popup = new PopupMenu("project_$dlid", $lang->options);
		// Add the items
		$popup->add_item($lang->downloads_dlmngr_edit, "index.php?module=dlmngr/manage&amp;action=edit&amp;dlid=$dlid");
		$popup->add_item($lang->downloads_dlmngr_delete, "index.php?module=dlmngr/manage&amp;action=delete&amp;dlid=$dlid");
		if($dlitem['public'] == 1)
		{
			$popup->add_item($lang->downloads_dlmngr_reject, "index.php?module=dlmngr/manage&amp;action=reject&amp;dlid=$dlid");
		}
		else
		{
			$popup->add_item($lang->downloads_dlmngr_accept, "index.php?module=dlmngr/manage&amp;action=accept&amp;dlid=$dlid");
		}
		
		// create the info cell
		// construct_cell(content, array(html modifiers))
		$table->construct_cell("<a href=\"index.php?module=dlmngr/manage&amp;action=edit&amp;dlid=$dlid\"><strong>".$projectname."</strong></a><br /><span style=\"font-size: 75%;\">".$desc_short."</span>");
		// create the menu cell
		$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		
		// output the row
		$table->construct_row();
	}

	// display the table with our title
	$table->output($lang->downloads_dlmngr_manage);
	
	// wrap up the page with the standard footer
	$page->output_footer();
}
else if($mybb->input['action']=="edit")
{	// we're editing a project download

	// get the project ID
	$dlid = $mybb->input['dlid'];

	// check if the user just saved
	// DONE; refused; Add a function for save and exit
	if($mybb->input['save']=="save")
	{	
		// error checking
		// TODO Change into several checks and set an boolean var / array for errors
		// FIXME extend check for external files
		// TODO Add a file browser here like in typolight to choose the file we want to
		if(
			(!file_exists(MYBB_ROOT."/uploads/downloads/".$mybb->input['filename']) OR $mybb->input['filename']=="")
			AND
			(
				(substr($mybb->input['filename'],0,6) != 'ftp://')
				AND
				(substr($mybb->input['filename'],0,7) != 'http://')
			)
		  )
		{	// download file doesn't exist
			flash_message($lang->downloads_dlmngr_baddownload, 'error');
		}
		// TODO Add a popupmenu or a file browser to choose the preview
		else if(
				(!file_exists(MYBB_ROOT."/uploads/downloads/".$mybb->input['preview']) && $mybb->input['preview']!="")
				AND
				(
					(substr($mybb->input['filename'],0,6) != 'ftp://')
					AND
					(substr($mybb->input['filename'],0,7) != 'http://')
				)
			)
		{	// preview isn't empty and doesn't exist
			flash_message($lang->downloads_dlmngr_badpreview, 'error');
		}
		else
		{	// no errors
			
			// Determine the usergroup stuff
			// TODO Check if we have to check if it is an array
			if(is_array($mybb->input['grplist']))
			{
				$groups = implode(",", $mybb->input['grplist']);
			}
			else
			{
				$groups = $mybb->input['grplist'];
			}
			
			// write stuff to database
			// DONE Escape all strings
			$update_array = array(
				"title"		=> $db->escape_string($mybb->input['title']),
				"desc_short"	=> $db->escape_string($mybb->input['desc_short']),
				"description"	=> $db->escape_string($mybb->input['description']),
				"fid"		=> $mybb->input['fid'],
				"cat"		=> $mybb->input['cat'],
				"preview"	=> $db->escape_string($mybb->input['preview']),
				"filename"	=> $db->escape_string($mybb->input['filename']),
				"grpflag"	=> $mybb->input['grpflag'],
				"grplist"	=> $groups,
			);
			$db->update_query("downloads", $update_array, "dlid='$dlid'");
			
			// tell the user success and continue editing
			flash_message($lang->downloads_dlmngr_saved, 'success');
		}
	}
	
	// display navigation link
	$page->add_breadcrumb_item($lang->downloads_dlmngr_edit, "index.php?module=dlmngr/manage&amp;action=edit&amp;dlid=$dlid");
	
	// Standard header
	$page->output_header($lang->downloads_dlmngr_edit);
	
	// create a form to edit our project
	$form = new Form("index.php?module=dlmngr/manage&amp;action=edit&amp;dlid=$dlid", "post", "", 1);
	
	$query = $db->simple_select("downloads", "*", "dlid = $dlid");
	$dlitem = $db->fetch_array($query);
	
	// if the user tried to save, don't wipe all of the entered fields in case of error
	if($mybb->input['save']=="save")
	{
		$dlitem['title'] = $mybb->input['title'];
		$dlitem['desc_short'] = $mybb->input['desc_short'];
		$dlitem['description'] = $mybb->input['description'];
		$dlitem['fid'] = $mybb->input['fid'];
		$dlitem['cat'] = $mybb->input['cat'];
		$dlitem['grpflag'] = $mybb->input['grpflag'];
		$dlitem['grplist'] = $mybb->input['grplist'];
	}
	
	// Prepare the data
	if(!is_array($dlitem['grplist']))
	{
		$dlitem['grplist'] = explode(',', $dlitem['grplist']);
	}
	
	// create a standard form container
	$form_container = new FormContainer($lang->downloads_dlmngr_edit);
	
	// create the save flag
	echo $form->generate_hidden_field("save", "save", array('id' => "save"))."\n";
	
	// display the text fields
	// output_row(title, desc, item, something I just set to the same as the ID)
	// generate_INPUTTYPE(name, vlaue, array(html modifiers))
	$form_container->output_row($lang->downloads_dlmngr_title, $lang->downloads_dlmngr_title_desc, $form->generate_text_box('title', $dlitem['title'], array('id' => 'title')), 'title');
	$form_container->output_row($lang->downloads_dlmngr_desc_short, $lang->downloads_dlmngr_desc_short_desc, $form->generate_text_box('desc_short', $dlitem['desc_short'], array('id' => 'desc_short')), 'desc_short');
	$form_container->output_row($lang->downloads_dlmngr_description, $lang->downloads_dlmngr_description_desc, $form->generate_text_area('description', $dlitem['description'], array('id' => 'description', 'style' =>'width: 100%;')), 'description');
	$form_container->output_row($lang->downloads_dlmngr_fid, $lang->downloads_dlmngr_fid_desc, $form->generate_forum_select('fid', $dlitem['fid'], array('id' => 'fid', 'main_option' => $lang->none)), 'fid');

	$query = $db->simple_select("downloads_catlist", "*", "1=1");
	while($cat = $db->fetch_array($query))
	{
		$categories[$cat['catid']] = $cat['title'];
	}
	$form_container->output_row($lang->downloads_dlmngr_cat, $lang->downloads_dlmngr_cat_desc, $form->generate_select_box('cat', $categories, $dlitem['cat'], array('id' => 'cat')), 'cat');
	$form_container->output_row($lang->downloads_dlmngr_grpflag, $lang->downloads_dlmngr_grpflag_desc, $form->generate_check_box('grpflag', '1', $lang->downloads_dlmngr_grpflag_desc_option ,array('checked' => $dlitem['grpflag'])) ,'grpflag');
	
	$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
	while($usergroup = $db->fetch_array($query))
	{
		$options[$usergroup['gid']] = $usergroup['title'];
		$display_group_options[$usergroup['gid']] = $usergroup['title'];
	}
	$form_container->output_row($lang->downloads_dlmngr_grpflag, $lang->downloads_dlmngr_grpflag_desc, $form->generate_select_box('grplist[]', $options, $dlitem['grplist'], array('multiple' => true, 'size' => 5)),'grplist');
	$form_container->output_row($lang->downloads_dlmngr_preview, $lang->downloads_dlmngr_preview_desc, $form->generate_text_box('preview', $dlitem['preview'], array('id' => 'preview')), 'preview');
	$form_container->output_row($lang->downloads_dlmngr_filename, $lang->downloads_dlmngr_filename_desc, $form->generate_text_box('filename', $dlitem['filename'], array('id' => 'filename')), 'filename');
	
	// end the container
	$form_container->end();
	
	// add the save button
	$buttons[] = $form->generate_submit_button($lang->downloads_dlmngr_save);
	
	// display and end
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	// end the page
	$page->output_footer();
}

?>