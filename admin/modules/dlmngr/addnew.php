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
 * @version $Id: addnew.php 214 2009-04-18 18:30:16Z JanMalte $
 * 
 * DONE fixed unescaped input for SQL queries
 * TODO set the filename to a generic filename to pretend to overwrite files with the same name not just a random number
 * Done set the filename to a generic filename to pretend to overwrite files with the same name
 * TODO add the function of a file browser to select the download file if wanted
 * DONE add the function to transfer the files to a ftp server
 * DONE add function to set usergroups
 * TODO optimize saving input in case of an error
 * TODO check and improve security
 * TODO optomize functions
 * TODO clarify variable and function names
 * TODO comment code better
 * DONE old version of the file; jan malte gerth 2009-03-17
 * 
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// load language strings
$lang->load("downloads");

$page->add_breadcrumb_item($lang->downloads_dlmngr_addnew, "index.php?module=dlmngr/addnew");

// set up default values
// DONE get the text from the language files to localize it
$title = $lang->downloads_projectname;
$desc_short = $lang->downloads_subtitle;
$description = $lang->downloads_projectdesc;
$fid = "1";

if($mybb->input['save']=="save")
{
	$filename = basename( $_FILES['downloadfile']['name']);
	$preview = basename( $_FILES['previewfile']['name']);
	if($filename=="")
	{	// we have to have a download file
		flash_message($lang->downloads_dlmngr_nofile, 'error');
		// set the values so the user doesn't have to retype them
		$title = $mybb->input['title'];
		$desc_short = $mybb->input['desc_short'];
		$description = $mybb->input['description'];
		$fid = $mybb->input['fid'];
		$grpflag = $mybb->input['grpflag'];
		$grplist = $mybb->input['grplist'];
	}
	else
	{
		// write stuff to database
		// DONE Add the ftp transfer here after checking the configuration
		// DONE fixed the download dir and moved it into the upload folder
		// set the filename to a non existing filename; done by adding a random number
		$num = rand(1000,9999);
		$errors = array();
		/**
		 * FTP transfer
		 */
		$filename_prefix = '';
		if($mybb->settings['downloads_ftptransfer'] == 1)
		{
			$filename_prefix = $mybb->settings['downloads_baseurl'];
		    $ftp_con = ftp_connect($mybb->settings['downloads_ftpserver']);
		    $ftp_loginresult = ftp_login($ftp_con, $mybb->settings['downloads_ftpuser'], $mybb->settings['downloads_ftppasswd']);
		    if($ftp_con && $ftp_loginresult)
		    {
		    	ftp_chdir($ftp_con, $mybb->settings['downloads_ftpserver']."/uploads/downloads/");
		        $ftp_uploadresult = ftp_put($ftp_con, $num.$filename, $_FILES['downloadfile']['tmp_name'], FTP_BINARY);
		        if($ftp_uploadresult)
		        {
		        	unset($errors);
		        }
		        else
		        {
		        	$errors[] = "error_upload";
		        }		
		        ftp_quit($ftp_con);
		    }
		    else
		    {
		    	$errors[] = "error_connection";
		    }
		}
		else
		{
			if(!move_uploaded_file($_FILES['downloadfile']['tmp_name'], MYBB_ROOT."/uploads/downloads/".$num.$filename))
			{
				$errors[] = "error_upload";
			}
			else
			{
				unset($errors);
			}
		}
		if(isset($errors))
		#if(!move_uploaded_file($_FILES['downloadfile']['tmp_name'], MYBB_ROOT."/uploads/downloads/".$num.$filename))
		{	// There was a problem with uploading the file
			// TODO set a special error message for each kind of errors
			flash_message($lang->downloads_dlmngr_problem_dl.$_FILES['downloadfile']['error'].'<br />'.$errors[0], 'error');
			// set the values so the user doesn't have to retype them
			$title = $mybb->input['title'];
			$desc_short = $mybb->input['desc_short'];
			$description = $mybb->input['description'];
			$fid = $mybb->input['fid'];
			$grpflag = $mybb->input['grpflag'];
			$grplist = $mybb->input['grplist'];
		}
		else
		{
			$errors = array();
			if($mybb->settings['downloads_ftptransfer'] == 1 && $preview != "")
			{
			    $ftp_con = ftp_connect($mybb->settings['downloads_ftpserver']);
		    	$ftp_loginresult = ftp_login($ftp_con, $mybb->settings['downloads_ftpuser'], $mybb->settings['downloads_ftppasswd']);
			    if($ftp_con && $ftp_loginresult)
			    {
			    	ftp_chdir($ftp_con, $mybb->settings['downloads_ftpserver']."/uploads/downloads/previews/");
			        $ftp_uploadresult = ftp_put($ftp_con, $num.$preview, $_FILES['previewfile']['tmp_name'], FTP_BINARY);
			        if($ftp_uploadresult)
			        {
			            unset($errors);
			        }
			        else
			        {
			            $errors[] = "error_upload";
			        }
			
			        ftp_quit($ftp_con);
			    }
			    else
			    {
			        $errors[] = "error_connection";
			    }
			}
			else if($preview != "")
			{
				if(move_uploaded_file($_FILES['previewfile']['tmp_name'], MYBB_ROOT."/uploads/downloads/previews/".$num.$preview))
				{
					unset($errors);
				}
				else
				{
					$errors[] = "error_upload";
				}					
			}
			else
			{
				unset($errors);
			}
			if(!isset($errors))
			#if($preview == "" || $previewuploadresult==true)
			#if($preview == "" || move_uploaded_file($_FILES['previewfile']['tmp_name'], MYBB_ROOT."/uploads/downloads/previews/".$num.$preview))
			{
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
				$insert_array = array(
					"title"		=> $db->escape_string($mybb->input['title']),
					"desc_short"	=> $db->escape_string($mybb->input['desc_short']),
					"description"	=> $db->escape_string($mybb->input['description']),
					"fid"		=> $mybb->input['fid'],
					"cat"		=> $mybb->input['cat'],
					"preview"		=> $filename_prefix.$num.$preview,
					"filename"	=> $filename_prefix.$num.$filename,
					"grpflag"		=> $mybb->input['grpflag'],
					"grplist"		=> $groups,
				);
				$db->insert_query("downloads", $insert_array);
				flash_message($filename.$lang->downloads_dlmngr_uploaded , 'success');
			}
			else
			{	// there was a problem uploading the preview
				// remove the download file
				@unlink(MYBB_ROOT."/uploads/downloads/".$num.$filename);
				// TODO set a special error message for each kind of errors
				flash_message($lang->downloads_dlmngr_problem_pr.$_FILES['previewfile']['error'].'<br />'.$errors[0], 'error');
				// set the values so the user doesn't have to retype them
				$title = $mybb->input['title'];
				$desc_short = $mybb->input['desc_short'];
				$description = $mybb->input['description'];
				$fid = $mybb->input['fid'];
				$grpflag = $mybb->input['grpflag'];
				$grplist = $mybb->input['grplist'];
			}
		}
	}
}


// start the page
$page->output_header($lang->downloads_dlmngr_addnew);

// create a new form for our new upload
$form = new Form("index.php?module=dlmngr/addnew", "post", "", 1);

// create the standard form container
$form_container = new FormContainer($lang->downloads_dlmngr_addnew);

// create the save flag
echo $form->generate_hidden_field("save", "save", array('id' => "save"))."\n";

// create text inputs
$form_container->output_row($lang->downloads_dlmngr_title, $lang->downloads_dlmngr_title_desc, $form->generate_text_box('title', $title, array('id' => 'title')), 'title');
$form_container->output_row($lang->downloads_dlmngr_desc_short, $lang->downloads_dlmngr_desc_short_desc, $form->generate_text_box('desc_short', $desc_short, array('id' => 'desc_short')), 'desc_short');
$form_container->output_row($lang->downloads_dlmngr_description, $lang->downloads_dlmngr_description_desc, $form->generate_text_area('description', $description, array('id' => 'description', 'style' =>'width: 100%;')), 'description');

$form_container->output_row($lang->downloads_dlmngr_fid, $lang->downloads_dlmngr_fid_desc, $form->generate_forum_select('fid', $dlitem['fid'], array('id' => 'fid', 'main_option' => $lang->none)), 'fid');

function getSubCatSelect($id = false)
{
	global $db, $mybb, $table, $form, $lang, $popup, $subdeepth;
	global $categories;
	// get the downloads
	if($id == false)
	{
		$query = $db->simple_select("downloads_catlist", "*", "parentID IS NULL", array("order by" => "catid"));
	}
	else
	{
		$query = $db->simple_select("downloads_catlist", "*", "parentID=$id");
		$subdeepth .= "&ndash; ";
	}
	while($cat = $db->fetch_array($query))
	{	// display each category
		$categories[$cat['catid']] = $subdeepth.$cat['title'];
		getSubCatSelect($cat['catid']);
	}
}
getSubCatSelect();
$form_container->output_row($lang->downloads_dlmngr_cat, $lang->downloads_dlmngr_cat_desc, $form->generate_select_box('cat', $categories, $dlitem['cat'], array('id' => 'cat')), 'cat');
$form_container->output_row($lang->downloads_dlmngr_grpflag, $lang->downloads_dlmngr_grpflag_desc, $form->generate_check_box('grpflag', '1', $lang->downloads_dlmngr_grpflag_desc_option ,array('checked' => $grpflag)) ,'grpflag');

$query = $db->simple_select("usergroups", "gid, title", "gid != '1'", array('order_by' => 'title'));
while($usergroup = $db->fetch_array($query))
{
	$options[$usergroup['gid']] = $usergroup['title'];
	$display_group_options[$usergroup['gid']] = $usergroup['title'];
}
$form_container->output_row($lang->downloads_dlmngr_grpflag, $lang->downloads_dlmngr_grpflag_desc, $form->generate_select_box('grplist[]', $options, $grplist, array('multiple' => true, 'size' => 5)),'grplist');
// create file upload boxes
$form_container->output_row($lang->downloads_dlmngr_preview, $lang->downloads_dlmngr_addpreview_desc, $form->generate_file_upload_box("previewfile", array('style' => 'width: 230px;')), 'preview');
$form_container->output_row($lang->downloads_dlmngr_filename, $lang->downloads_dlmngr_addfilename_desc, $form->generate_file_upload_box("downloadfile", array('style' => 'width: 230px;')), 'filename');

// close the form container
$form_container->end();

// create the save button
$buttons[] = $form->generate_submit_button($lang->downloads_dlmngr_save);

// wrap up the form
$form->output_submit_wrapper($buttons);
$form->end();

// end the page
$page->output_footer();

?>