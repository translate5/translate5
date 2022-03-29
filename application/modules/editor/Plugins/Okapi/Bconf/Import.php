<?php
/*
START LICENSE AND COPYRIGHT

This file is part of translate5

Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
@author	 MittagQI - Quality Informatics
@license	GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/



class editor_Plugins_Okapi_Bconf_Import
{
	protected $util;
	/**
	 * Import bconf
	 * Extracts the parts of a given bconf file in the order
	 * 1) plug-ins
	 * 2) references data
	 * 3) pipeline
	 * 4) filter configurations
	 * 5) extensions -> filter configuration id mapping
	 *
	 * @param string $bconfFile
	 * @param string $outputDir
	 */
	public function importBconf($bconfFile, $outputDir)
	{
		//TODO: sanity check for params, at least dir_exists, file exists etc
		$outputDir = realpath($outputDir);
		if (!$outputDir || !is_writable($outputDir)) {
			return false;
		}
        $content = [
            'refs' => [],
            'fprm' => [],
        ];

/*	public void installConfiguration (
			String configPath,
			String outputDir,
			PipelineWrapper pipelineWrapper,
			Map<String, String> stepParamOverrides,
			Map<String, FilterConfigOverride> filterParamOverrides)
	{
		if (stepParamOverrides == null) {
			throw new IllegalArgumentException("stepParamOverrides must not be null");
		}
		if (filterParamOverrides == null) {
			throw new IllegalArgumentException("filterParamOverrides must not be null");
		}
*/			$raf = new editor_Plugins_Okapi_Bconf_RandomAccessFile($bconfFile['tmp_name'], "rb");
			$sig = $raf->readUTF(); // signature
			if (!$raf::SIGNATURE === $sig) {
				throw new OkapiIOException("Invalid file format.");
			}
			$version = $raf->readInt(); // Version info
			if (!($version >= 1 && $version <= $raf::VERSION)) {
				throw new OkapiIOException("Invalid version.");
			}
			//TODO make dir if not exists
			//Util.createDirectories(outputDir+File.separator);

			//=== Section 1: plug-ins
			if ($version > 1) { // Remain compatible with v.1 bconf files
				$pluginsDir = $outputDir;

				$numPlugins = $raf->readInt();
				for ($i = 0; $i < $numPlugins; $i++) {
					$relPath = $raf->readUTF();
					$raf->readInt(); // Skip ID
					$raf->readUTF(); // Skip original full filename
					$size = $raf->readLong();
					$path = "$outputDir/$relPath";
					$this->createReferencedFile($raf, $size, $path);
				}

/*				PluginsManager pm = new PluginsManager();
				try {
					pm.discover(new File(pluginsDir), true);
					pipelineWrapper.addFromPlugins(pm);
				} finally {
					pm.releaseClassLoader();
				}
*/
			}

			//=== Section 2: references data

			// Build a lookup table to the references
			$refMap = [];
			$refNames = [];
			$id = $raf->readInt(); // First ID or end of section marker
			$pos = $raf->ftell();
			while ($id != -1) {
				$filename = $refNames[] = $raf->readUTF(); // Skip filename
				// Add the entry in the lookup table
				$refMap[$id] = $pos;
				// Skip over the data to move to the next reference
				$size = $raf->readLong();
				if ($size > 0) {
					$path = "$outputDir/$filename";
					$this->createReferencedFile($raf, $size, $path);

					//$raf->fseek($raf->ftell() + $size);
				}
				// Then get the information for next entry
				$id = $raf->readInt(); // ID
				$pos = $raf->ftell(); // Position
			}

			//=== Section 3 : the pipeline itself
			$xmlWordCount = $raf->readInt();
			$pipelineXml = '';
			for ($i=0; $i<$xmlWordCount; $i++) {
				$pipelineXml .= $raf->readUTF();
			}

			$startFilterConfigs = $raf->ftell();
			// Read the pipeline and instantiate the steps
			$pipeline = [
				'steps' => [], // FIXME needed for plugin support, do  pipelineWrapper.getAvailableSteps()
				'xml' => $pipelineXml
			];
			// reads in the given path
            $content['refs'] = $refNames;
			$this->parsePipeline($pipeline, $refNames, $outputDir);
			//validate the step param overrides
			//checkStepParamOverrides(pipeline.getSteps(), stepParamOverrides);
/*
			$id = 0;
			// Go through each step of the pipeline
			foreach ( $pipeline['steps'] as $step ) {
				// Get the parameters for that step
				$params = step.getParameters();
				//if ( params == null ) continue;
				//override the params if requested

				String overrideParams = stepParamOverrides.get(step.getClass().getName());
				if($overrideParams) {	
					if(params instanceof StringParameters) {
						//merge with existing string params
						array_merge($params, $overrideParams);
					} else {
						//override existing params
						params.fromString(overrideParams);
					}
				}
				// Get all methods for the parameters object
				Method[] methods = params.getClass().getMethods();
				// Look for references
				for ( Method m : methods ) {
					if ( Modifier.isPublic(m.getModifiers() ) && m.isAnnotationPresent(ReferenceParameter.class)) {
						// Update the references to point to the new location
						// Read the reference content

						pos = refMap.get(++id);
						$raf->fseek(pos);
						String filename = $raf->readUTF();
						long size = $raf->readLong();
						String path = outputDir + File.separator + filename;

						if (!Util.isEmpty(filename) && createReferencedFile(raf, size, path)) {
							String setMethodName = "set"+m.getName().substring(3);
							Method setMethod = params.getClass().getMethod(setMethodName, String.class);
							setMethod.invoke(params, path);
						}
//						byte[] buffer = new byte[MAXBUFFERSIZE];
//						
//						pos = refMap.get(++id);
//						$raf->fseek(pos);
//						String filename = $raf->readUTF();
//						long size = $raf->readLong(); // Read the size
//						// Save the data to a file
//						if ( !Util.isEmpty(filename) ) {
//							String path = outputDir + File.separator + filename;
//							FileOutputStream fos = new FileOutputStream(path);
//							int toRead = (int)Math.min(size, MAXBUFFERSIZE);
//							int bytesRead = $raf->read(buffer, 0, toRead);
//							while ( bytesRead > 0 ) {
//								fos.write(buffer, 0, bytesRead);
//								size -= bytesRead;
//								if ( size <= 0 ) break;
//								toRead = (int)Math.min(size, MAXBUFFERSIZE);
//								bytesRead = $raf->read(buffer, 0, toRead);
//							}
//							fos.close();
//							// Update the reference in the parameters to point to the saved file
//							// Test changing the value
//							String setMethodName = "set"+m.getName().substring(3);
//							Method setMethod = params.getClass().getMethod(setMethodName, String.class);
//							setMethod.invoke(params, path);
//						}
					}
				}
			}
*/
			// Write out the pipeline file
			$path = "$outputDir/pipeline.pln";
			file_put_contents($path, $pipeline['xml']);

			//=== Section 4 : the filter configurations

			$raf->fseek($startFilterConfigs);
			// Get the number of filter configurations
			$count = $raf->readInt();
            
			// Read each one
			for ($i=0; $i<$count; $i++) {
				$okapiId = $content['fprm'][] = $raf->readUTF();
				$data = $raf->readUTF();
				// And create the parameters file
				file_put_contents("$outputDir/$okapiId.fprm", $data);
			}

			// Write out any custom filter config overrides
			$customIdCount = 1;
/*			Map<String, String> extOverrides = new HashMap<>();

			for (Map.Entry<String, FilterConfigOverride> e : filterParamOverrides.entrySet()) {
				if (e.getValue().getConfigData() != null) {
					// Generate a custom classname
					String overrideOkapiId = e.getValue().getFilterName() + "@custom_" + customIdCount++;
					writeConfig(outputDir, overrideOkapiId, e.getValue().getConfigData());
					extOverrides.put(e.getKey(), overrideOkapiId);
				}
				else {
					// Use default configuration of another named filter - for example, when remapping
					// .xml to okf_xmlstream-dita, or .myfile to okf_openxml.
					extOverrides.put(e.getKey(), e.getValue().getFilterName());
				}
			}
*/
			//=== Section 5: the extensions -> filter configuration id mapping

			// Get the number of mappings
			$path = $outputDir . '/' . "extensions-mapping.txt";
			{
				$write = '';
				$extMap = $this->readExtensionMap($raf);
				// Apply the overrides
//				extMap.putAll(extOverrides);
				foreach ($extMap as $ext => $okapiId) {
					$write .= $ext . "\t" . $okapiId . PHP_EOL;
				}
				file_put_contents($path, $write);
			}
            file_put_contents("$outputDir/content.json", json_encode($content, JSON_PRETTY_PRINT));
	}

	private function writeConfig(String $outputDir, String $okapiId, String $data) : string {
		$path = $outputDir + "/" + $okapiId + ".fprm";
//		try (PrintWriter pw = new PrintWriter(path, "UTF-8")) {
			file_put_contents($path, $data);
//		}
//		return path;
	}

	private function readExtensionMap($raf) : array {
		$map = array();
		$count = $raf->readInt();
		for (  $i=0; $i<$count; $i++ ) {
			$ext  = $raf->readUTF();
            $okapiId = $raf->readUTF();
			$map[$ext] = $okapiId;
		}
		return $map;
	}
/*
	private void checkStepParamOverrides(List<IPipelineStep> steps,
			Map<String, String> stepParamOverrides) {
		for(String className : stepParamOverrides.keySet()) {
			if(!contains(steps, className)) {
				throw new IllegalArgumentException("Step specified in override step cofniguration:"+className+", does not exist in pipeline.");
			}
		}
	}
*//*
	/**
	 * @param steps
	 * @param className
	 * @return
	 * /
	private boolean contains(List<IPipelineStep> steps, String className) {
		for(IPipelineStep step : steps) {
			if(step.getClass().getName().equals(className)) {
				return true;
			}
		}
		return false;
	}

	private void harvestReferencedFile (DataOutputStream dos,
		int id,
		String refPath)
		throws IOException
	{
		FileInputStream fis = null;
		try {
			dos.writeInt(id);
			String filename = Util.getFilename(refPath, true);
			dos.writeUTF(filename);

			// Deal with empty references
			if ( Util.isEmpty(refPath) ) {
				dos.writeLong(0); // size = 0
				return;
			}
			// Else: copy the content of the referenced file

			// Write the size of the file
			File file = new File(refPath);
			long size = file.length();
			dos.writeLong(size);

			// Write the content
			if ( size > 0 )  {
				fis = new FileInputStream(refPath);
				int bufferSize = Math.min(fis.available(), MAXBUFFERSIZE);
				byte[] buffer = new byte[bufferSize];
				int bytesRead = fis.read(buffer, 0, bufferSize);
				while ( bytesRead > 0 ) {
					dos.write(buffer, 0, bufferSize);
					bufferSize = Math.min(fis.available(), MAXBUFFERSIZE);
					bytesRead = fis.read(buffer, 0, bufferSize);
				}
			}
		}
		finally {
			if ( fis != null ) {
				fis.close();
			}
		}
	}

	private void writeLongString (DataOutputStream dos,
		String data)
		throws IOException
	{
		int r = (data.length() % MAXBLOCKLEN);
		int n = (data.length() / MAXBLOCKLEN);
		int count = n + ((r > 0) ? 1 : 0);

		dos.writeInt(count); // Number of blocks
		int pos = 0;

		// Write the full blocks
		for ( int i=0; i<n; i++ ) {
			dos.writeUTF(data.substring(pos, pos+MAXBLOCKLEN));
			pos += MAXBLOCKLEN;
		}
		// Write the remaining text
		if ( r > 0 ) {
			dos.writeUTF(data.substring(pos));
		}
	}

	private String readLongString (RandomAccessFile raf)
		throws IOException
	{
		StringBuilder tmp = new StringBuilder();
		int count = raf.readInt();
		for ( int i=0; i<count; i++ ) {
			tmp.append(raf.readUTF());
		}
		return tmp.toString();
	}
*/
	protected function initBconfFile()
	{
		$bconfModel = new editor_Plugins_Okapi_Models_Bconf();
		$bconfModel->save();
	}

	/**
	 * Writes out part of an opened file as separate file
	 * @return boolean
	 * @throws IOException
	 */
	private function createReferencedFile(SplFileObject $raf, int $size, string $path)
	{
		if ($raf == null || !$path) {
			return false;
		}
		/** @var SplFileObject $fos file output stream */
		$fos = fopen($path, 'wb');
		if (!$fos) {
			return false;
		}
		// FIXME: when stream_copy_to_stream suppoerts SplFileObjects use that
		// $written = stream_copy_to_stream($raf->getFp(), $fos, $size);
		$written = 0;
		$toWrite = $size;
		$buffer = min(65536, $toWrite); // 16 pages à 4K
		while ($toWrite > $buffer && $written !== false) {
			$written += fwrite($fos, $raf->fread($buffer));
			$toWrite -= $buffer;
		}
		$written += fwrite($fos, $raf->fread($toWrite));
		fclose($fos);
		if ($written === $size) {
			return true;
		} else {
			//FIXME throw exception
			return false;
		}
	}

	private function parsePipeline(&$pipeline, $refMap, $outputDir)
	{
		$doc = new DOMDocument();
		$doc->loadXML($pipeline['xml']);
		/** @var NodeList nodes */
		$nodes = $doc->getElementsByTagName("step");
		$stepRefs = json_decode(file_get_contents(__DIR__."/StepReferences.json"), associative: true);

		foreach ($nodes as $elem) {
			$class = $elem->getAttribute("class");
			if ($class == null) {
				throw new OkapiException("The attribute 'class' is missing.");
			}
            $classParts = explode('.', $class);
			$stepName = end($classParts);
			$pathParams = $stepRefs[$stepName] ?? [];
			foreach ($pathParams as $param) {
				$param = lcfirst($param);
				$path = $outputDir."/".array_shift($refMap);
				$pipeline['xml'] = preg_replace("/^($param=).*$/m", "$1$path", $pipeline['xml']);
			}
			/* FIXME: Needed for plugin support
			// Check if we can use the available steps (and their loaders)
			StepInfo stepInfo = availableSteps.get(className);
			if ( stepInfo == null ) {
				// The pipeline has a step that is not currently in the available steps
				LOGGER.warn(String.format(
					"The step '%s' is not among the steps currently available. " +
					"It will be removed from the loaded pipeline.",
					className));
				continue;
			}
			IPipelineStep step;

			if ( stepInfo.loader == null ) {
				step = (IPipelineStep)Class.forName(stepInfo.stepClass).newInstance();
			}
			else {
*/

/*
			step = (IPipelineStep)Class.forName(stepInfo.stepClass, true, stepInfo.loader).newInstance();
			// Load the parameters if needed
			IParameters params = step.getParameters();
			if ( params != null ) {
			params.fromString(Util.getTextContent(elem));
			}
*/
			// add the step
			array_push($pipeline['steps'], $class);
		}

		return $pipeline;
	}
}
