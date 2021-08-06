<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
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
 * abstract base class to create QM SubSegment Images
 */
abstract class editor_ImageTag_QmSubSegment extends editor_ImageTag {
    const IMAGE_PADDING_WIDTH = 15;
    const IMAGE_PADDING_HEIGHT = 4;
    const TEXT_PADDING_RIGHT = 5;
    
    /**
     * Enthält die Image-Texte aller bereits von $this->save der aktuellen Objektinstanz gespeicherten oder auf Existenz
     * geprüften Images als Schlüssel
     * @var array
     */
    protected $_existingImages = array();
    /**
     * Imagick Main Instance
     * @var Imagick
     */
    protected $image;
    /**
     * Imagick Color / Font Settings Instance
     * @var ImagickDraw
     */
    protected $draw;
    /**
     * @var integer
     */
    protected $widthImage;
    protected $widthText;
    protected $heightText;
    protected $heightImage;
    /**
     * @var string
     * Unterhalb diesen Pfades speichert die save methode die Grafiken
     * - wird im Konstruktor mit $config->runtimeOptions->dir->tagImagesBasePath
     *   befüllt
     */
    protected $basePath;
    /**
     * @var Zend_Config
     */
    protected $_config;
    /**
     * Tagdefinitionen aus der application.ini
     * @var Zend_Config_Ini
     */
    protected $_tagDef;
    /**
     * @var string
     */
    protected $_filename;
    
    public function __construct() {
        $this->existsGd();
        $this->_config = Zend_Registry::get('config');
        $this->_tagDef = $this->_config->runtimeOptions->imageTag;
        $parts = explode('/', $this->_config->runtimeOptions->dir->tagImagesBasePath);
        $path = array(APPLICATION_PATH, '..', 'public');
        $path = array_merge($path, $parts);
        $path = join(DIRECTORY_SEPARATOR, $path);
        $this->setSaveBasePath($path);
        
        if(!file_exists($this->_tagDef->fontFilePath)) {
            throw new ZfExtended_Exception(get_class($this).': configured font path can not be found! Font: '.$this->_tagDef->fontFilePath);
        }
        
        $this->_tagDef = new Zend_Config(array(), 1);
        //preset the internal config with the default values
        $this->_tagDef->merge($this->_config->runtimeOptions->imageTag);
        //override the custimized values in the clone
        $this->_tagDef->merge($this->_config->runtimeOptions->imageTags->qmSubSegment);
    }
    
    /**
     * creates the tag with the given tag, saves in memory
     * @param string $text
     * @return self
     */
    public function create($text) {
        $this->text = $text;
        $this->computeMetricsAndInitImage();
        $this->putTextOnImage();
        return $this;
    }
    
    protected function computeMetricsAndInitImage() {
        $box = $this->calculateTextBox();
        $imageWidth = $box['width'] + $this->_tagDef->horizStart + $this->_tagDef->paddingRight;
        $this->image = imagecreate($imageWidth, $this->_tagDef->height);
    }
    
    /**
     * Gibt Maße der durch den aktuell in $this->text gesetzten Text erzeugten Box zurück
     *
     * @return array array(
     *       "left" => integer,
     *       "top" => integer,
     *       "width" => integer,
     *       "height" => integer,
     *       "box" => integer
     *   )
     */
    protected function calculateTextBox() {
        $rect = imagettfbbox(
            $this->_tagDef->fontSize,
            0,
            $this->_tagDef->fontFilePath,
            $this->text
            );
        $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
        $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));
        
        return array(
            "left" => abs($minX) - 1,
            "top" => abs($minY) - 1,
            "width" => $maxX - $minX,
            "height" => $maxY - $minY,
            "box" => $rect
        );
    }
    
    protected function putTextOnImage() {
        ImageColorAllocate(
            $this->image,
            $this->_tagDef->backColor->R,
            $this->_tagDef->backColor->G,
            $this->_tagDef->backColor->B
            );
        $col = ImageColorAllocate(
            $this->image,
            $this->_tagDef->fontColor->R,
            $this->_tagDef->fontColor->G,
            $this->_tagDef->fontColor->B
            );
        ImageTTFText(
            $this->image,
            $this->_tagDef->fontSize,
            0,
            $this->_tagDef->horizStart,
            $this->_tagDef->vertStart,
            $col,
            $this->_tagDef->fontFilePath,
            $this->text
            );
    }
    
    /**
     * setzt den basepath unter welchem die generierten Grafiken gespeichert werden
     * @param string $path
     */
    public function setSaveBasePath($path) {
        $this->basePath = $path;
    }
    
    /**
     * speichert die generierte Grafik unterhalb von $this->basePath
     * PNG Suffix wird automatisch an den übergebenen Dateinamen angehängt
     * Wird der Dateiname weggelassen, wird md5($text) als Dateinamen genommen
     * @param string $filename [optional]
     * @return self
     */
    public function save($filename = null) {
        $this->checkBasePath();
        if (!empty($this->_filename)) {
            $filename = $this->_filename;
        } elseif (empty($filename)) {
            $filename = md5($this->text);
        }
        imagepng($this->image, $this->generateFilepath($filename), 9);
        return $this;
    }
    
    protected function checkBasePath() {
        if (empty($this->basePath)) {
            throw new Zend_Exception('ImageTag::$basePath is not set; Path: ' . $this->basePath);
        }
        if (!is_dir($this->basePath)) {
            throw new Zend_Exception('ImageTag::$basePath is no directory; Path: ' . $this->basePath);
        }
    }
    
    /**
     * genereates filepath from basepath and given filename, adds png suffix
     * @param string $filename
     * @return string
     */
    protected function generateFilepath($filename) {
        return $this->basePath . DIRECTORY_SEPARATOR . $filename . '.png';
    }
    
    /**
     * @throws Zend_Exception
     */
    protected function existsGd() {
        if (!function_exists('gd_info')) {
            throw new Zend_Exception('GD ist nicht installiert');
        }
    }
}