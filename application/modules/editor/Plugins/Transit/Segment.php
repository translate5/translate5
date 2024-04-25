<?php

/*
START LICENSE AND COPYRIGHT

This file is part of Translate5 Editor PHP Serverside

Copyright (c) 2014:
   Original Version: beo Gesellschaft für Sprachen und Technologie mbH;
   Changes and extensions: Marc Mittag, MittagQI - Quality Informatics;
   All rights reserved.

Contact: http://www.beo-doc.de/; http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

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

@copyright  beo Gesellschaft für Sprachen und Technologie mbH; Marc Mittag, MittagQI - Quality Informatics
@author     beo Gesellschaft für Sprachen und Technologie mbH; MittagQI - Quality Informatics
@license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
            with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)

END LICENSE AND COPYRIGHT
*/

/** #@+
 * @author Michael Herrnberger, beo GmbH; Marc Mittag - MittagQI
 * @package editor
 * @version 1.0
 */

class editor_Plugins_Transit_Segment
{
    private $text = "";

    private $data = "";

    private $dataRaw = "";

    private $id = false;

    private $isChanged = false;

    public static $status = [
        "\x02\xE9" => self::STATUS_NOT_TRANSLATED,
        "\x0A\xE9" => self::STATUS_TRANSLATED,
        "\x2A\xE9" => self::STATUS_TRANSLATED_JOIN_START,
        "\x4A\xE9" => self::STATUS_TRANSLATED_JOIN,
        "\x6A\xE9" => self::STATUS_TRANSLATED_JOIN_END,
        "\x08\xE9" => self::STATUS_PARTLY_TRANSLATED,
        "\x0C\xE9" => self::STATUS_SPELLCHECKED,
        "\x0E\xE9" => self::STATUS_CHECKED1,
        "\x0F\xE9" => self::STATUS_CHECKED2,
        "\x04\xE9" => self::STATUS_ALIGNMENT,
        "\x02\xEE" => self::STATUS_CREATED,
        "\x06\xE9" => self::STATUS_ALIGNMENT_CHECKED,
    ];

    public static $editby = [
        0 => "Unbekannt",
        13 => "Vorübersetzung",
        3 => "Fuzzy-Match (Benutzer)",
        2 => "Benutzer",
    ];

    public const STATUS_NOT_TRANSLATED = 0;

    public const STATUS_TRANSLATED = 1;

    public const STATUS_TRANSLATED_JOIN_START = 11;

    public const STATUS_TRANSLATED_JOIN = 12;

    public const STATUS_TRANSLATED_JOIN_END = 13;

    public const STATUS_PARTLY_TRANSLATED = 2;

    public const STATUS_SPELLCHECKED = 3;

    public const STATUS_CHECKED1 = 4;

    public const STATUS_CHECKED2 = 5;

    public const STATUS_ALIGNMENT = 6;

    public const STATUS_CREATED = 7;

    public const STATUS_ALIGNMENT_CHECKED = 8;

    public const STATUS_UNKNOWN = -1;

    public const STATUS_MATCHVALUE = "\xEB";

    public const STATUS_MATCHVALUE_VALUE_NOT_SET = '2-233-4-234-6-238-2-245'; //ascii representation of the returned status part of STATUS_MATCHVALUE, if encoded by this->getAsciiValOfString

    public const STATUS_MATCHVALUE_VALUE_SET_ZERO = '10-233-6-234-6-238-21-246-2-245'; //ascii representation of the returned status part of STATUS_MATCHVALUE, if encoded by this->getAsciiValOfString

    public const STATUS_EDITOR = "\xEA";

    public const EXTERNAL = "\xC0\xE8";

    public const PART_SPLIT = "(.\xEF)";

    public const PART_STATUS = "\x00\xEF";

    public const PART_INFO = "\x01\xEF";

    public const PART_REFMAT = "\x02\xEF";

    public const PART_UNKNOWN1 = "\x03\xEF"; // mögliche Werte: 30 00 und 31 00

    public const PART_LAST_EDIT_DATE = "\x04\xEF";

    public const PART_LAST_EDIT_TIME = "\x05\xEF";

    public const PART_LAST_EDIT_USER = "\x06\xEF";

    public const PART_FIRST_EDIT_DATE = "\x07\xEF";

    public const PART_FIRST_EDIT_TIME = "\x08\xEF";

    public const PART_FIRST_EDIT_USER = "\x09\xEF";

    public const PART_UNKNOWN2 = "\x10\xEF"; // mögliche Werte: 00 40

    public const PART_UNKNOWN3 = "\xA0\xEF"; // mögliche Werte: 01 40 und 02 40 usw.

    public const PART_REFMAT2 = "\xD2\xEF"; // möglicherweise weiteres RefMat

    public const PART_UNKNOWN4 = "\xD3\xEF"; // mögliche Werte: 00 40

    public const PART_ACCESS_STATUS = "\xD6\xEF"; // mögliche Werte: 20 40

    public const ACCESS_NO_REFMAT = "\x20\x40";

    public const ACCESS_NO_RESTRICTIONS = "\x00\x00";

    public const EDIT_FIRST = 1; // Zugriff auf den ersten Bearbeiter eines Segments, bzw Datum und Uhrzeit

    public const EDIT_LAST = 2; // Zugriff auf den letzten Bearbeiter eines Segments, bzw Datum und Uhrzeit

    public const EDIT_BY_PRETRANS = 1;

    public const EDIT_BY_USER = 3;

    public const EDIT_BY_USER_FUZZY = 2;

    public const EDIT_BY_UNKNOWN = 0;

    public const JOIN_NONE = 0;

    public const JOIN_START = 2;

    public const JOIN_RUN = 4;

    public const JOIN_END = 6;

    public function init($node_segment)
    {
        $this->text = $this->getInnerXML($node_segment);
        $this->data = "\x00\xEF" . editor_Plugins_Transit_File::enc2ucs($node_segment->getAttribute("Data"));

        $this->id = $node_segment->getAttribute("SegID");

        if ($this->id && ! empty($this->data)) {
            return $this->id;
        }

        return false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getJoinStatus()
    {
        $status = $this->getStatusRawCode();
        $join = ord($status);
        $join = floor($join / 16);

        return $join;
    }

    public function getInfo()
    {
        return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_INFO));
    }

    public function setInfo($infotext)
    {
        $this->addInfoPartIfNotExists();

        return $this->setPart(self::PART_INFO, editor_Plugins_Transit_File::enc2ucs($infotext), true);
    }

    public function getEditUser($whichUser)
    {
        if ($whichUser == self::EDIT_FIRST) {
            return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_FIRST_EDIT_USER));
        } elseif ($whichUser == self::EDIT_LAST) {
            return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_LAST_EDIT_USER));
        }

        return false;
    }

    public function setEditUser($whichUser, $username)
    {
        if (editor_Plugins_Transit_File::enc2ucs($username)) {
            if ($whichUser == self::EDIT_FIRST) {
                return $this->setPart(self::PART_FIRST_EDIT_USER, editor_Plugins_Transit_File::enc2ucs($username));
            } elseif ($whichUser == self::EDIT_LAST) {
                return $this->setPart(self::PART_LAST_EDIT_USER, editor_Plugins_Transit_File::enc2ucs($username));
            }
        }

        return false;
    }

    public function setEditDate($whichDate, $date)
    {
        if (! is_numeric($date) && strlen($date) != 8) {
            return false;
        }

        if (editor_Plugins_Transit_File::enc2ucs($date)) {
            if ($whichDate == self::EDIT_FIRST) {
                return $this->setPart(self::PART_FIRST_EDIT_DATE, editor_Plugins_Transit_File::enc2ucs($date));
            } elseif ($whichDate == self::EDIT_LAST) {
                return $this->setPart(self::PART_LAST_EDIT_DATE, editor_Plugins_Transit_File::enc2ucs($date));
            }
        }

        return false;
    }

    public function getEditDate($whichDate)
    {
        if ($whichDate == self::EDIT_FIRST) {
            return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_FIRST_EDIT_DATE));
        } elseif ($whichDate == self::EDIT_LAST) {
            return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_LAST_EDIT_DATE));
        }

        return false;
    }

    public function getEditTime($whichTime)
    {
        if ($whichTime == self::EDIT_FIRST) {
            return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_FIRST_EDIT_TIME));
        } elseif ($whichTime == self::EDIT_LAST) {
            return editor_Plugins_Transit_File::ucs2enc($this->getPart(self::PART_LAST_EDIT_TIME));
        }

        return false;
    }

    public function isChanged()
    {
        return $this->isChanged;
    }

    public function setStatus($newstatus)
    {
        $partdata = $this->getPart(self::PART_STATUS);

        if ($partdata) {
            $offset = 0;
            if (substr($partdata, 0, 2) == self::EXTERNAL) {
                $offset = 2;
            }
            $partdata = substr_replace($partdata, $this->isStatusCode($newstatus), $offset, 2);

            return $this->setPart(self::PART_STATUS, $partdata);
        }

        return false;
    }

    public function isStatusCode($statusvalue)
    {
        $keys = array_keys(self::$status);
        if (array_key_exists($statusvalue, $keys)) {
            return $keys[$statusvalue];
        }

        return false;
    }

    public static function getStatusName($statusvalue)
    {
        $values = array_values(self::$status);
        if (array_key_exists($statusvalue, $values)) {
            return $values[$statusvalue];
        }

        return "Status unknown!";
    }

    public function getStatusRawCode()
    {
        $partdata = $this->getPart(self::PART_STATUS);

        if ($partdata) {
            $status = substr($partdata, 0, 2);

            return $status;
        }

        return false;
    }

    public function getMatchValue()
    {
        $matchvalue = $this->getStatusPart(self::STATUS_MATCHVALUE);
        if ($matchvalue) {
            return ord($matchvalue);
        }

        return 0;
    }

    public function setMatchValue($value)
    {
        $partdata = $this->getPart(self::PART_STATUS);
        if ($partdata) {
            if ($this->getAsciiValOfString($partdata) === self::STATUS_MATCHVALUE_VALUE_NOT_SET) {
                $partdata = $this->getByteValOfAsciiRepresetentation(self::STATUS_MATCHVALUE_VALUE_SET_ZERO);
            }
            $max = strlen($partdata);
            for ($pos = 1; $pos < $max; $pos++) {
                if (substr($partdata, $pos, 1) == self::STATUS_MATCHVALUE) {
                    $this->setPart(self::PART_STATUS, substr_replace($partdata, chr($value), $pos - 1, 1));

                    return true;
                }
            }
            $this->setPart(self::PART_STATUS, $partdata . chr($value) . self::STATUS_MATCHVALUE);

            return true;
        }

        return false;
    }

    public function getStatusPart($status_part)
    {
        $partdata = $this->getPart(self::PART_STATUS);
        if ($partdata) {
            $max = strlen($partdata);
            for ($pos = 1; $pos < $max; $pos++) {
                if (substr($partdata, $pos, 1) == $status_part) {
                    return substr($partdata, $pos - 1, 1);
                }
            }
        }

        return false;
    }

    public function setStatusPart($status_part, $value)
    {
        $partdata = $this->getPart(self::PART_STATUS);
        if ($partdata) {
            $max = strlen($partdata);
            for ($pos = 1; $pos < $max; $pos++) {
                if (substr($partdata, $pos, 1) == $status_part) {
                    $this->setPart(self::PART_STATUS, substr_replace($partdata, chr($value), $pos - 1, 1));

                    return true;
                }
            }
            $this->setPart(self::PART_STATUS, $partdata . chr($value) . $status_part);

            return true;
        }

        return false;
    }

    private function getAsciiValOfString($string)
    {
        $asciiArr = [];
        for ($i = 0; $i < strlen($string); $i++) {
            $asciiArr[] = ord($string[$i]);
        }

        return implode('-', $asciiArr);
    }

    private function getByteValOfAsciiRepresetentation($string)
    {
        $byte = '';
        $arr = explode('-', $string);
        foreach ($arr as $value) {
            $byte .= chr($value);
        }

        return $byte;
    }

    public function getEditBy()
    {
        $editor = $this->getStatusPart(self::STATUS_EDITOR);
        if ($editor) {
            switch ($editor) {
                case "\x0D":
                    $status = self::EDIT_BY_PRETRANS;

                    break;
                case "\x0C":
                case "\x0B":
                case "\x0A":
                case "\x09":
                case "\x08":
                case "\x07":
                case "\x03":
                    $status = self::EDIT_BY_USER_FUZZY;

                    break;
                case "\x06":
                case "\x02":
                    $status = self::EDIT_BY_USER;

                    break;
                default:
                    $status = self::EDIT_BY_UNKNOWN;

                    throw new Zend_Exception("Unbekannter Status: " . bin2hex($status));

                    break;
            }

            return self::$editby[$status];
        }

        return false;
    }

    public function setEditBy($editby)
    {
        $this->setStatusPart(self::STATUS_EDITOR, $editby);
    }

    public function getStatus()
    {
        $status = $this->getStatusRawCode();
        if ($status) {
            if (isset(self::$status[$status])) {
                return self::$status[$status];
            } else {
                return self::STATUS_UNKNOWN;
            }
        }

        return false;
    }

    public function getSegmentNode()
    {
        return "";
    }

    public function getData()
    {
        return editor_Plugins_Transit_File::ucs2enc(substr($this->data, 2));
    }

    public function getInnerXML($node)
    {
        $innerXML = "";
        foreach ($node->childNodes as $childNode) {
            $innerXML .= $node->ownerDocument->saveXML($childNode);
        }

        return $innerXML;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($newtext)
    {
        $this->text = $newtext;
        $this->isChanged = true;

        return true;
    }

    public function getAccessStatus()
    {
        $accessStatus = $this->getPart(self::PART_ACCESS_STATUS);
        if (empty($accessStatus)) {
            return self::ACCESS_NO_RESTRICTIONS;
        }

        return $accessStatus;
    }

    private function getPart($partid)
    {
        $segment_parts = preg_split("/" . self::PART_SPLIT . "/", $this->data, -1, PREG_SPLIT_DELIM_CAPTURE);

        while (next($segment_parts)) {
            if (current($segment_parts) == $partid) {
                return next($segment_parts);
            }
        }

        return false;
    } // end of public function getPart

    /**
     * @param string $partid the "ID" for the part. Normaly some fancy unicode.
     * @param string $newpartdata the new data for this part.
     * @param bool $append if true $newpartdata will be append to the existing partdata.
     *      Default will replace the existing partdata.
     *
     * @return boolean
     */
    private function setPart($partid, $newpartdata, $append = false)
    {
        $segment_parts = preg_split("/" . self::PART_SPLIT . "/", $this->data, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (empty($segment_parts)) {
            return false;
        }

        // detect wich part of the data is the desired one
        $tempKey = false;
        foreach ($segment_parts as $key => $part) {
            if ($part == $partid) {
                $tempKey = $key;

                break;
            }
        }

        // if desired part does nocht yet exist, create a new part with the given $partid
        if (! $tempKey) {
            $tempKey = count($segment_parts);
            $segment_parts[$tempKey] = $partid;
        }

        // if new data sholud be append to the existing data, apppend
        if ($append == true) {
            $tempPart = $this->getPart($partid);
            if (! empty($tempPart)) {
                $newpartdata = $tempPart . editor_Plugins_Transit_File::enc2ucs(" ") . $newpartdata;
            }
        }

        // write the new data into the desired part
        $segment_parts[$tempKey + 1] = $newpartdata;
        $this->data = implode("", $segment_parts);
        $this->isChanged = true;

        return true;
    }

    /**
     * @return boolean
     */
    private function addInfoPartIfNotExists()
    {
        if (strpos($this->data, self::PART_INFO) !== false) {
            return false;
        }
        $segment_parts = preg_split("/" . self::PART_SPLIT . "/", $this->data, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (strpos($this->data, self::PART_STATUS) !== false) {
            array_splice($segment_parts, 3, 0, self::PART_INFO);
            array_splice($segment_parts, 4, 0, '');
        } else {
            array_splice($segment_parts, 1, 0, self::PART_INFO);
            array_splice($segment_parts, 2, 0, '');
        }

        $this->data = implode("", $segment_parts);
        $this->isChanged = true;

        return true;
    }
}
