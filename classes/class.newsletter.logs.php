<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Newsletter log class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.1
 * @author     Björn Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created  2004-08-01
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$plugin_name= 'newsletter';

/**
 * Collection management class
 */
class cNewsletterLogCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_log"], "idnewslog");
        $this->_setItemClass("cNewsletterLog");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cNewsletterLogCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * Creates a single new log item
     * @param $idnewsjob    integer ID of corresponding newsletter send job
     * @param $idnewsrcp    integer ID of recipient
     * @param $rcp_name    string    Name of the recipient (-> recipient may be deleted)
     * @param $rcp_email    string    E-Mail of the recipient (-> recipient may be deleted)
     */
    public function create($idnewsjob, $idnewsrcp)
    {
        global $client, $lang, $auth;

        $idnewsjob  = Contenido_Security::toInteger($idnewsjob);
        $idnewsrcp  = Contenido_Security::toInteger($idnewsrcp);
        $client     = Contenido_Security::toInteger($client);
        $lang       = Contenido_Security::toInteger($lang);

        $this->resetQuery();
        $this->setWhere("idnewsjob", $idnewsjob);
        $this->setWhere("idnewsrcp", $idnewsrcp);
        $this->query();

        if ($oItem = $this->next()) {
            return $oItem;
        }

        $oRecipient = new Recipient;
        if ($oRecipient->loadByPrimaryKey($idnewsrcp)) {
            $oItem = parent::create();

            $oItem->set("idnewsjob", $idnewsjob);
            $oItem->set("idnewsrcp", $idnewsrcp);

            $sEMail = $oRecipient->get("email");
            $sName  = $oRecipient->get("name");

            if ($sName == "") {
                $oItem->set("rcpname", $sEMail);
            } else {
                $oItem->set("rcpname", $sName);
            }

            $oItem->set("rcpemail", $sEMail);
            $oItem->set("rcphash", $oRecipient->get("hash"));
            $oItem->set("rcpnewstype", $oRecipient->get("news_type"));
            $oItem->set("status", "pending");
            $oItem->set("created", date("Y-m-d H:i:s"), false);
            $oItem->store();

            return $oItem;
        } else {
            return false;
        }
    }

    /**
     * Gets all active recipients as specified for the newsletter and adds for
     * every recipient a log item
     * @param integer    $idnewsjob    ID of corresponding newsletter dispatch job
     * @param integer    $idnews        ID of newsletter
     * @return integer    Recipient count
     */
    public function initializeJob($idnewsjob, $idnews)
    {
        global $cfg;

        $idnewsjob = Contenido_Security::toInteger($idnewsjob);
        $idnews    = Contenido_Security::toInteger($idnews);

        $oNewsletter = new Newsletter();
        if ($oNewsletter->loadByPrimaryKey($idnews)) {
            $sDestination = $oNewsletter->get("send_to");
            $iIDClient    = $oNewsletter->get("idclient");
            $iIDLang      = $oNewsletter->get("idlang");

            switch ($sDestination) {
                case "all" :
                    $sDistinct = "";
                    $sFrom     = "";
                    $sSQL      = "deactivated='0' AND confirmed='1' AND idclient='".$iIDClient."' AND idlang='".$iIDLang."'";
                    break;
                case "default" :
                    $sDistinct = "distinct";
                    $sFrom     = $cfg["tab"]["news_groups"]." AS groups, ".$cfg["tab"]["news_groupmembers"]." AS groupmembers ";
                    $sSQL      = "recipientcollection.idclient = '".$iIDClient."' AND ".
                                 "recipientcollection.idlang = '".$iIDLang."' AND ".
                                 "recipientcollection.deactivated = '0' AND ".
                                 "recipientcollection.confirmed = '1' AND ".
                                 "recipientcollection.idnewsrcp = groupmembers.idnewsrcp AND ".
                                 "groupmembers.idnewsgroup = groups.idnewsgroup AND ".
                                 "groups.defaultgroup = '1' AND groups.idclient = '".$iIDClient."' AND ".
                                 "groups.idlang = '".$iIDLang."'";
                    break;
                case "selection" :
                    $aGroups = unserialize ($oNewsletter->get("send_ids"));

                    if (is_array($aGroups) && count($aGroups) > 0) {
                        $sGroups    = "'" . implode("','", $aGroups) . "'";

                        $sDistinct  = "distinct";
                        $sFrom      = $cfg["tab"]["news_groupmembers"]." AS groupmembers ";
                        $sSQL       = "recipientcollection.idclient = '".$iIDClient."' AND ".
                                      "recipientcollection.idlang = '".$iIDLang."' AND ".
                                      "recipientcollection.deactivated = '0' AND ".
                                      "recipientcollection.confirmed = '1' AND ".
                                      "recipientcollection.idnewsrcp = groupmembers.idnewsrcp AND ".
                                      "groupmembers.idnewsgroup IN (".$sGroups.")";
                    } else {
                        $sDestination = "unknown";
                    }
                    break;
                case "single" :
                    $iID = $oNewsletter->get("send_ids");
                    if (is_numeric($iID)) {
                        $sDistinct = "";
                        $sFrom     = "";
                        $sSQL      = "idnewsrcp = '".$iID."'";
                    } else {
                        $sDestination = "unknown";
                    }
                    break;
                default:
                    $sDestination = "unknown";
            }
            unset ($oNewsletter);

            if ($sDestination == "unknown") {
                return 0;
            } else {
                $oRecipients = new RecipientCollection;
                $oRecipients->flexSelect($sDistinct, $sFrom, $sSQL, "", "", "");

                $iRecipients = $oRecipients->count();

                while ($oRecipient = $oRecipients->next()) {
                    $this->create($idnewsjob, $oRecipient->get($oRecipient->primaryKey));
                }

                return $iRecipients;
            }
        } else {
            return 0;
        }
    }

    /**
     * Overriden delete function to update recipient count if removing recipient from the list
     * @param integer $idnewslog ID
     */
    public function delete($idnewslog)
    {
        $idnewslog = Contenido_Security::toInteger($idnewslog);

        $oLog = new cNewsletterLog($idnewslog);
        $iIDNewsJob = $oLog->get("idnewsjob");
        unset($oLog);

        $oJob = new cNewsletterJob($iIDNewsJob);
        $oJob->set("rcpcount", $oJob->get("rcpcount") - 1);
        $oJob->store();
        unset ($oJob);

        parent::delete($idnewslog);
    }

    public function deleteJob($idnewsjob)
    {
        $idnewsjob = Contenido_Security::toInteger($idnewsjob);
        $this->setWhere("idnewsjob", $idnewsjob);
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get($oItem->primaryKey));
        }

        return true;
    }
}


/**
 * Single NewsletterLog Item
 */
class cNewsletterLog extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_log"], "idnewslog");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cNewsletterLog($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>