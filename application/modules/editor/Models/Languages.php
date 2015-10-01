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
 * @method int getId() getId()
 */
class editor_Models_Languages extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Languages';

    const LANG_TYPE_ID = 'id';
    const LANG_TYPE_RFC5646 = 'rfc5646';
    const LANG_TYPE_LCID = 'lcid';

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
        $this->row = $this->db->fetchRow($s);
        if(empty($this->row)){
            $this->notFound('#by'.ucfirst($field), $lang);
        }
        return $this->row;
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
        return self::LANG_TYPE_RFC5646;
    }
}
