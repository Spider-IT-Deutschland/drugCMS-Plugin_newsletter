<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend user editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Bj�rn Behrens (HerrB)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2007-01-01, Bj�rn Behrens (HerrB)
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$plugin_name = 'newsletter';

cInclude("includes", "functions.con.php");	// For conDeleteArt and conCopyArt

// Initialization
$oPage			= new cPage;
$oClientLang 	= new cApiClientLanguage(false, $client, $lang);

// Include plugins
if (is_array($cfg['plugins']['newsletters'])) {
	foreach ($cfg['plugins']['newsletters'] as $plugin) {
		plugin_include("newsletters", $plugin."/".$plugin.".php");	
	}
}

// Exec actions
$oNewsletter = new Newsletter;
$oNewsletter->loadByPrimaryKey($idnewsletter);

if ($oNewsletter->virgin == false && $oNewsletter->get("idclient") == $client && $oNewsletter->get("idlang") == $lang) {
	// Check and set values
	if (!is_numeric($_REQUEST["selTemplate"])) {
		$_REQUEST["selTemplate"] = 0;
	}	
	
	// Saving message changes; note, that if a user doesn't have the right to save the
	// text message he may still have the right to change the html article. To prevent 
	// changing the html article, give the user only read access right for the newsletter 
	// article category - the article will be shown also, if he doesn't have any rights at all...
	if ($action == "news_save" && $perm->have_perm_area_action("news", $action)) // Don't use $area! 
	{ 
		// Changing e.g. \' back to ' (magic_quotes)
		$sMessage = Contenido_Security::unescapeDB($_REQUEST["txtMessage"]);
		$oNewsletter->set("message", $sMessage);
		
		if ($oNewsletter->get("template_idart") != $_REQUEST["selTemplate"])
	 	{		
	 		if ($oNewsletter->get("idart") > 0)
	 		{
				// Template has been changed: Delete old article
				// (this discards the current html content as it deletes the existing newsletter article)
	 			conDeleteArt($oNewsletter->get("idart"));
	 			$iIDArt = 0;
	 		}
	 		
	 		if ($_REQUEST["selTemplate"] > 0)
	 		{
	 			// Template has been changed, but specified: Store template article as new newsletter article
	 			$iIDArt = conCopyArticle($_REQUEST["selTemplate"], 
	 									 $oClientLang->getProperty("newsletter", "html_newsletter_idcat"), 
	 									 sprintf(i18n("Newsletter: %s", $plugin_name), $oNewsletter->get("name")));
	 			conMakeOnline($iIDArt, $lang); // Article has to be online for sending...
	 		} 
	 		
	 		$oNewsletter->set("idart", 			$iIDArt);
	 		$oNewsletter->set("template_idart", $_REQUEST["selTemplate"]);
	 	}
	 							
		$oNewsletter->store();
	} else if ($oNewsletter->get("idart") > 0) {
		// Check, if html message article and template article are still available
		$oArticles = new cApiArticleLanguageCollection;
		$oArticles->setWhere("idlang", $lang);
		$oArticles->setWhere("idart", $oNewsletter->get("idart"));
		$oArticles->query();
		
		if ($oArticles->count() == 0)
		{
			// Ups, article lost, reset idart and template_idart for newsletter
			$notis = $notification->returnNotification("error", sprintf(i18n("The html newsletter article has been deleted (idart: %s), the html message is lost", $plugin_name), $oNewsletter->get("idart"))) . "<br>";
			
			$oNewsletter->set("idart", 0);
			$oNewsletter->set("template_idart", 0);
			$oNewsletter->store();
		} else {
			$oArticles->resetQuery();
			$oArticles->setWhere("idlang", $lang);
			$oArticles->setWhere("idart", $oNewsletter->get("template_idart"));
			$oArticles->query();
			
			if ($oArticles->count() == 0)
			{
				// Ups, template has been deleted: Restore from current newsletter message article
				$notis = $notification->returnNotification("warning", i18n("The html newsletter template article has been deleted, it has been restored using the html message article of this newsletter", $plugin_name)) . "<br>";
				
	 			$iIDArt = conCopyArticle($oNewsletter->get("idart"), 
	 									 $oClientLang->getProperty("newsletter", "html_template_idcat"), 
	 									 sprintf(i18n("%s (Template restored)", $plugin_name), $oNewsletter->get("name")));
	 			$oNewsletter->set("template_idart", $iIDArt);
	 			$oNewsletter->store();
			}
		}	
	}
	
	$oForm = new UI_Table_Form("frmNewsletterMsg");
	$oForm->setVar("frame", $frame);
	$oForm->setVar("area", $area);
	$oForm->setVar("action", "news_save");
	$oForm->setVar("idnewsletter", $idnewsletter);
	$oForm->setWidth("100%");

	$oForm->addHeader(sprintf(i18n("Edit newsletter message (%s)", $plugin_name), $oNewsletter->get("name")));
	$oForm->add(i18n("Subject", $plugin_name), $oNewsletter->get("subject"));
	
	$sTagInfoText = '<a href="javascript:fncShowHide(\'idTagInfoText\');"><strong>'.i18n("Tag information", $plugin_name).'</strong></a>'.
			'<div id="idTagInfoText" style="display: none"><br /><b>'. i18n("Special message tags (will be replaced when sending)", $plugin_name).':</b><br>'.
			'MAIL_NAME: '.i18n("Name of the recipient", $plugin_name).'<br />'.
			'MAIL_DATE: '.i18n("Date, when the mail has been sent", $plugin_name).'<br />'.
			'MAIL_TIME: '.i18n("Time, when the mail has been sent", $plugin_name).'<br />'.
			'MAIL_NUMBER: '.i18n("Number of recipients", $plugin_name).'<br />'.
			#'MAIL_CHANGE: '.i18n("Link to change the e-mail adress", $plugin_name).'<br />'.
			'MAIL_UNSUBSCRIBE: '.i18n("Link to unsubscribe", $plugin_name).'<br />'.
			'MAIL_STOP: '.i18n("Link to pause the subscription", $plugin_name).'<br />'.
			'MAIL_GOON: '.i18n("Link to resume the subscription", $plugin_name);	

	$sTagInfoHTML = '<a href="javascript:fncShowHide(\'idTagInfoHTML\');"><strong>'.i18n("Tag information", $plugin_name).'</strong></a>'.
			'<div id="idTagInfoHTML" style="display: none"><br /><b>'.i18n("Special message tags (will be replaced when sending, {..} = optional)", $plugin_name).":</b><br />".
			'[mail name="name" type="text"]{text}MAIL_NAME{text}[/mail]: '.i18n("Name of the recipient", $plugin_name)."<br />".
			'[mail name="date" type="text"]{text}MAIL_DATE{text}[/mail]: '.i18n("Date, when the mail has been sent", $plugin_name)."<br />".
			'[mail name="time" type="text"]{text}MAIL_TIME{text}[/mail]: '.i18n("Time, when the mail has been sent", $plugin_name)."<br />".
			'[mail name="number" type="text"]{text}MAIL_NUMBER{text}[/mail]: '.i18n("Number of recipients", $plugin_name)."<br />".
			#'[mail name="change" type="link" {text="'.i18n("Link text", $plugin_name).'"}]{text}MAIL_CHANGE{text}[/mail]: '.i18n("Link to change the e-mail adress", $plugin_name)."<br />".
			'[mail name="unsubscribe" type="link" {text="'.i18n("Link text", $plugin_name).'" }]{text}MAIL_UNSUBSCRIBE{text}[/mail]: '.i18n("Link to unsubscribe", $plugin_name)."<br />".
			'[mail name="stop" type="link" {text="'.i18n("Link text", $plugin_name).'" }]{text}MAIL_STOP{text}[/mail]: '.i18n("Link to pause the subscription", $plugin_name)."<br />".
			'[mail name="goon" type="link" {text="'.i18n("Link text", $plugin_name).'" }]{text}MAIL_GOON{text}[/mail]: '.i18n("Link to resume the subscription", $plugin_name);	
	
	// Mention plugin interface
	if (getSystemProperty("newsletter", "newsletter-recipients-plugin") == "true")
	{
		$sTagInfoText .= "<br /><br /><strong>".i18n("Additional message tags from recipients plugins:", $plugin_name)."</strong><br />";
		$sTagInfoHTML .= "<br /><br /><strong>".i18n("Additional message tags from recipients plugins:", $plugin_name)."</strong><br />";

		if (is_array($cfg['plugins']['recipients']))
		{
			foreach ($cfg['plugins']['recipients'] as $plugin)
			{
				plugin_include("recipients", $plugin."/".$plugin.".php");

				if (function_exists("recipients_".$plugin."_wantedVariables"))
				{
					$aPluginVars = call_user_func("recipients_".$plugin."_wantedVariables");
					
					foreach ($aPluginVars as $sPluginVar)
					{
						$sTagInfoText .= 'MAIL_'.strtoupper($sPluginVar).'<br />';
						$sTagInfoHTML .= '[mail name="'.strtolower($sPluginVar).'" type="text"][/mail]<br />';
					}
				}	
			}
		}
	} else {
		setSystemProperty("newsletter", "newsletter-recipients-plugin", "false"); // -> Property available in system settings
	}
	$sTagInfoText .= "</div>";
	$sTagInfoHTML .= "</div>";

	$iTplIDArt = 0; // Used later for on change event
	if ($oNewsletter->get("type") == "html")
	{ 
		$iTplIDArt 		= $oNewsletter->get("template_idart");
		$oSelTemplate 	= new cHTMLSelectElement("selTemplate");
		$oSelTemplate->setEvent("change", "askSubmitOnTplChange(this);");
		$aOptions 		= array(	"idcat" => $oClientLang->getProperty("newsletter", "html_template_idcat"),
								 	"start" => true,
						 			"offline" => true,
						 			"order" => "title");
		$oTemplateArticles = new ArticleCollection($aOptions);
		
		$aItems = array();
		$aItems[] = array(0, i18n("-- none --", $plugin_name));
		while ($oArticle = $oTemplateArticles->nextArticle())
		{
			$aItems[] = array($oArticle->get("idart"), $oArticle->get("title"));
		}
		
		$oSelTemplate->autoFill($aItems);
		$oSelTemplate->setDefault($iTplIDArt);
		unset ($aItems);
		unset ($oArticles);
		unset ($oTemplateArticles);
		
		$oForm->add(i18n("HTML Template", $plugin_name), $oSelTemplate->render()."&nbsp;".i18n("Note, that changing the template discards the current html message content", $plugin_name));
		
		if ($iTplIDArt != 0)
		{
			$sFrameSrc = $cfgClient[$client]["path"]["htmlpath"]."front_content.php?changeview=edit&action=con_editart&idart=".$oNewsletter->get("idart")."&idcat=".$oClientLang->getProperty("newsletter", "html_newsletter_idcat")."&lang=".$lang."&contenido=".$sess->id;
			$oForm->add(i18n("HTML Message", $plugin_name), '<iframe width="100%" height="600" src="'.$sFrameSrc.'"></iframe><br />'.$sTagInfoHTML);
		} else {
			// Add a real note, that a template has to be specified
			$notis .= $notification->returnNotification("warning", i18n("Newsletter type has been set to HTML/text, please remember to select an html template", $plugin_name)) . "<br />";
			
			$oForm->add(i18n("HTML Message", $plugin_name), i18n("Please choose a template first", $plugin_name));
		}
	}

	$oTxtMessage = new cHTMLTextarea("txtMessage", $oNewsletter->get("message"), 80, 20);
	$oForm->add(i18n("Text Message", $plugin_name), $oTxtMessage->render()."<br />".$sTagInfoText);
	
	$sExecScript = '
	<script type="text/javascript">
		// Enabled/Disable group box
		function fncShowHide(strItemID) {
			objItem = document.getElementById(strItemID);

			if (objItem.style.display == "none") {
				objItem.style.display = "inline";
			} else {
				objItem.style.display = "none";
			}
		}

		// Create messageBox instance
		box = new messageBox("", "", "", 0, 0);

		// If html newsletter template selection has changed, ask user
		// if he/she may like to save this change (e.g. to get an html
		// newsletter immediately)
		function askSubmitOnTplChange(oSelectObject) {
			iOriginalTplIDArt = '.$iTplIDArt.';

			if (iOriginalTplIDArt != oSelectObject.options[oSelectObject.selectedIndex].value) {
				if (iOriginalTplIDArt == 0) {
					// Everything fine: Just selecting a template for the first time
					submitForm();
				} else {
					// You may loose information, warn!
					box.confirm("'.i18n("HTML newsletter template changed", $plugin_name).'", "'.i18n("HTML template has been changed. Do you like to save now to apply changes?<br /><br /><b>Note, that existing HTML newsletter content will get lost!</b>", $plugin_name).'", "submitForm()");
				}
			}
		}

		function submitForm() {
			document.frmNewsletterMsg.submit();
		}
		</script>';
	$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
	$oPage->addScript('execscript', $sExecScript);
	$oPage->setContent($notis . $oForm->render(true));
} else {
	$oPage->setContent($notis . "");
}

$oPage->render();
?>