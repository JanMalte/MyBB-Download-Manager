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
if(!defined('IN_MYBB'))
{
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

// load language strings
$lang->load('downloads');

// output page header
$page->output_header($lang->downloads_dlmngr_cats);

// add our page to the navigation links
$page->add_breadcrumb_item($lang->downloads_dlmngr_cats, 'index.php?module=dlmngr/cats');

$plugins->run_hooks("admin_dlmgr_categories_begin");

if($mybb->input['cancel']) {
	flash_message('Canceled','info');
	admin_redirect('index.php?module=dlmngr/cats');
}

switch($mybb->input['action']) {
	case 'delete' :
		$catid = $mybb->input['catid'];
		$db->delete_query('downloads_catlist','catid='.$catid);
		$db->delete_query('downloads_catlist','parentID='.$catid);
		flash_message('Categorie deleted');
		admin_redirect('index.php?module=dlmngr/cats');
		break;

	case 'save' :
		$categorie = array('title' => $db->escape_string($mybb->input['title']));
		$db->update_query('downloads_catlist', $categorie, 'catid='.$mybb->input['catid']);
		flash_message('Saved changes','success');
		admin_redirect('index.php?module=dlmngr/cats');
		break;

	case 'add' :
		$newcat = array(
			"title"	=> $mybb->input['title']
		);
		$db->insert_query("downloads_catlist", $newcat);
		flash_message('New categorie added','success');
		admin_redirect('index.php?module=dlmngr/cats');
		break;

	case 'savesub' :
		$newcat = array(
			"title"	=> $mybb->input['title'],
			"parentID"	=> $mybb->input['parentcat'],
		);
		$db->insert_query("downloads_catlist", $newcat);
		flash_message('New sub categorie added','success');
		admin_redirect('index.php?module=dlmngr/cats');
		break;

	case 'edit' :
		$form = new Form("index.php?module=dlmngr/cats&amp;action=save&amp;catid=".$mybb->input['catid'], "post", "", true);
			break;

	case 'addsub' :
		$form = new Form("index.php?module=dlmngr/cats&amp;action=savesub", "post", "", true);
		break;
	
	default:
		$form = new Form("index.php?module=dlmngr/cats&amp;action=add", "post", "", true);
		break;
}

$table = new Table;
$table->construct_header($lang->downloads_dlmngr_cat);
$table->construct_header($lang->controls, array("class" => "align_center", "width" => 250));

showCatsTable();

if(($mybb->input['action'] != "edit") && ($mybb->input['action'] != "addsub")) {
	$table->construct_cell("<strong>{$lang->downloads_dlmngr_addnew}</strong><br/>".$form->generate_text_box('title', $lang->downloads_dlmngr_addnew, array('id' => 'title')));
	$table->construct_cell($form->generate_submit_button($lang->downloads_dlmngr_save, array('name' => 'add')), array("class" => "align_center"));
	$table->construct_row();
}

// output the created table
$table->output($lang->downloads_dlmngr_cats);

// show the created form
$form->end();

// output the page footer
$page->output_footer();

/**
 * show a table row for each categorie
 * 
 * @param $id ID of the parent categorie
 * @param $depth depth of the parent categorie; neccessary to display the icons
 */
function showCatsTable($id = false, $depth = 0)
{
	global $db, $mybb, $table, $form, $lang;

	if($id == false) {
		$query = $db->simple_select('downloads_catlist', '*', "parentID IS NULL", array('order by' => 'catid'));
	}
	else {
		$query = $db->simple_select('downloads_catlist', '*', "parentID=$id", array('order by' => 'catid'));
	}
	
	while($depth > $i) {
		$i++; $subdeepth .= "<img src=\"/images/nav_bit.gif\" /> ";
	}
	$depth++;

	while($cat = $db->fetch_array($query)) {
		if(($mybb->input['action'] == "addsub") && ($cat['catid'] == $mybb->input['catid'])) {
			$table->construct_cell($subdeepth."<strong><a href=\"index.php?module=dlmngr/cats&amp;action=edit&amp;catid={$cat['catid']}\">{$cat['title']}</a></strong>",array("colspan" => "2"));
			$table->construct_row();
			$table->construct_cell($subdeepth."<img src=\"/images/nav_bit.gif\" /> ".$form->generate_text_box('title', "Sub - {$cat['title']}", array('id' => 'title')));
			$table->construct_cell($form->generate_hidden_field('parentcat', $cat['catid'], array('id' => 'parentcat')).
									$form->generate_submit_button($lang->downloads_dlmngr_save, array('name' => 'savesub')).
									$form->generate_submit_button($lang->downloads_dlmngr_cancel, array('name' => 'cancel')), array("class" => "align_center"));	
		}
		elseif(($mybb->input['action'] != "edit") || ($cat['catid'] != $mybb->input['catid'])) {
			$table->construct_cell($subdeepth."<strong><a href=\"index.php?module=dlmngr/cats&amp;action=edit&amp;catid={$cat['catid']}\">{$cat['title']}</a></strong>");	
			$popup = new PopupMenu("cat_{$cat['catid']}", $lang->options);
			$popup->add_item($lang->downloads_dlmngr_edit, "index.php?module=dlmngr/cats&amp;action=edit&amp;catid={$cat['catid']}");
			$popup->add_item($lang->downloads_dlmngr_delete, "index.php?module=dlmngr/cats&amp;action=delete&amp;catid={$cat['catid']}");
			$popup->add_item($lang->downloads_dlmngr_addsub, "index.php?module=dlmngr/cats&amp;action=addsub&amp;catid={$cat['catid']}");
			$table->construct_cell($popup->fetch(), array("class" => "align_center"));
		}
		else {
			$table->construct_cell($form->generate_text_box('title', $cat['title'], array('id' => 'title')));
			$table->construct_cell($form->generate_submit_button($lang->downloads_dlmngr_save, array('name' => 'save')), array("class" => "align_center"));
		}
		$table->construct_row();
		showCatsTable($cat['catid'], $depth);
	}
}
