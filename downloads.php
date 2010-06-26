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
 * @version $Id: downloads.php 211 2009-04-18 18:30:05Z JanMalte $
 * 
 * DONE optimize displaying of the categories
 * TODO add a message for external downloads before redirect
 * TODO check and improve security
 * TODO optomize functions
 * TODO clarify variable and function names
 * TODO comment code better
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'downloads.php');

// A list of the templates used by this page
$templatelist  = "downloads,";
$templatelist .= "multipage_prevpage,multipage_nextpage,multipage_page_current,multipage_page,multipage_start,multipage_end,multipage";

// Global functions & whatnot
require_once "./global.php";

// MyCode Formating stuff
require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;

// Load global language phrases
$lang->load("downloads");

#require_once('/usr/share/php/FirePHPCore/FirePHP.class.php');
#require_once('/usr/share/php/FirePHPCore/fb.php');
#$firephp = FirePHP::getInstance(true);

// Do nav breadcrumb stuff
add_breadcrumb($lang->nav_downloads, "downloads.php");

if($mybb->settings['downloads_isactive'] != '1')
{
// We're not active, so let's throw a fit. At least it'll look something like the rest of the site...
	$downloads = "<html>
<head>
<title>{$mybb->settings['bbname']} - Oops!</title>
{$headerinclude}
</head>
<body>
{$header}
<table cellspacing=\"{$theme['borderwidth']}\" cellpadding=\"{$theme['tablespace']}\" class=\"tborder\">
<tr>
<td class=\"thead\"><strong>Oops!</strong></td>
</tr>
<tr>
<td class=\"trow1\">
This page hasn't been activated yet!
</td>
</tr>
</table>
{$footer}
</body>
</html>";
}
else
{	// We're active! Make some global work for all download actions and pages
	// We will later split up into several actions

	// Load our class and create a new instance
	$Download = new dlmngr();

	// From this point forward, when I say cat, I mean category,
	// not a feline. I was just too lazy to replace them all.
	// I may make jokes, though...

	if($mybb->input["cat"] == "") // or invalid cat
	{	// Set $currentcat to the 'all cats' value
		$currentcat = -1;
	}
	else
	{	// it's a valid cat, so set it to the current cat.
		$currentcat = intval($mybb->input["cat"]);
	}

	// Build the category list
	$query = $db->simple_select("downloads_catlist", "*", "1=1 ORDER BY catid ASC");
	while($catsql = $db->fetch_array($query))
	{
		// $category[parent] = all direct children of parent
		$category[$catsql['parentID']][] = $catsql;
		
		// make lookup easier later
		$categories[$catsql['catid']] = $catsql;
	}

	// set up the alternating rows variables for css classes
	$alt = 1;
	$alt_base = "trow";

	if($mybb->input["action"] == "addnew")
	{
		AddUserDownload();
	}
	else if($mybb->input["action"] == "showadd")
	{
		ShowAddForm();
	}
	else if(($mybb->input["action"] == "manage") OR ($mybb->input["action"] == "reject")
			OR ($mybb->input["action"] == "accept") OR ($mybb->input["action"] == "delete"))
	{
		switch($mybb->input["action"])
		{
			case 'reject':
				$Download->RejectDownload($mybb->input["dlid"]);
				break;
			case 'accept':
				$Download->AcceptDownload($mybb->input["dlid"]);
				break;
			case 'delete':
				$Download->DeleteDownload($mybb->input["dlid"]);
				break;
			default:
				$Download->ManageDownloads();
		}
	}
	else if($mybb->input["action"] == "search")
	{	// The user searched for something
		$keywords = $mybb->input["keywords"];

		if($keywords != "")
		{	// the user really did search for something
			// build 'where' part of query
			
			$searchterm = explode(' ', $db->escape_string($keywords));
			foreach($searchterm as $term)
				$search .= "'%{$term}%' || title LIKE ";

			$search .= "''";

			$where = "title LIKE {$search}";
			if($mybb->input["titlesOnly"] == "false")
			{
				foreach($searchterm as $term)
					$search .= "'%{$term}%' || desc_short LIKE ";

				$search .= "''";
				$where .= " || desc_short LIKE {$search}";
			}
			$where .= " AND `public` =1";

			$query = $db->simple_select("downloads", "COUNT(dlid) AS projcount", $where);
			$projcount = $db->fetch_field($query, "projcount");

			if($projcount > 0)
			{
				// get the page #
				$page = $mybb->input["page"];
				if($page == "")
				{	// set default page
					$page = 1;
				}
				else
				{	// set actual page
					$page = intval($page);
				}

				$ITEMS_PER_PAGE = $mybb->settings['downloads_itemsperpage'];

				// set up multiple page stuff
				$multipage = multipage($projcount, $ITEMS_PER_PAGE, $page, "downloads.php?action=search");

				// get the right page
				if($currentcat != -1)
				{
					$query = $db->simple_select("downloads", "dlid, title, desc_short, preview, cat, dlcount, public", $where." LIMIT ".$ITEMS_PER_PAGE *($page-1).", ".$ITEMS_PER_PAGE );
				}
				else
				{
					$query = $db->simple_select("downloads", "dlid, title, desc_short, preview, cat, dlcount, public", $where." LIMIT ".$ITEMS_PER_PAGE *($page-1).", ".$ITEMS_PER_PAGE );
				}
				
				$alt = 0;
				
				$maxwidth = $mybb->settings['downloads_maxwidth_listprev']."px";
				
				while($dlitem = $db->fetch_array($query))
				{	// for each item, get the bg, set the variable, and display it
				
					// 'trow1' for odd items, & 'trow2' for even ones
					$altbg = $alt_base.($alt%2+1);
					
					// next item index
					$alt++;
					
					// get the item's title
					$title = $dlitem['title'];
					if($dlitem['preview']=="")
					{	// get the default preview
						$preview = "/uploads/downloads/previews/".$mybb->settings['downloads_defaultprev'];
					}
					else
					{	// get the actual preview
						$prvlist = explode(",", $dlitem['preview']);
						$preview = "/uploads/downloads/previews/".$prvlist[0];
					}
					// get the id number
					$dlid = $dlitem['dlid'];
					
					// get the subtitle
					$subtitle = $dlitem['desc_short'];
					
					$dlcount = $dlitem['dlcount'];
					
					// add it to the list using the template
					eval("\$project_list .= \"".$templates->get("downloads_projlist_listbit", 1, 0)."\";");
				}
			}
			else
			{	// Didn't find any projects, so tell the user
				eval("\$project_list = \"".$templates->get("downloads_projlist_empty")."\";");
			}

			eval("\$results = \"".$templates->get("downloads_search_resultbox")."\";");
		}
		
		// generate the select box for the user to
		// choose which cats to search
		$searchcatlist = '<select name="cats[]" size="10" multiple="multiple">
		<option value="-1" selected="selected">' . $lang->downloads_all . '</option>';
		
		// list categories
		$cats = listCats2('', "", $category);
		foreach($cats as $cat)
		{
			$searchcatlist .= "<option value=\"{$cat['catid']}\">{$cat['title']}</option>";
		}
		
		$searchcatlist .= "</select>";
		
		$keywords = htmlspecialchars($mybb->input["keywords"]);
		$templateName = "downloads_search";
	}
	else if($mybb->input["action"] == "download")
	{
		$download = $Download->CheckDownload($mybb->input["dlid"]);
		if($download != false)
		{
			$Download->SendDownloadFile($download);
			exit();
		}
		else
			header("Location: downloads.php");
		
	}
	else if($mybb->input["action"] == "view")
	{
		// invalid request
		if(!((string)($mybb->input["dlid"]) === (string)(int)($mybb->input["dlid"])))
		{
			header("Location: downloads.php");
			exit();
		}
		
		$dlid = $mybb->input["dlid"];
		$dlitem = $Download->GetDownloadInfo($dlid);
		/*
		$query = $db->simple_select("downloads", "*", "dlid={$dlid} AND `public` =1");
		
		if(!($dlitem = $db->fetch_array($query)))
		{
			header("Location: downloads.php");
			exit();
		}
		
		if($dlitem['grpflag']=="1")
			$cantDL = true;
		else
			$cantDL = false;
		
		if($dlitem['grplist'] != "")
		{
			$grplist = explode(",", $dlitem['grplist']);

			$usergroup = $mybb->usergroup['gid'];
			if(!(($dlitem['grpflag']=="1") ^ in_array($usergroup, $grplist)))
			{
				$cantDL = false;
			}
		}
		*/
		$cantDL = $Download->CheckPermissions($dlitem);
		
		$firephp->log($mybb->user, 'User');
		$firephp->log($dlitem, 'Download');
		$firephp->log($cantDL, 'Access');
		
		if(!is_file(MYBB_ROOT."/uploads/downloads/".$dlitem['filename']))
		{
			$cantDL = true;
			$file_exist = false;
		}
		else
		{
			$file_exist = true;
		}
		
		// TODO clean up error messages and their handling
		if($cantDL)
		{
			if($file_exist == false)
				$error = $lang->downloads_cant_find_file . "<br />";
			else if($dlitem['grpflag']=="0")
				$error = $lang->downloads_cant_blist . "<br />";
			else
				$error = $lang->downloads_cant_wlist . "<br />";
				
			if($dlitem['grplist'] != "")
			{
				$query = $db->simple_select("usergroups", "title", "gid IN (".$dlitem['grplist'].") ORDER BY gid ASC");
				while($grp = $db->fetch_array($query))
					$grouplist .= "<li>".$grp['title']."</li>";
			}
			else 
				$grouplist = "<li>N/A</li>";

			eval("\$dlerror = \"".$templates->get("downloads_project_dlerror")."\";");
		}
		
		$currentcat = $dlitem['cat'];
		
		$projname = $dlitem['title'];
		
		$maxwidth = $mybb->settings['downloads_maxwidth_normalprev']."px";
		
		if($dlitem['preview']=="")
		{	// get the default preview
			$previews = $previews .= "<img src=\"/uploads/downloads/previews/".$mybb->settings['downloads_defaultprev']."\" style=\"max-width: {$maxwidth};\" alt=\"{$lang->downloads_preview_for}{$projname}\" title=\"{$lang->downloads_preview_for}{$projname}\" /><br />\n";
		}
		else
		{
			$prvlist = explode(",", $dlitem['preview']);
			foreach($prvlist as $prev)
				$previews .= "<img src=\"/uploads/downloads/previews/".$prev."\" style=\"max-width: {$maxwidth};\" alt=\"{$lang->downloads_preview_for}{$projname}\" title=\"{$lang->downloads_preview_for}{$projname}\" /><br />\n";
		}
		// Set up MyCode parser options
		$parser_options['allow_html'] = 0;
		$parser_options['allow_mycode'] = 1;
		$parser_options['allow_smilies'] = 1;
		$parser_options['allow_imgcode'] = 1;
		$parser_options['filter_badwords'] = 1;
		
		// parse the description
		$description = $parser->parse_message($dlitem['description'], $parser_options);
		
		if($dlitem['fidflag'] == "1")
		{
			$discusslink = "showthread.php?tid=".$dlitem['fid'];
		}
		else
		{
			$discusslink = "forumdisplay.php?fid=".$dlitem['fid'];
		}
		
		if(($cantDL) || ($file_exist == false))
			$dllink = $lang->downloads_cant_dlnow;
		 else
			$dllink = "<a href=\"downloads.php?action=download&amp;dlid=".$dlid."\">".$lang->downloads_dlnow."</a>";
		
		$templateName = "downloads_project";
	}
	else
	{
		$cat_box = "";

		if(is_array($category[$currentcat]))
		{
			$alt = 0;
			foreach($category[$currentcat] as $cat)
			{
				$catname = $cat['title'];
				$catid = $cat['catid'];
				$altbg = $alt_base.($alt%2+1);
				eval("\$catlist .= \"".$templates->get("downloads_categories_listbit")."\";");
				$alt++;
			}
			eval("\$cat_box = \"".$templates->get("downloads_categories")."\";");
		}

		// Build the project list for this category
		// get the number of downloads in this category
		if($currentcat != -1)
		{
			$query = $db->simple_select("downloads", "COUNT(dlid) AS projcount", "cat={$currentcat} AND `public` =1");
		}
		else
		{
			$query = $db->simple_select("downloads", "COUNT(dlid) AS projcount", "`public` =1");
		}
		$projcount = $db->fetch_field($query, "projcount");

		if($projcount > 0)
		{
			// get the page #
			$page = $mybb->input["page"];
			if($page == "")
			{	// set default page
				$page = 1;
			}
			else
			{	// set actual page
				$page = intval($page);
			}

			$ITEMS_PER_PAGE = $mybb->settings['downloads_itemsperpage'];

			// set up multiple page stuff
			$multipage = multipage($projcount, $ITEMS_PER_PAGE, $page, "downloads.php?");

			// multipage() adds '&page=x', but we don't want the '&', so remove it
			$multipage = str_replace("&amp;", "", $multipage);

			// get the right page
			if($currentcat != -1)
			{
				$query = $db->simple_select("downloads", "dlid, title, desc_short, preview, cat, dlcount", "cat={$currentcat} AND `public` =1 ORDER BY dlcount DESC LIMIT ".$ITEMS_PER_PAGE *($page-1).", ".$ITEMS_PER_PAGE );
			}
			else
			{
				$query = $db->simple_select("downloads", "dlid, title, desc_short, preview, cat, dlcount", "1=1 AND `public` =1 ORDER BY dlcount DESC LIMIT ".$ITEMS_PER_PAGE *($page-1).", ".$ITEMS_PER_PAGE );
			}
			
			$alt = 0;
			
			$maxwidth = $mybb->settings['downloads_maxwidth_listprev']."px";
			
			while($dlitem = $db->fetch_array($query))
			{	// for each item, get the bg, set the variable, and display it
			
				// 'trow1' for odd items, & 'trow2' for even ones
				$altbg = $alt_base.($alt%2+1);
				
				// next item index
				$alt++;
				
				// get the item's title
				$title = $dlitem['title'];
				if($dlitem['preview']=="")
				{	// get the default preview
					$preview = "/uploads/downloads/previews/".$mybb->settings['downloads_defaultprev'];
				}
				else
				{	// get the actual preview
					#die();
					$prvlist = explode(",", $dlitem['preview']);
					$preview = "/uploads/downloads/previews/".$prvlist[0];
				}
				// get the id number
				$dlid = $dlitem['dlid'];
				
				// get the subtitle
				$subtitle = $dlitem['desc_short'];
				
				$dlcount = $dlitem['dlcount'];
				
				// add it to the list using the template
				eval("\$project_list .= \"".$templates->get("downloads_projlist_listbit", 1, 0)."\";");
				
			}
		}
		else
		{
			eval("\$project_list = \"".$templates->get("downloads_projlist_empty")."\";");
		}

		eval("\$projlist = \"".$templates->get("downloads_projlist")."\";");

		$templateName = "downloads";
	}

	if($currentcat == -1)
	{
		foreach( $category[''] as $cat )
		{
			$catname = $cat['title'];
			$catid = $cat['catid'];
			if($catid != $currentcat)
				$altbg = $alt_base.($alt%2+1);
			else
				$altbg = $alt_base."_selected";
			eval("\$sidecat_list .= \"".$templates->get("downloads_sidecat_listbit")."\";");
			$alt++;
		}
		// add the "all downloads" item
		$catname = $lang->downloads_all;
		$catid = -1;
		$altbg = $alt_base."_selected";
		eval("\$sidecat_list .= \"".$templates->get("downloads_sidecat_listbit")."\";");
	}
	else
	{
		$parent = $categories[$currentcat]['parentID'];
		$heirarchy = array(null);
		while($parent != "")
		{
			array_unshift($heirarchy, $parent);
			$parent = $categories[$parent]['parentID'];
		}
		
		$sidecat_list = listCats($currentcat, '', $heirarchy, "", $alt, $category);

		$alt++;
		// add the "all downloads" item
		$catname = $lang->downloads_all;
		$catid = -1;
		$altbg = $alt_base.($alt%2+1);
		eval("\$sidecat_list .= \"".$templates->get("downloads_sidecat_listbit")."\";");
	}
	//  Build container
	eval("\$sidecat = \"".$templates->get("downloads_sidecat")."\";");

	if(($mybb->input["action"] == "view") or ($mybb->input["action"] == "download"))
		add_breadcrumb($projname, "downloads.php?action=view&amp;dlid={$dlid}");

	eval("\$downloads = \"".$templates->get($templateName)."\";");
}

output_page($downloads);

#$Download->GetUserDownloads(1);
#echo '<pre>';
#print_r($Download);

/**
 * Class for downloads
 */
class dlmngr
{
	public $total_downloads;
	
	public $download_info;
	
	public $dlid;
	
	/**
	 * count downloads
	 */
	function CountDownloads()
	{
		global $db;
		
		$query = $db->simple_select("downloads", "COUNT(dlid) AS projcount", $where);
		return $this->total_downloads = $db->fetch_field($query, "projcount");
	}
	
	/**
	 * function to check the permissions of downloading the file
	 */
	function CheckPermissions($dlitem = false)
	{
		global $mybb;
		
		#if($dlitem['public']!="1" && $mybb->usergroup['gid'] != 4)
		#	return false;
		
		if($dlitem['grpflag']==0)
			return false;
		
		if($dlitem['grplist'] != "")
		{
			$grplist = explode(",", $dlitem['grplist']);

			$usergroup = $mybb->usergroup['gid'];
			if(!(($dlitem['grpflag']=="1") ^ in_array($usergroup, $grplist)))
			{
				return false;
			}
		}		
		return true;
	}
	
	/**
	 * function to check if the download is possible
	 */
	function CheckDownload($dlid = false)
	{
		global $mybb, $db;
		
		if(!((string)($dlid) === (string)(int)($dlid)))
			header("Location: downloads.php");
		else
		{
			$query = $db->simple_select("downloads", "filename, grpflag, grplist, public", "dlid=".$dlid);
			$dlitem = $this->GetDownloadInfo($dlid);
			if($dlitem != false)
			{
				if($this->CheckPermissions($dlitem) == true)
				{
					// @FIXME Funktion anwenden
					//$Download->SendDownloadFile($dlitem);
					return $dlitem;
				}
				else
				{
					// redirect to view page, which will show any errors.
					header("Location: downloads.php?action=view&dlid=".$mybb->input["dlid"]);
				}
			}
			else
			{
				// non-existant download id, redirect
				header("Location: downloads.php");
			}
		}
	}
	
	/**
	 * function to send the file to the user
	 */
	function SendDownloadFile($dlitem)
	{
		global $db, $mybb;
		
		// DONE pretend config.php to be downloaded
		// DONE maybe pretend all mybb files from beeing downloaded
		// DONE only search for downloads in the download directory
		if( !preg_match("@^(?:http://)@i", $dlitem['filename']) && !preg_match("@^(?:ftp://)@i", $dlitem['filename']))
		{
			if(is_file(MYBB_ROOT."/uploads/downloads/".$dlitem['filename']))
			{
				header("Content-type: application/octet-stream");
				header("Content-Disposition: attachment; filename={$dlitem['filename']}");
				header("Content-length: ".(string)(filesize(MYBB_ROOT."/uploads/downloads/".$dlitem['filename'])));
				// Send the file contents. Found and adapted from http://www.webmasterworld.com/php/3339169.htm (3rd post)
				$file = fopen(MYBB_ROOT."/uploads/downloads/".$dlitem['filename'],"rb"); 
				while(!feof($file))
				{
					print(fread($file, filesize(MYBB_ROOT."/uploads/downloads/".$dlitem['filename'])));
					flush();
					if(connection_status() != 0)
					{
						fclose($file);
						die();
					}
				}
				fclose($file);
				$db->write_query("UPDATE ".TABLE_PREFIX."downloads SET dlcount=dlcount+1 WHERE dlid=".$mybb->input["dlid"]);
			}
			else
			{
				// redirect to view page, which will show any errors.
				header("Location: downloads.php?action=view&dlid=".$mybb->input["dlid"]);
			}
		}
		else
		{
			// we're going to have to assume that the user did, in fact, download the file
			$db->write_query("UPDATE ".TABLE_PREFIX."downloads SET dlcount=dlcount+1 WHERE dlid=".$mybb->input["dlid"]);
			header("Location: ".$dlitem['filename']);
		}
	}
	
	/**
	 * function to get information about one download
	 */
	function GetDownloadInfo($dlid = false)
	{
		global $db;
		
		if($dlid == false) return false;
		
		$query = $db->simple_select("downloads", "*", "dlid={$dlid}");		
		if(!($dlitem = $db->fetch_array($query)))
		{
			return false;
		}
		else
		{
			return $this->download_info = $dlitem;
		}
	}
	
	/**
	 * function to get the downloads of a user
	 */
	function GetUserDownloads($uid = false)
	{
		global $db;
		
		if($uid == false) return false;
		
		$query = $db->simple_select("downloads", "*", "uid={$uid}");		
		if(!($dlitem = $db->fetch_array($query)))
		{
			return false;
		}
		else
		{
			return $this->user_downloads = $dlitem;
		}
	}
	
	/**
	 * function to accept a download
	 */
	function AcceptDownload($dlid = false)
	{
		global $db;
		
		if($dlid != false)
		{
			// write stuff to database
			$update_array = array(
				"public"		=> 1,
			);
			$db->update_query("downloads", $update_array, "dlid='$dlid'");
			return true;
		}
		else
			return false;
	}
	
	/**
	 * function to reject a download
	 */
	function RejectDownload($dlid = false)
	{
		global $db;
		
		if($dlid != false)
		{
			// write stuff to database
			$update_array = array(
				"public"		=> 0,
			);
			$db->update_query("downloads", $update_array, "dlid='$dlid'");
			return true;
		}
		else
			return false;
	}
	
	/**
	 * function to delete a download
	 */
	function DeleteDownload($dlid = false)
	{
		global $db, $mybb;
		
		if($dlid != false)
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
			return true;
		}
		else
			return false;
	}
}


// Quick function to recursively build the heirarchy
function listCats($curcat, $catlevel, $heirarchy, $subIndicator, &$alternate, &$category)
{
	global $templates;
	$alternate++;
	$catlist;
	$alt_base = "trow";
	foreach( $category[$catlevel] as $cat )
	{
		$catname = $subIndicator.$cat['title'];
		$catid = $cat['catid'];
		if($catid == $curcat)
		{
			$altbg = $alt_base."_selected";
			add_breadcrumb($cat['title'], "downloads.php?cat={$catid}");
			eval("\$catlist .= \"".$templates->get("downloads_sidecat_listbit")."\";");
			if(is_array($category[$catid]))
			{
				$catlist .= listCats($curcat, $catid, $heirarchy, $subIndicator."<img src=\"/admin/styles/default/images/nav_bit.gif\" /> ", $alternate, $category);
			}
		}
		else if($catid == $heirarchy[0])
		{
			$altbg = $alt_base.($alternate%2+1);
			add_breadcrumb($cat['title'], "downloads.php?cat={$catid}");
			eval("\$catlist .= \"".$templates->get("downloads_sidecat_listbit")."\";");
			array_shift($heirarchy);
			$catlist .= listCats($curcat, $catid, $heirarchy, $subIndicator."<img src=\"/admin/styles/default/images/nav_bit.gif\" /> ", $alternate, $category);
		}
		else
		{
			$altbg = $alt_base.($alternate%2+1);
			eval("\$catlist .= \"".$templates->get("downloads_sidecat_listbit")."\";");
		}
		$alternate++;
	}
	$alternate++;
	return $catlist;
}

// another Quick function to recursively build the heirarchy
function listCats2($catlevel, $subIndicator, &$category)
{
	foreach( $category[$catlevel] as $cat )
	{
		$cat['title'] = $subIndicator.$cat['title'];
		$catlist[] = $cat;
		if(is_array($category[$cat['catid']]))
		{
			$subCats = listCats2($cat['catid'], $subIndicator."-", $category);
			$catlist = array_merge($catlist, $subCats);
		}
	}
	return $catlist;
}

function ShowAddForm()
{
	global $db, $mybb, $templateName, $searchcatlist, $lang, $category;
	// generate the select box for the user to
	// choose which cats to search
	$searchcatlist = '<select name="cat" size="5">';
	
	// list categories
	$cats = listCats2('', "", $category);
	foreach($cats as $cat)
	{
		$searchcatlist .= "<option value=\"{$cat['catid']}\">{$cat['title']}</option>";
	}
	
	$searchcatlist .= "</select>";
	$templateName = "downloads_addform";
	#die('add new');
	return;
}

function AddUserDownload()
{
	#die('added new');
	global $lang, $mybb, $db, $templateName, $message;
	$filename = basename( $_FILES['downloadfile']['name']);
	$preview = basename( $_FILES['previewfile']['name']);
	if($filename=="")
	{	// we have to have a download file
		$message= $lang->downloads_dlmngr_nofile;
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
		    	ftp_chdir($ftp_con, $mybb->settings['downloads_ftpdir']."/uploads/downloads/");
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
			$message = $lang->downloads_dlmngr_problem_dl.$_FILES['downloadfile']['error'].'<br />'.$errors[0];
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
				if($mybb->input['cat'] == "") $mybb->input['cat'] = "1";
				$insert_array = array(
					"title"		=> $db->escape_string($mybb->input['title']),
					"desc_short"	=> $db->escape_string($mybb->input['desc_short']),
					"description"	=> $db->escape_string($mybb->input['description']),
					#"fid"		=> $mybb->input['fid'],
					"cat"		=> $mybb->input['cat'],
					"preview"		=> $filename_prefix.$num.$preview,
					"filename"	=> $filename_prefix.$num.$filename,
					#"grpflag"		=> '',
					#"grplist"		=> '',
					"public"		=> 0,
				);
				$db->insert_query("downloads", $insert_array);
				$message = $filename.$lang->downloads_dlmngr_uploaded;
			}
			else
			{	// there was a problem uploading the preview
				// remove the download file
				@unlink(MYBB_ROOT."/uploads/downloads/".$num.$filename);
				// TODO set a special error message for each kind of errors
				$message = $lang->downloads_dlmngr_problem_pr.$_FILES['previewfile']['error'].'<br />'.$errors[0];
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
	$templateName = "downloads_addform";
#	die('add new');
	return;
}
?>