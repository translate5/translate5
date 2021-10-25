<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

/***
 *  README:
    Replace language id's in ERP_purchaseOrder and ERP_order with rfc5646 value
 
    TMUE-143 Referencing languages via rfc5646 instead of id 
 */

set_time_limit(0);


/* @var $this ZfExtended_Models_Installer_DbUpdater */

//$this->doNotSavePhpForDebugging = false;

/**
 * define database credential variables
 */
$argc = count($argv);
if(empty($this) || empty($argv) || $argc < 5 || $argc > 7) {
    die("please dont call the script direct! Call it by using DBUpdater!\n\n");
}

$db = Zend_Db_Table::getDefaultAdapter();
$conf = $db->getConfig();
$dbname = $conf['dbname'];

//get the languages
$res = $db->query('SELECT * FROM LEK_languages');
$languages_full = $res->fetchAll();
$languages = array();
foreach($languages_full as $languages_single){
    $languages[$languages_single['id']]=$languages_single['rfc5646'];
}
//convert to simple id => rfc map

/////////////////////////////////////PO START////////

//get the po's
$res = $db->query('SELECT id,sourceLang,targetLang FROM ERP_purchaseOrder');
$purchaseOrder = $res->fetchAll();
//update po

$stmt = $db->prepare('UPDATE ERP_purchaseOrder set sourceLang = :sourceLang, targetLang = :targetLang where id = :id');

foreach ($purchaseOrder as &$po){

    $po['sourceUpdate'] = isset($languages[$po['sourceLang']]) ? $languages[$po['sourceLang']] : null;
    
    $po['targetUpdate'] = isset($languages[$po['targetLang']]) ? $languages[$po['targetLang']] : null;


    if(isset($po['sourceUpdate']) || isset($po['targetUpdate'])){
        //prepare update statement
        $stmt->execute([
            ':sourceLang' =>$po['sourceUpdate'],
            ':targetLang' => $po['targetUpdate'],
            ':id' => $po['id'],
        ]);
    }
    else if(!empty($po['sourceLang']) || !empty($po['targetLang'])){
        if(!isSourceTargetValid($languages,$po['sourceLang'],$po['targetLang']) ){
            echo "PO could not converted due wrong language IDs. Po id -> (".$po['id'].'),'.
                ' sourceLanguage->('.$po['sourceLang'].'),'.
                ' targetLanguage->('.$po['targetLang'].')'.'<br/>';
        }
    }
}
/////////////////////////////////////////PO END////////


/////////////////////////////////////////PO History START////////

//get the po's
$res = $db->query('SELECT id,sourceLang,targetLang FROM ERP_purchaseOrder_history');
$purchaseOrderHistory = $res->fetchAll();
//update po

$stmt = $db->prepare('UPDATE ERP_purchaseOrder_history set sourceLang = :sourceLang, targetLang = :targetLang where id = :id');

foreach ($purchaseOrderHistory as &$po){

    $po['sourceUpdate'] = isset($languages[$po['sourceLang']]) ? $languages[$po['sourceLang']] : null;
    
    $po['targetUpdate'] = isset($languages[$po['targetLang']]) ? $languages[$po['targetLang']] : null;


    if(isset($po['sourceUpdate']) || isset($po['targetUpdate'])){
        //prepare update statement
        $stmt->execute([
            ':sourceLang' =>$po['sourceUpdate'],
            ':targetLang' => $po['targetUpdate'],
            ':id' => $po['id'],
        ]);
    }
    else if(!empty($po['sourceLang']) || !empty($po['targetLang'])){
        if(!isSourceTargetValid($languages,$po['sourceLang'],$po['targetLang'])){
            echo "(Po History table) PO could not converted due wrong language IDs. Po History id -> (".$po['id'].'),'.
                ' sourceLanguage->('.$po['sourceLang'].'),'.
                ' targetLanguage->('.$po['targetLang'].')'.'<br/>';
        }
    }
    
}

/////////////////////////////////////////PO History END//////////


///////////////////////////////////////// Order START ////////
//get the orders
$res = $db->query('SELECT id,sourceLang,targetLang FROM ERP_order');
$orders = $res->fetchAll();

$stmt_order = $db->prepare('UPDATE ERP_order set sourceLang = :sourceLang, targetLang = :targetLang where id = :id');

foreach($orders as &$order){
        
    $order['sourceUpdate'] = isset($languages[$order['sourceLang']]) ? $languages[$order['sourceLang']] : null;

    $rfclangs=array();

    if(isset($order['targetLang'])){
        $targetLangs = explode(',',$order['targetLang']);
        if(empty($targetLangs)){
            continue;
        }
        foreach($targetLangs as $lng){
            if(empty($lng)){
                continue;
            }
            if(isset($languages[$lng])){
                array_push($rfclangs,$languages[$lng]);
            }
        }
        if(!empty($rfclangs)){
            $order['targetUpdate'] = ','.join(',',$rfclangs).',';
        }
    }
    if(isset($order['sourceUpdate']) || isset($order['targetUpdate'])){
        //prepare update statement
        $stmt_order->execute([
            ':sourceLang' => isset($order['sourceUpdate']) ? $order['sourceUpdate'] : null,
            ':targetLang' => isset($order['targetUpdate']) ? $order['targetUpdate'] : null,
            ':id' => $order['id'],
        ]);
    }else if(!empty($order['sourceLang']) || !empty($order['targetLang'])){
        if(!isSourceTargetValidOrder($languages,$order['sourceLang'],$order['targetLang'])){
            echo "Order could not be converted due wrong language IDs. Order id -> (".$order['id'].'),'.
                ' sourceLanguage->('.$order['sourceLang'].'),'.
                ' targetLanguage->('.$order['targetLang'].')'.'<br/>';
        }
    }
}

///////////////////////////////////////// Order END ////////

///////////////////////////////////////// Order History START ////////

//get the orders
$res = $db->query('SELECT id,sourceLang,targetLang FROM ERP_order_history');
$ordersHistory = $res->fetchAll();

$stmt_order = $db->prepare('UPDATE ERP_order_history set sourceLang = :sourceLang, targetLang = :targetLang where id = :id');

foreach($ordersHistory as &$order){
        
    $order['sourceUpdate'] = isset($languages[$order['sourceLang']]) ? $languages[$order['sourceLang']] : null;

    $rfclangs=array();

    if(isset($order['targetLang'])){
        $targetLangs = explode(',',$order['targetLang']);
        if(empty($targetLangs)){
            continue;
        }
        foreach($targetLangs as $lng){
            if(empty($lng)){
                continue;
            }
            if(isset($languages[$lng])){
                array_push($rfclangs,$languages[$lng]);
            }
        }
        if(!empty($rfclangs)){
            $order['targetUpdate'] = ','.join(',',$rfclangs).',';
        }
    }
    if(isset($order['sourceUpdate']) || isset($order['targetUpdate'])){
        //prepare update statement
        $stmt_order->execute([
            ':sourceLang' => isset($order['sourceUpdate']) ? $order['sourceUpdate'] : null,
            ':targetLang' => isset($order['targetUpdate']) ? $order['targetUpdate'] : null,
            ':id' => $order['id'],
        ]);
    }else if(!empty($order['sourceLang']) || !empty($order['targetLang'])){
        if(!isSourceTargetValidOrder($languages,$order['sourceLang'],$order['targetLang'])){
            echo "(Order history table) Order could not be converted due wrong language IDs. Order History id -> (".$order['id'].'),'.
                ' sourceLanguage->('.$order['sourceLang'].'),'.
                ' targetLanguage->('.$order['targetLang'].')'.'<br/>';
        }
    }
}
///////////////////////////////////////// Order History END ////////

//check if the languages are valid 
function isSourceTargetValid($languages,$source,$target){
    return in_array($source,$languages) && in_array($target,$languages);
}

//check if the languages is valid (orders check)
function isSourceTargetValidOrder($languages,$source,$target){
    $sourceValid = in_array($source,$languages);
    if(!$sourceValid){
        return false;
    }
    if(!$target && $target=="" && $target==","){
        return false;
    }

    $targetLangs = explode(',',$target);
    if(empty($targetLangs)){
        return false;
    }
    $retval=true;
    foreach($targetLangs as $lng){
        if(empty($lng)){
            continue;
        }
        if(!in_array($lng,$languages)){
            $retval = false;
        }
    }
    return $retval;
}