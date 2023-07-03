<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\ZfExtended\Controller\Response\Header;

/**
 * Class representing a UTF-8-text based resource file embedded into a bconf
 * These are usually XML, JSON or plain text files
 */
abstract class editor_Plugins_Okapi_Bconf_ResourceFile {

    /**
     * Little helper to create a unique hash for a resource
     * Nothing sophisticated neccessary, this is not security relevant, just enables re-identification
     * @param string $content
     * @return string
     */
    public static function createHash(string $content) : string {
        return md5($content);
    }

    /**
     * @var string
     */
    protected string $content;

    /**
     * @var string
     */
    protected string $path;

    /**
     * Just a default, may has to be overwritten/evaluated in extending classes
     * @var string
     */
    protected string $mime = 'text/plain';

    /**
     * Must be filled when validation fails
     * @var string
     */
    protected string $validationError;

    /**
     * @var bool
     */
    protected bool $doDebug;

    /**
     * @param string $path
     * @param string|null $content
     * @throws ZfExtended_Exception
     */
    public function __construct(string $path, string $content=NULL){
        $this->path = $path;
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfValidation');
        if($content === NULL){
            $this->content = @file_get_contents($this->getPath());
            if(!$this->content || strlen($this->content) < 1){
                // DEBUG
                if($this->doDebug){ error_log('RESOURCE FILE can only be instantiated for an existing file ('.$this->path.') with contents'); }
                throw new ZfExtended_Exception(get_class($this).' can only be instantiated for an existing file ('.$this->path.') with contents');
            }
        } else {
            $this->content = $content;
        }
    }

    /**
     * @return string
     */
    public function getContent() : string {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getMimeType() : string {
        return $this->mime;
    }

    /**
     * @return int
     */
    public function getContentLength() : int {
        return mb_strlen($this->getContent(), 'UTF-8');
    }

    /**
     * @return string
     */
    public function getPath() : string {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getHash() : string {
        return self::createHash($this->getContent());
    }

    /**
     * @return string
     */
    public function getFile() : string {
        return basename($this->path);
    }

    /**
     * writes our content to our related file
     */
    public function flush() {
        file_put_contents($this->path, $this->getContent());
    }

    /**
     * Generates download-headers and echos the contents
     * @param string $downloadFilename
     */
    public function download(string $downloadFilename) {
        Header::sendDownload(
            $downloadFilename,
            $this->getMimeType(),
            'no-store',
            $this->getContentLength()
        );
        echo $this->getContent();
    }

    /**
     * Generates the output for get actions
     */
    public function output() {
        Header::sendDownload(
            null,
            $this->getMimeType(),
            'no-store',
            $this->getContentLength()
        );
        echo rtrim($this->getContent());
    }

    /**
     * Validates the resource file
     * @param bool $forImport: if set, this is an import-process
     * @return bool
     */
    abstract public function validate(bool $forImport=false) : bool;

    /**
     * Retrieves the error that caused the file to be invalid
     * @return string
     */
    public function getValidationError() : string {
        return $this->validationError;
    }
}