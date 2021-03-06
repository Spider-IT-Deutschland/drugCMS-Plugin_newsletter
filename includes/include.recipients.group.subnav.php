<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Custom subnavigation for the newsletter recipient groups
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Bj�rn Behrens (HerrB)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2004-08-01, Bj�rn Behrens (HerrB)
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$plugin_name = 'newsletter';

if (isset($_GET['idrecipientgroup']) && (int)$_GET['idrecipientgroup'] > 0)
{
	$caption = i18n("Overview", $plugin_name);
	$tmp_area = "foo2";

	# Set template data
	$tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt[0]);
	$tpl->set("d", "CLASS",     '');
	$tpl->set("d", "OPTIONS",   '');
	$tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$area&frame=4&idrecipientgroup=$idrecipientgroup").'">'.$caption.'</a>');
	$tpl->next();
    
	if (is_array($cfg['plugins']['recipientslogic'])) {
		foreach ($cfg['plugins']['recipientslogic'] as $plugin) {
			cInclude("plugins", "recipientslogic/$plugin/".$plugin.".php");

			$className = "recipientslogic_".$plugin;    
			$class     = new $className;
    
			$caption   = $class->getFriendlyName();
			$tmp_area  = "foo2";    	
			$tpl->set("d", "ID",        'c_'.$tpl->dyn_cnt[0]);
			$tpl->set("d", "CLASS",     '');
			$tpl->set("d", "OPTIONS",   '');
			$tpl->set("d", "CAPTION",   '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=recipientgroup_rights&frame=4&useplugin=$plugin&idrecipientgroup=$idrecipientgroup").'">'.$caption.'</a>');
			$tpl->next();
		}
	}

	$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt[0] * 2) + 2);

	# Generate the third navigation layer
	$tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
} else {
	include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>