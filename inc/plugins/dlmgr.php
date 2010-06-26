<?php
/**
 * Part of the "Downloads Manager" Plugin for MyBB
 * 
 * @access public
 * @author Jan Malte Gerth
 * @category MyBB Plugin
 * @copyright Copyright (c) 2009 Jan Malte Gerth (http://www.malte-gerth.de)
 * @license GPL3
 * @package Downloads Manager
 * @since Version 1.0
 * @version 3.0.0
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Let MyBB see the plugin
function dlmgr_info() {
	return array(
		"name" 		=> "Downloads Manager",
		"description"	=> "Adds a Download Section to your MyBB. You can easily add new downloads and manage them into categories.",
		"website"		=> "http://www.mybbcoder.info/",
		"author"		=> "Jan",
		"authorsite"	=> "http://www.malte-gerth.de/mybb.html",
		"version"		=> "3.0.0",
		'compatibility' => '14*,15*,16*',
		'guid'		 => '');
}

// Performs the actual installation of the templates & settings needed
function dlmgr_install() {
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	// Add database tables
	$db->write_query("CREATE TABLE ".TABLE_PREFIX."downloads (".
		"dlid INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL DEFAULT NULL, ".
		"uid INT(10) UNSIGNED NOT NULL DEFAULT 0, ".
		"title VARCHAR(120), ".
		"desc_short VARCHAR(255), ".
		"description TEXT DEFAULT NULL, ".
		"filename TEXT,".
		"preview TEXT, ".
		"cat INT(10) UNSIGNED NOT NULL DEFAULT 1, ".
		"fid INT(10) UNSIGNED NOT NULL DEFAULT 0, ".
		"fidflag INT(1) NOT NULL DEFAULT 0, ".
		"dlcount INT(10) UNSIGNED NOT NULL DEFAULT 0, ".
		"public INT(1) NOT NULL DEFAULT 0, ".
		"grpflag INT(1) NOT NULL DEFAULT 0, ".
		"grplist TEXT )");
	
	$db->write_query("CREATE TABLE ".TABLE_PREFIX."downloads_catlist (".
		"catid INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL DEFAULT NULL, ".
		"title VARCHAR(120), ".
		"parentID INT(10) UNSIGNED DEFAULT NULL".
		"grpflag INT(1) NOT NULL DEFAULT 0, ".
		"grplist TEXT)");

	// Add Settings
	$query = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query, "rows");
	
	$dlsettinggroup = array(
		'name'		=> 'dlmngr',
		'title'		=> 'Downloads Manager',
		'description'	=> 'These settings let you configure the Downloads Manager plugin.',
		'disporder'	=> $rows+1,
		'isdefault'	=> 0);
	$gid = $db->insert_query("settinggroups", $dlsettinggroup);
	
	$settings = array(
		"name"           => "downloads_itemsperpage",
		"title"          => "Projects per Page",
		"description"    => "This is how many projects you want on a page.",
		"optionscode"    => "text",
		"value"          => '10',
		"disporder"      => '1',
		"gid"            => intval($gid));
	$db->insert_query("settings", $settings);
	
	$settings = array(
		"name"           => "downloads_maxwidth_normalprev",
		"title"          => "Project Page Preview Width",
		"description"    => "How wide the previews are on the project description page.",
		"optionscode"    => "text",
		"value"          => '160',
		"disporder"      => '2',
		"gid"            => intval($gid));
	$db->insert_query("settings", $settings);
	
	$settings = array(
		"name"           => "downloads_maxwidth_listprev",
		"title"          => "List Preview Width",
		"description"    => "How wide the previews are on the project listings and search pages.",
		"optionscode"    => "text",
		"value"          => '160',
		"disporder"      => '3',
		"gid"            => intval($gid));
	$db->insert_query("settings", $settings);
	
	$settings = array(
		"name"           => "downloads_defaultprev",
		"title"          => "Default Preview File",
		"description"    => "Which file to show if none are given.",
		"optionscode"    => "text",
		"value"          => "nopreview.png",
		"disporder"      => '4',
		"gid"            => intval($gid));
	$db->insert_query("settings", $settings);
	
	$settings = array(
		"name"           => "downloads_isactive",
		"title"          => "Activated",
		"description"    => "Allows users to view the downloads page.",
		"optionscode"    => "yesno",
		"value"          => '1',
		"disporder"      => '5',
		"gid"            => intval($gid));
	$db->insert_query("settings", $settings);
	
	$setting_6 = array(
		"name"           => "downloads_ftptransfer",
		"title"          => "FTP Transfer",
		"description"    => "Allows to send the files to a several ftp server.",
		"optionscode"    => "yesno",
		"value"          => '0',
		"disporder"      => '6',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_6);
	
	$setting_7 = array(
		"name"           => "downloads_ftpserver",
		"title"          => "FTP Server",
		"description"    => "Which server the files should be transfered to.",
		"optionscode"    => "text",
		"value"          => "ftp.domain.com",
		"disporder"      => '7',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_7);
	
	$setting_8 = array(
		"name"           => "downloads_ftpuser",
		"title"          => "FTP User",
		"description"    => "Which user should be used to connect to the FTP server?",
		"optionscode"    => "text",
		"value"          => "username",
		"disporder"      => '8',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_8);
	
	$setting_9 = array(
		"name"           => "downloads_ftppasswd",
		"title"          => "FTP Password",
		"description"    => "Which password should be used to connect to the FTP server?",
		"optionscode"    => "text",
		"value"          => "******",
		"disporder"      => '9',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_9);
	
	$setting_10 = array(
		"name"           => "downloads_ftpdir",
		"title"          => "FTP Path",
		"description"    => "Which path should be used for FTP transfer?",
		"optionscode"    => "text",
		"value"          => "/mybb/",
		"disporder"      => '10',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_10);
	
	$setting_11 = array(
		"name"           => "downloads_baseurl",
		"title"          => "Base URL for external files",
		"description"    => "Which URL should be used for files which has been transfered to an external server. Must contain http:// or ftp:// at the beginning",
		"optionscode"    => "text",
		"value"          => "http://www.domain.com/",
		"disporder"      => '11',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_11);
	
	$setting_12 = array(
		"name"           => "downloads_userupload",
		"title"          => "User can add new Downloads",
		"description"    => "Should users are allowed to add Downloads.",
		"optionscode"    => "yesno",
		"value"          => "0",
		"disporder"      => '12',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_12);
	
	$setting_13 = array(
		"name"           => "downloads_usergrpupload",
		"title"          => "Allowed Usergroups",
		"description"    => "Which user groups should be allowed to add new Downloads. Seperate more than one Usergroup by \",\"",
		"optionscode"    => "text",
		"value"          => "2",
		"disporder"      => '13',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_13);
	
	$setting_14 = array(
		"name"           => "downloads_manageuploads",
		"title"          => "Checked Downloads added by Users",
		"description"    => "Should Downloads added by Users have to be checked by an other Usergroup?",
		"optionscode"    => "yesno",
		"value"          => "1",
		"disporder"      => '14',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_14);
	
	$setting_15 = array(
		"name"           => "downloads_usergrpmanage",
		"title"          => "Which Usergroups can check new Downloads",
		"description"    => "Which Usergroups can check new Downloads. Seperate more than one Usergroup by \",\"",
		"optionscode"    => "text",
		"value"          => "3,4,6",
		"disporder"      => '15',
		"gid"            => intval($gid));
	$db->insert_query("settings", $setting_15);
	
	// Update cached settings
	rebuild_settings();
	
	// Add default templates
	dlmgr_addTemplates();
	
	// Add sample downloads
	dlmgr_addSamples();
}

// This function tells MyBB whether the plugin is installed or not
function dlmgr_is_installed() {
	global $db;
	if($db->table_exists("downloads")) {
 		return true;
	}
	return false;
}

// perform any actions to activate plugin
// in this case, activate the downloads page
function dlmgr_activate()
{
	global $db;
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	dlmgr_addTemplates();
	
	dlmgr_updateTables();
	
	// to do: check settings
	
	$setting_1 = array(
		"value"          => '1',
	);
	
	find_replace_templatesets("header", '#toplinks_help}</a></li>#', "$0\n<li id=\"download\"><a href=\"{\$mybb->settings['bburl']}/downloads.php\"><img src=\"{\$mybb->settings[bburl]}/images/dlmngr/icon.png\" border=\"0\" alt=\"\" />Downloads</a></li>");

	$db->update_query("settings", $setting_1, "name='downloads_isactive'");
	rebuild_settings();
}

function dlmgr_deactivate()
{
	global $db;
	require MYBB_ROOT."/inc/adminfunctions_templates.php";
	$setting_1 = array(
		"value"          => '0',
	);
	find_replace_templatesets("header", '#(\n?)<li id="download">(.*)</li>#', '', 0);
	$db->update_query("settings", $setting_1, "name='downloads_isactive'");
	rebuild_settings();
}

// cleans up everything our plugin installed
function dlmgr_uninstall()
{
	global $db;
	$db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."downloads");
	$db->write_query("DROP TABLE IF EXISTS ".TABLE_PREFIX."downloads_catlist");
	$db->delete_query("settings","name LIKE 'downloads%'");
	$db->delete_query("settinggroups","name='dlmngr'");
	rebuild_settings();
	// remove all download files
	chdir(MYBB_ROOT."/uploads/downloads/");
	foreach (glob("*") as $filename) {
		#echo "$filename size " . filesize($filename) . "\n";
		if($filename != '1000sample.txt')
		{
			@unlink($filename);
		}
	}
	chdir(MYBB_ROOT."/uploads/downloads/previews/");
	// remove all preview files
	foreach (glob("*") as $filename) {
		#echo "$filename size " . filesize($filename) . "\n";
		if($filename != 'nopreview.png')
		{
			@unlink($filename);
		}
	}
	chdir(MYBB_ROOT);
	$db->delete_query("templates","title LIKE 'downloads%'");
}

// Update the tables
function dlmgr_updateTables()
{
	global $db;
	if(!$db->table_exists("downloads_catlist"))
	{
		$db->write_query("CREATE TABLE ".TABLE_PREFIX."downloads_catlist (".
			"catid INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL DEFAULT NULL, ".
			"title VARCHAR(120), ".
			"parentID INT(10) UNSIGNED DEFAULT NULL )");
	}
	else if(!$db->field_exists('parentID', "downloads_catlist"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads_catlist ADD COLUMN parentID INT(10) UNSIGNED DEFAULT NULL");
	}
	
	$query = $db->query("describe ".TABLE_PREFIX."downloads");
	while($row = $db->fetch_array($query))
		$downloads[$row['Field']] = $row;
	
	if($downloads['dlid']['Type'] != "int(10) unsigned")
	{
		// set type
		$columnDef = "dlid INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL DEFAULT NULL";
		
		$modadd = "MODIFY";
		if($downloads['dlid']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['uid']['Type'] != "int(10) unsigned")
	{
		// set type
		$columnDef = "uid INT(10) UNSIGNED NOT NULL DEFAULT 0";
		
		$modadd = "MODIFY";
		if($downloads['uid']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['title']['Type'] != "varchar(120)")
	{
		// set type
		$columnDef = "title VARCHAR(120)";
		
		$modadd = "MODIFY";
		if($downloads['title']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['desc_short']['Type'] != "varchar(255)")
	{
		// set type
		$columnDef = "desc_short VARCHAR(255)";
		
		$modadd = "MODIFY";
		if($downloads['desc_short']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['description']['Type'] != "text")
	{
		// set type
		$columnDef = "description TEXT DEFAULT NULL";
		
		$modadd = "MODIFY";
		if($downloads['description']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['preview']['Type'] != "text")
	{
		// set type
		$columnDef = "preview TEXT";
		
		$modadd = "MODIFY";
		if($downloads['preview']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['fid']['Type'] != "int(10) unsigned")
	{
		// set type
		$columnDef = "fid INT(10) UNSIGNED NOT NULL DEFAULT 0";
		
		$modadd = "MODIFY";
		if($downloads['fid']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['filename']['Type'] != "text")
	{
		// set type
		$columnDef = "filename TEXT";
		
		$modadd = "MODIFY";
		if($downloads['filename']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['cat']['Type'] != "int(10) unsigned")
	{
		// set type
		$columnDef = "cat INT(10) UNSIGNED NOT NULL DEFAULT 1";
		
		$modadd = "MODIFY";
		if($downloads['cat']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['dlcount']['Type'] != "int(10) unsigned")
	{
		// set type
		$columnDef = "dlcount INT(10) UNSIGNED NOT NULL DEFAULT 0";
		
		$modadd = "MODIFY";
		if($downloads['dlcount']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['fidflag']['Type'] != "int(1)")
	{
		// set type
		$columnDef = "fidflag INT(1) NOT NULL DEFAULT 0";
		
		$modadd = "MODIFY";
		if($downloads['fidflag']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['grpflag']['Type'] != "int(1)")
	{
		// set type
		$columnDef = "grpflag INT(1) NOT NULL DEFAULT 0";
		
		$modadd = "MODIFY";
		if($downloads['grpflag']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['public']['Type'] != "int(1)")
	{
		// set type
		$columnDef = "public INT(1) NOT NULL DEFAULT 0";
		
		$modadd = "MODIFY";
		if($downloads['public']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
	
	if($downloads['grplist']['Type'] != "text")
	{
		// set type
		$columnDef = "grplist TEXT";
		
		$modadd = "MODIFY";
		if($downloads['grplist']['Type'] == "")
			$modadd = "ADD";
		
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."downloads ".$modadd." COLUMN ".$columnDef);
	}
}

// Add sample items
function dlmgr_addSamples()
{
	global $db;
	$sampleDL = array(
		// A sample download project
		"uid"		=> '1',
		"title"		=> "Sample Download",
		"desc_short"	=> "A sample local file for download",
		"description"	=> $db->escape_string("[b][i]This is the project's description.[/i][/b] This is where you can describe the download file."),
		"preview"		=> "nopreview.png",
		// This is the forum ID for the project's discussion forum
		"fid"		=> '1',
		"filename"	=> "1000sample.txt",
		"cat"		=> '2',
		"dlcount"		=> '0',
		// 0 = a forum, 1 = a thread
		"fidflag"		=> '0',
		// 0 = blacklist, 1 = whitelist
		// ATTENTION
		// not yet omplemented. until now you can set groups which have the permission to download the file
		"grpflag"		=> '0',
		// if blacklist then these groups can't download
		//  else only these groups can download
		"grplist"		=> '',
		"public"		=> 1,
	);
	// Add the sample download to our custom table
	$db->insert_query("downloads", $sampleDL);
	
	// Add sample category
	$sampleCat = array(
		// A sample download category
		"title"		=> "General"
	);	
	$db->insert_query("downloads_catlist", $sampleCat);
	
	$sampleCat = array(
		// A sample download category
		"title"		=> "Subcategory",
		"parentID"	=> '1'
	);	
	$db->insert_query("downloads_catlist", $sampleCat);
	
	$sampleCat = array(
		// A sample download category
		"title"		=> "Sub Subcategory",
		"parentID"	=> '2'
	);	
	$db->insert_query("downloads_catlist", $sampleCat);
}

// Installs new templates
function dlmgr_addTemplates()
{
	global $db;
	
	// Old templates that aren't used anymore
	$db->delete_query("templates","title='downloads_listbit'");
	
	$query = $db->simple_select("templates", "version, title", "title LIKE 'downloads%'");
	while($temp = $db->fetch_array($query))
		$template[$temp['title']] = $temp;
		
	if($template['downloads']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads",
			"template"	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->downloads}</title>
{$headerinclude}
</head>
<body>
{$header}
<table>
<tr>
<td width="1%" style="min-width: 150px; vertical-align: top;">
{$sidecat}
</td>
<td style="vertical-align: top;">
{$cat_box}
{$projlist}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_categories']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_categories'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_categories",
			"template"	=> $db->escape_string('<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->downloads_categories}</strong></td>
</tr>
{$catlist}
</table>
<br />'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_categories_listbit']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_categories_listbit'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_categories_listbit",
			"template"	=> $db->escape_string('<tr class="{$altbg}">
<td>
<a href="downloads.php?cat={$catid}">{$catname}</a>
</td>
</tr>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_project']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_project'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_project",
			"template"	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$projname}</title>
{$headerinclude}
</head>
<body>
{$header}
<table>
<tr>
<td width="1%" style="min-width: 150px; vertical-align: top;">
{$sidecat}
</td>
<td style="vertical-align: top;">
{$dlerror}
<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="3"><div><strong>{$projname}</strong></div></td>
</tr>
<tr>
<td class="tcat" width="1%">
{$lang->downloads_preview}
</td>
<td class="tcat">
{$lang->downloads_info}
</td>
</tr>
<tr>
<td class="trow2" style="overflow: auto; vertical-align: top;">
{$previews}
</td>
<td class="trow1"  style="vertical-align: top;">
{$description}
</td>
</tr>
<tr>
<td colspan="2" class="tfoot">
<div class="float_right">
<strong>{$dllink}</strong>
</div>
<div>
<strong><a href="{$discusslink}">{$lang->downloads_discuss}</a></strong>
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_project_dlerror']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_project_dlerror'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_project_dlerror",
			"template"	=> $db->escape_string('<div class="error">
<p>
<em>{$error}</em>
<ul>{$grouplist}</ul>
{$lang->downloads_yourgroup} {$mybb->usergroup[\'title\']}
</p>
</div>
<br />'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_projlist']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_projlist'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_projlist",
			"template"	=> $db->escape_string('{$multipage}
<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="3"><strong>{$lang->downloads}</strong></td>
</tr>
<tr>
<td class="tcat" width="1%">
{$lang->downloads_preview}
</td>
<td class="tcat">
{$lang->downloads_info}
</td>
<td class="tcat" width="10%">
{$lang->downloads_count}
</td>
</tr>
{$project_list}
</table>
{$multipage}'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_projlist_empty']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_projlist_empty'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_projlist_empty",
			"template"	=> $db->escape_string('<tr>
<td colspan="3" class="trow1" style="text-align: center; vertical-align: middle;">
{$lang->downloads_noprojects}
</td>
</tr>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_projlist_listbit']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_projlist_listbit'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_projlist_listbit",
			"template"	=> $db->escape_string('<tr>
<td class="{$altbg}" style="text-align: center; vertical-align: middle;">
<img src="{$preview}" style="max-width: {$maxwidth};" alt="{$lang->downloads_preview_for}{$title}" title="{$lang->downloads_preview_for}{$title}" />
</td>
<td class="{$altbg}" style="vertical-align: middle;">
<a href="downloads.php?action=view&amp;dlid={$dlid}">{$title}</a><br />
<span class="smalltext">{$subtitle}</span>
</td>
<td class="{$altbg}" style="text-align: center; vertical-align: middle;">
{$dlcount}
</td>
</tr>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_search']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_search'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_search",
			"template"	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->downloads}</title>
{$headerinclude}
</head>
<body>
{$header}
<table>
<tr>
<td width="1%" style="min-width: 150px; vertical-align: top;">
{$sidecat}
</td>
<td style="vertical-align: top;">
<form method="post" action="downloads.php?action=search">
<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->downloads_search}</strong></td>
</tr>
<tr>
<td class="trow1">
<table>
<tr>
<td style="vertical-align: top;">
<strong>{$lang->downloads_categories}</strong><br />
{$searchcatlist}
</td>
<td style="vertical-align: top; padding-left: 4px;">
<strong>{$lang->downloads_title}</strong><br />
<input type="text" class="textbox" name="keywords" size="35" maxlength="250" value="{$keywords}"/><br />
<span class="smalltext">
<input type="radio" class="radio" name="titlesOnly" value="false" checked="checked" />{$lang->downloads_search_subtitle}<br />
<input type="radio" class="radio" name="titlesOnly" value="true" />{$lang->downloads_search_title}</span><br />
</td>
</tr>
</table>
</td>
</tr>
</table>
<div align="center"><br /><input type="submit" class="button" value="{$lang->downloads_dosearch}" /></div>
</form>
<br />
{$results}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_addform']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_addform'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_addform",
			"template"	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->downloads}</title>
{$headerinclude}
</head>
<body>
{$header}
<table>
<tr>
<td width="1%" style="min-width: 150px; vertical-align: top;">
{$sidecat}
</td>
<td style="vertical-align: top;">
<form method="post" action="downloads.php?action=addnew" enctype="multipart/form-data">
<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><strong>{$lang->downloads_addnew}</strong></td>
</tr>
<tr>
<tr>
<td>
{$message}
</td>
</tr>
<td class="trow1">
<table>
<tr>
<td style="vertical-align: top;">
<strong>{$lang->downloads_categories}</strong><br />
{$searchcatlist}
</td>
<td style="vertical-align: top; padding-left: 4px;">
<strong>{$lang->downloads_title}</strong><br />
<input type="text" class="textbox" name="title" size="35" maxlength="250" value=""/><br />
<strong>{$lang->downloads_desc_short}</strong><br />
<input type="text" class="textbox" name="desc_short" size="35" maxlength="250" value=""/><br />
<strong>{$lang->downloads_desc}</strong><br />
<textarea class="textbox" name="description" cols="50" rows="10"></textarea><br />
<strong>{$lang->downloads_preview}</strong><br />
<input type="file" class="file" name="previewfile" /><br />
<strong>{$lang->downloads_download}</strong><br />
<input type="file" class="file" name="downloadfile" /><br />
</td>
</tr>
</table>
</td>
</tr>
</table>
<div align="center"><br /><input type="submit" class="button" value="{$lang->downloads_addnew}" /></div>
</form>
<br />
{$results}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_search_resultbox']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_search_resultbox'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_search_resultbox",
			"template"	=> $db->escape_string('{$multipage}
<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="3"><strong>{$lang->downloads}</strong></td>
</tr>
<tr>
<td class="tcat" width="1%">
{$lang->downloads_preview}
</td>
<td class="tcat">
{$lang->downloads_info}
</td>
<td class="tcat" width="10%">
{$lang->downloads_count}
</td>
</tr>
{$project_list}
</table>
{$multipage}'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_sidecat']['version'] != "2.1")
	{
		$db->delete_query("templates","title='downloads_sidecat'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_sidecat",
			"template"	=> $db->escape_string('<table cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" width="100%">
<tr>
<td class="thead"><strong>{$lang->downloads_categories}</strong></td>
</tr>
{$sidecat_list}
<tr>
<td class="tfoot">
<strong><a href="downloads.php?action=search">{$lang->downloads_search}</a></strong>
</td>
</tr>
<tr>
<td class="tfoot">
<strong><a href="downloads.php?action=showadd">{$lang->downloads_addnew}</a></strong>
</td>
</tr>
</table>'),
			"sid"		=> "-1",
			"version"		=> "2.1",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
	
	if($template['downloads_sidecat_listbit']['version'] != "2.0")
	{
		$db->delete_query("templates","title='downloads_sidecat_listbit'");
		$temp = array(
			"sid"		=> "NULL",
			"title"		=> "downloads_sidecat_listbit",
			"template"	=> $db->escape_string('<tr class="$altbg">
<td style="white-space: nowrap;">
<a href="downloads.php?cat={$catid}">{$catname}</a>
</td>
</tr>'),
			"sid"		=> "-1",
			"version"		=> "2.0",	
			"status"		=> "0",
			"dateline"        => time()
		);
		$db->insert_query("templates", $temp);
	}
}
?>