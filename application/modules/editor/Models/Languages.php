<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Diese Klasse muss mittels factoryOverwrites überschrieben werden,
 * da die Herkunft der Sprachinformationen nicht Teil des Editor-Moduls ist,
 * sondern vom Default-Modul gestellt werden muss.
 *
 * @method string getRfc5646() getRfc5646()
 * @method int getLcid() getLcid()
 * @method string getUnix() getUnix()
 * @method int getId() getId()
 */
class editor_Models_Languages extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Languages';

    const LANG_TYPE_ID = 'id';
    const LANG_TYPE_RFC5646 = 'rfc5646';
    const LANG_TYPE_LCID = 'lcid';
    const LANG_TYPE_UNIX = 'unix';

    /**
     * Lädt die Sprache anhand dem übergebenen Sprachkürzel (nach RFC5646)
     * @param string $lang
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByRfc5646($lang){
        return $this->loader($lang, 'rfc5646');
    }

    /**
     * Lädt die Sprache anhand der übergebenen LCID
     * @param string $lcid
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByLcid($lcid){
        return $this->loader($lcid, 'lcid');
    }

    /**
     * Lädt die Sprache anhand der übergebenen UNIX Locale
     * @param string $unixLocale
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByUnix($unixLocale){
        return $this->loader($unixLocale, 'unixLocale');
    }

    /**
     * loads the language by the given DB ID
     * @param integer $id
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadById($id){
        return $this->loader($id, 'id');
    }

    /**
     * @param mixed $lang
     * @param string $field
     * @return Zend_Db_Table_Row_Abstract | null
     */
    protected function loader($lang, $field) {
        $s = $this->db->select();
        $s->where('lower('.$field.') = ?',strtolower($lang));
        return $this->row = $this->db->fetchRow($s);
    }

    /**
     * Gibt die interne Sprach ID (PK der Sprach Tabelle) zu einer LCID zurück
     * @param int $lcid LCID, wie in Tabelle languages hinterlegt
     * @return int id der gesuchten Sprache
     */
    public function getLangIdByLcid($lcid){
        $this->loadByLcid($lcid);
        return $this->getId();
    }

    /**
     * Gibt die interne Sprach ID (PK der Sprach Tabelle) zu einem Sprachkürzel nach RFC5646 zurück
     * @param int $lang Sprachkürzel nach RFC5646, wie in Tabelle languages hinterlegt
     * @return int id der gesuchten Sprache
     */
    public function getLangIdByRfc5646($lang){
        $this->loadByRfc5646($lang);
        return $this->getId();
    }

    /**
     * Gibt die interne Sprach ID (PK der Sprach Tabelle) zu einer UNIX Locale zurück
     * @param int $lcid UNIX Locale, wie in Tabelle languages hinterlegt
     * @return int id der gesuchten Sprache
     */
    public function getLangIdByUnix($lang){
        $this->loadByUnix($lang);
        return $this->getId();
    }

    /**
     * Gibt die interne Sprach ID anhand der übergebenen Sprache im spezifizierten Typ zurück
     * @param mixed $lang
     * @param unknown_type $lang
     */
    public function getLangId($lang, $type = self::LANG_TYPE_RFC5646) {
        $this->loadLang($lang, $type);
        return $this->getId();
    }

    /**
     * Gibt ein Language-Entity anhand der übergebenen Sprache im spezifizierten Typ zurück
     * @param mixed $lang
     * @param unknown_type $lang
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadLang($lang, $type = self::LANG_TYPE_RFC5646) {
        switch ($type) {
            case self::LANG_TYPE_ID:
                return $this->loadById($lang);
            case self::LANG_TYPE_LCID:
                return $this->loadByLcid($lang);
            case self::LANG_TYPE_RFC5646:
                return $this->loadByRfc5646($lang);
            case self::LANG_TYPE_UNIX:
                return $this->loadByUnix($lang);
            default:
                return $this->loadLang($lang, $this->getAutoDetectedType($lang));
        }
    }

    /**
     * Versucht anhand der übergebenen Sprache die Art der Sprachspezifikation zu bestimmen
     * @param string $lang
     * @return string
     */
    public function getAutoDetectedType($lang) {
        if(is_int($lang)) {
            return self::LANG_TYPE_LCID;
        }
        if(strpos($lang, '_') !== false) {
            return self::LANG_TYPE_UNIX;
        }
        return self::LANG_TYPE_RFC5646;
    }
}
