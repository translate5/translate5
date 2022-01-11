<?php

const SIGNATURE = "batchConf";
const VERSION = 2;
$bconf = 'D:\okapi\import\okapi.bconf';
        
$filesize = filesize($bconf);
// open file for reading in binary mode
$fp = fopen($bconf, 'rb');
// read the entire file into a binary string
$binary = fread($fp, $filesize);
$currentPos=0;

$version = readInt($binary,$currentPos+19);
$currentPos=$currentPos+4;

//=== Section 1: plug-ins
if ($version > 1) { // Remain compatible with v.1 bconf files
    $pluginsDir = 'D:\okapi\import\output';

				$numPlugins = readInt($binary,$currentPos+19);
                $currentPos=$currentPos+4;
                
				for ($i = 0; $i < $numPlugins; $i++) {
                    String relPath = raf.readUTF();
					raf.readInt(); // Skip ID
					raf.readUTF(); // Skip original full filename
					long size = raf.readLong();
					String path = Util.fixPath(outputDir + relPath);
					createReferencedFile(raf, size, path);
				}

				PluginsManager pm = new PluginsManager();
				try {
                    pm.discover(new File(pluginsDir), true);
                    pipelineWrapper.addFromPlugins(pm);
                } finally {
                    pm.releaseClassLoader();
                }
			}

function readInt(string $data, int $potion) {
        
         $ch1 = $data[$potion];
         $ch2 = $data[++$potion];
         $ch3 = $data[++$potion];
         $ch4 = $data[++$potion];
         
         $intValue =  unpack('N',$ch1.$ch2.$ch3.$ch4);
         return $intValue;

    }