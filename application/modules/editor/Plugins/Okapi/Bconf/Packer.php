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

namespace MittagQI\Translate5\Plugins\Okapi\Bconf;

use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Filter\FilterEntity;
use MittagQI\Translate5\Plugins\Okapi\Bconf\Segmentation\Srx;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use Throwable;
use ZfExtended_Debug;
use ZfExtended_Exception;

/**
 * Packs/Assembles a bconf
 * Algorithmically a copy of the original JAVA implementation
 */
final class Packer
{
    private BconfEntity $bconf;

    private string $folder;

    private ?RandomAccessFile $raf;

    private bool $doDebug;

    /**
     * @throws OkapiException
     */
    public function __construct(BconfEntity $bconf)
    {
        $this->bconf = $bconf;
        $this->folder = $this->bconf->getDataDirectory();
        $this->doDebug = ZfExtended_Debug::hasLevel('plugin', 'OkapiBconfPackUnpack');
    }

    /**
     * Creates a BCONF usable for Extraction/Import
     */
    public function createExtraction(bool $isOutdatedRepack, bool $isSystemDefault = false): void
    {
        $this->createBConf($isOutdatedRepack, $isSystemDefault);
    }

    /**
     * Creates a BCONF usable for Merging/Export
     */
    public function createMerging(): void
    {
        $this->createBConf(false, false, true);
    }

    /**
     * Creates a BCONF
     * @throws BconfInvalidException
     * @throws Throwable
     * @throws ZfExtended_Exception
     * @throws OkapiException
     */
    private function createBConf(bool $isOutdatedRepack, bool $isSystemDefault = false, bool $isExport = false): void
    {
        // we must catch all exceptions of the RandomAccessFile to be able to release the file-pointer properly!
        try {
            // DEBUG
            if ($this->doDebug) {
                error_log('PACK BCONF: ' . $this->bconf->getName());
            }

            // so we can access all files in the bconf's data-dir with file name only
            $this->raf = new RandomAccessFile($this->bconf->getPath($isExport), 'wb');

            $this->raf->writeUTF(BconfEntity::SIGNATURE, false);
            $this->raf->writeInt(BconfEntity::VERSION);
            // TODO BCONF: currently plugins are not supported
            $this->raf->writeInt(BconfEntity::NUM_PLUGINS);

            if ($isExport) {
                $pipeline = editor_Plugins_Okapi_Init::getDataDir() . 'pipeline/translate5-merging.pln';
                $pipeline = new Pipeline($pipeline, null, (int) $this->bconf->getId());
            } else {
                $content = $this->bconf->getContent();
                $pipeline = $this->bconf->getPipeline();
                $refId = 0;
                if ($xsltFile = $content->getXsltFile()) {
                    $this->harvestReferencedFile(++$refId, $xsltFile, $isOutdatedRepack);
                }
                $this->harvestReferencedFile(++$refId, $content->getSrxFile('source'), $isOutdatedRepack);
                $this->harvestReferencedFile(++$refId, $content->getSrxFile('target'), $isOutdatedRepack);
            }
            // Last ID=-1 to mark no more references
            $this->raf->writeInt(-1);
            $this->raf->writeInt(1);
            $pipelineContent = $pipeline->getContent();
            if (! $isExport && str_contains($pipelineContent, '<rainbowPipeline version="1">')) {
                $pipelineContent = str_replace(
                    '<rainbowPipeline version="1">',
                    '<rainbowPipeline version="1" ' . Pipeline::BCONF_VERSION_ATTR . '="' . editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX . '">',
                    $pipelineContent
                );
            }
            $this->raf->writeUTF($pipelineContent, false);
            // process filters & extension mapping
            $customIdentifiers = [];
            foreach ($this->bconf->getCustomFilterData() as $filterData) {
                $customIdentifiers[] = Filters::createIdentifier($filterData['okapiType'], $filterData['okapiId']);
            }
            // DEBUG
            if ($this->doDebug) {
                error_log('PACKED CUSTOM FILTERS: ' . "\n" . implode(', ', $customIdentifiers));
            }

            // instantiate the extension mapping and evaluate the additional default okapi and
            // translate5 filter files (this needs to know the "real" custom filters
            $extensionMapping = $this->bconf->getExtensionMapping();

            // special: for system default bconfs we add all extension-mapping entries defined for translate5
            // that are not yet in the mapping. This enables updating mappings to the default system bconf
            // - which will be the template for most of the customized ones
            if ($isSystemDefault) {
                $extensionMapping->complementTranslate5Extensions();
            }
            $extensionMapData = $extensionMapping->getMapForPacking($customIdentifiers);
            // retrieves an array of paths !
            $defaultFilterFiles = $extensionMapping->getOkapiDefaultFprmsForPacking($customIdentifiers);

            // DEBUG
            if ($this->doDebug) {
                error_log('PACKED DEFAULT FILTERS: ' . "\n" . print_r($defaultFilterFiles, 1));
                error_log('PACKED EXTENSION MAPPING: ' . "\n" . print_r($extensionMapData, 1));
            }
            $numAllEmbeddedFilters = count($customIdentifiers) + count($defaultFilterFiles);
            // write number of embedded filters
            $this->raf->writeInt($numAllEmbeddedFilters);
            foreach ($customIdentifiers as $identifier) {
                // we are already in the bconf's dir, so we can reference custom filters by filename only
                $this->writeFprm(
                    $identifier,
                    $this->folder . '/' . basename($identifier . '.' . FilterEntity::EXTENSION)
                );
            }
            foreach ($defaultFilterFiles as $identifier => $path) {
                // The static default filters will be added with explicit settings
                // These are either OKAPI defaults or translate5 adjusted defaults
                $this->writeFprm($identifier, $path);
            }
            // write the adjusted extension map
            $countLines = count($extensionMapData);
            $extMapBinary = ''; // we'll build up the binary format in memory instead of wirting every line itself to file
            foreach ($extensionMapData as $lineData) {
                $extMapBinary .= $this->raf::toUTF($lineData[0]);
                $extMapBinary .= $this->raf::toUTF($lineData[1]);
            }
            $this->raf->writeInt($countLines);
            $this->raf->fwrite($extMapBinary);

            // explicitly close file-pointer
            $this->raf = null;
        } catch (Throwable $e) {
            $this->raf = null;

            throw $e;
        }
    }

    private function writeFprm(string $identifier, string $path): void
    {
        $this->raf->writeUTF($identifier, false);
        // QUIRK: Need additional null byte. Where does it come from in Java?
        $this->raf->writeUTF(file_get_contents($path), false);
    }

    /**
     * @throws BconfInvalidException
     */
    private function harvestReferencedFile(int $id, string $fileName, bool $isOutdatedRepack): void
    {
        $fileName = basename($fileName); // security!
        $this->raf->writeInt($id);
        $this->raf->writeUTF($fileName, false);

        if ($fileName == '') {
            // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf.
            // En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we write 2 32bit Ints here
            $this->raf->writeInt(0);
            $this->raf->writeInt(0);

            return;
        }
        // check, if a SRX is a T5 default SRX and exchange it with the current version if we are in the process of
        // repacking. This will be a permanent change since the physical file is overwritten
        // and it happens without knowing/caring if this is the source/target bconf
        if ($isOutdatedRepack && pathinfo($fileName, PATHINFO_EXTENSION) === Srx::EXTENSION) {
            Segmentation::instance()->onRepack($this->folder . '/' . $fileName);
        }
        $fileSize = filesize($this->folder . '/' . $fileName);
        if ($fileSize === false) {
            throw new BconfInvalidException('Unable to open file ' . $fileName);
        }
        // QUIRK: this value is encoded as BIG ENDIAN long long in the bconf.
        // En/Decoding of 64 byte values creates Exceptions on 32bit OS, so we write 2 32bit Ints here
        // (limiting the encodable size to 4GB...)
        $this->raf->writeInt(0);
        $this->raf->writeInt($fileSize);
        if ($fileSize > 0) {
            $resource = fopen($this->folder . '/' . $fileName, 'rb');
            // can not really happen in normal operation but who knows
            if ($resource === false) {
                throw new BconfInvalidException('Unable to open file ' . $fileName);
            }
            $this->raf->fwrite(fread($resource, $fileSize));
            fclose($resource);
        }
    }
}
