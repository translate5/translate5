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

namespace MittagQI\Translate5\Tools;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use RuntimeException;

/**
 * Factory class for creating filesystem (local, FTP, SFTP, AWS S3, etc. pp) access over the unified flysystem API
 */
class FlysystemFactory {
    const TYPE_LOCAL = 'local';
    const TYPE_SFTP = 'sftp';

    /**
     * Factory method to create one of the available filesystems
     * @param string $type one of the implemented self::TYPE_* types
     * @param object $config a configuration object as needed by the creation function for the given type
     * @return Filesystem
     */
    public static function create(string $type, object $config): Filesystem {
        return match ($type) {
            self::TYPE_LOCAL => self::createLocal($config),
            self::TYPE_SFTP => self::createSftp($config),
            default => throw new RuntimeException('The given flysystem adapter type ' . $type . ' is not yet supported in the factory. Please implement it.'),
        };
    }

    /**
     * Creates a filesystem object with the SFTP adapter.
     * Options to be given in the $options array:
     *  rootpath (optional, defaults to /) - the root directory to be used on the SFTP server
     *  host (required)
     *  username (required)
     *  password (optional, default: null) set to null if privateKey is used
     *  privateKey (optional, default: null) can be used instead of password, set to null if password is set, must be a path: '/path/to/my/private_key'
     *  passphrase (optional, default: null), set to null if privateKey is not used or has no passphrase
     *  port (optional, default: 22)
     *  useAgent (optional, default: false)
     *  timeout (optional, default: 10)
     *  maxTries (optional, default: 4)
     *  hostFingerprint (optional, default: null),
     *  connectivityChecker (must be an implementation of 'League\Flysystem\PhpseclibV2\ConnectivityChecker' to check if a connection can be established (optional, omit if you don't need some special handling for setting reliable connections)
     *
     * @param object $options
     * @return Filesystem
     */
    public static function createSftp(object $options): Filesystem {
        return new Filesystem(new SftpAdapter(
            SftpConnectionProvider::fromArray((array) $options),
            $options->rootpath ?? '/'
//            PortableVisibilityConverter::fromArray([
//                'file' => [
//                    'public' => 0640,
//                    'private' => 0604,
//                ],
//                'dir' => [
//                    'public' => 0740,
//                    'private' => 7604,
//                ],
//            ])
        ));
    }

    /**
     * Creates a filesystem object with the local filesystem adapter.
     * Options to be given in the $options array:
     *  location (required) - the fileystem location / path to be started from
     *  visibility (optional, default: null) customize how visibility is converted to unix permissions, must be an instance of League\Flysystem\UnixVisibility\PortableVisibilityConverter
     *  writeFlags (optional, default: LOCK_EX)
     *  linkHandling (optional, default: LocalFilesystemAdapter::DISALLOW_LINKS) How to deal with links, either DISALLOW_LINKS or SKIP_LINKS; Disallowing them causes exceptions when encountered LocalFilesystemAdapter::DISALLOW_LINKS
     *  mimeTypeDetector (optional, default: null) must be an instance of League\MimeTypeDetection\MimeTypeDetector
     *
     * @param object $options
     * @return Filesystem
     */
    public static function createLocal(object $options): Filesystem
    {
        return new Filesystem(new LocalFilesystemAdapter(
            $options->location ?? DIRECTORY_SEPARATOR, //Whats the Windows Root???
            $options->visibility ?? null,
            $options->writeFlags ?? LOCK_EX,
            $options->linkHandling ?? LocalFilesystemAdapter::DISALLOW_LINKS,
            $options->mimeTypeDetector ?? null,
        ));
    }
}
