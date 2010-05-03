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
 * @version $Id: module_meta.php 210 2009-03-28 16:33:51Z JanMalte $
 * 
 * DONE check and improve security
 * DONE optomize functions
 * DONE clarify variable and function names
 * DONE comment code better
 * DONE old version of the file; jan malte gerth 2009-03-17
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// This function is needed
function dlmngr_meta()
{
	// get access to everything we want
	global $page, $lang, $plugins, $db;
	
	// load language strings
	$lang->load("downloads");

	// this is a list of sub menus
	$sub_menu = array();
	$sub_menu['10'] = array("id" => "addnew", "title" => $lang->downloads_dlmngr_addnew, "link" => "index.php?module=dlmngr/addnew");
	$sub_menu['20'] = array("id" => "manage", "title" => $lang->downloads_dlmngr_manage, "link" => "index.php?module=dlmngr/manage");
	$sub_menu['30'] = array("id" => "cats", "title" => $lang->downloads_dlmngr_cats, "link" => "index.php?module=dlmngr/cats");
	// get the link for the plugin settings
	$gid = $db->fetch_field($db->simple_select("settings", "gid", "name='downloads_isactive'"), "gid");
	$sub_menu['40'] = array("id" => "settings", "title" => $lang->downloads_dlmngr_settings, "link" => "index.php?module=config/settings&action=change&gid=$gid");
	
	// custom plugin hooks!
	$plugins->run_hooks_by_ref("admin_dlmngr_menu", $sub_menu);

	if($db->table_exists("downloads"))
	{	// plugin installed, so show this module's link
		// add_menu_item(title, name, link, display order, submenus)
		$page->add_menu_item($lang->downloads_dlmngr, "dlmngr", "index.php?module=dlmngr", 100, $sub_menu);
		return true;
	}
	// I assume returning false means "don't do anything"
	// no adverse effects so far.
	return false;
}

// This function is needed for handling the called action of this module
function dlmngr_action_handler($action)
{
	// get access to everything we want
	global $page, $plugins;
	
	// our module's name
	$page->active_module = "dlmngr";
	
	// the available actions and their pages
	$actions = array(
		'addnew' => array('active' => 'addnew', 'file' => 'addnew.php'),
		'manage' => array('active' => 'manage', 'file' => 'manage.php'),
		'cats' => array('active' => 'cats', 'file' => 'categories.php')
	);
	
	// more custom plugin hooks!
	$plugins->run_hooks_by_ref("admin_dlmngr_action_handler", $actions);
	
	if(isset($actions[$action]))
	{	// set the action and return the page
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{	// return the default page
		$page->active_action = "manage";
		return "manage.php";
	}
}

?>