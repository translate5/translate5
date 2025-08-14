
# OKAPI Plugin General functionality


* OKAPI is used to convert almost any input format to translatable, bilingual XLF files which then is imported to translate5
* translate5 can only work with XLF/XLIFF (there is an outdated parser for CSV)
* t5 utilizes several versions of OKAPI for this, the currend standard is OKAPI 1.44-snapshot (snapshot -> version tweaked by Denis)
* these versions are installed in several docker-containers (one for okapi <= 1.45. one or several for > 1.45)
* The process is two-fold:
  * before import OKAPI is used to generate the XLF - alongside a manifest.rkm that holds the import/extraction settings and is needed for export
  * these files are saved in the task-data-dir in /okapi-data
  * t5 generates a skeleton-file out of the "original.<extension>.xlf, that has the XLF targets replaced with segment-placeholders: `<lekTargetSeg id="551916" field="target" />`
  * on export ("original file, translated"), these placeholders are replaced with the translaed/reviewed segment targets and the resulting xlf is uploaded with the "manifest.rkm" to okapi again
  * for import, the selected file-format-settings are used (-> BCONF), the selected BCONF defines, which extensions the workfiles can have. These BCONFS are manageable in the "File format settings"
  * on export, a fixed BCONF is used "okapi_default_export.bconf"
  * the workers are OkapiImportWorker and OkapiExportWorker
  * for both steps the used OKAPI-version is saved in the config to ensure the export works with the same codebase as the import
  * "import" in t5 equals "extraction" in longhorn/rainbow, "export" equals "merging"


## BCONF: General Management

* the editable BCONFs are generally those used for import/extraction
* each BCONF has it's own database-entry alongside it's internal version
* the parts of a BCONF are stored in the filesystem in a folder with the DB-id: <APPLICATION_DATA>/editorOkapiBconf/<id>
* A bconf consists of a Pipeline, two SRX-files (usually identical) and 0-n FPRM files and the extension-mapping
* the version corresponds to editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX, it is the translate5-internal version of the BCONF
* if the version is "outdated", the BCONF is automatically repacked before it's next use
* the repacking ensures, the most recent version of it's parts are packed
* there is a "Translate5-Default" BCONF that can not be edited, it will be automatically generated on.e.g. fresh installations
* there can be system-bconfs & client/customer-bconfs, on each level a "default" can be set (that is preselected in the import-wizard & is used in e.g. InstantTranslate)
* the used file-format settings & the used OKAPI-version are logged to the task's event-log
* each bconf has a "content.json" created on unpacking which is an inventory of all relevant parts: the linked SRX files, the pipeline-steps and the customized FPRMs
* Packer::createMerging creates the export-bconf & saves it to the bconf-directory
* It consists of the extension-mapping, the FPRMs and a merging-pipeline always copied from translate5/application/modules/editor/Plugins/Okapi/data/pipeline/translate5-merging.pln
* merging/export bconf is always processed in parallel with import/extraction bconf: repackIfOutdated / pack / delete
* to enable linked subfilters, the export-bconf matches the used import-bconf in terms of FPRMs and Extension-Mapping


### BCONF: Individual Export BCONFs

* For exporting a task, a seperate Export-Bconf is used
* The Export-BCONF is a copy of the import-BCONF with a different Pipeline but without SRX (no Segmentation Step)
* The Export-BCONF is always generated alongside the import BCONF and is stored alongside the import-BCONF with the name "bconf-export"
* When a task is imported with a BCONF in the ZIP, a fixed Export-Bconf stored in .../Plugins/Okapi/data/okapi_default_export.bconf is used


### BCONF: Pipeline

- Currently: not editable, but unpacked to the data-folder
- proper base validation exists for extraction/import pipeline (basic steps are existing just like in `translate5/application/modules/editor/Plugins/Okapi/data/pipeline/translate5-extraction.pln`)
- each step is validated based on our properties-validation and the steps defined in `translate5/application/modules/editor/Plugins/Okapi/data/pipeline/step/`. Note, that srx-pathes are irrelevant, only the filename is important
- optional internal XSLT pipeline step is supported (XSLT file is added to the content.json and is part of BCONF after import)
- import/extraction pipeline can be downloaded/uploaded (validation is triggered on pipeline upload)
- editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX since version 10 is added as attribute within import/extraction pipeline: <rainbowPipeline .. bconfVersion="10">
- TODO: add full pipeline validation on BCONF import
- TODO: add version-update of the 3 base-steps in case of a internal version update
- TODO: in the Pipeline-Editor a XSLT step is addable in conjunction with uploading a XSLT file


### BCONF: Extension-Mapping

* the extension-mapping defines, which file-extension is processed with which Filter/FPRM in the RawDocumentToFilterEventsStep
* all customized and translate5-adjusted FPRMS (represented by their identifier) **must** be embedded in the BCONF
* the OKAPI-default FPRMs (as in `translate5/application/modules/editor/Plugins/Okapi/data/fprm/okapi`) do **not** need to be embedded as they are part of longhorn
* the frontend uses the extension-mapping to gather the data shown in the grid (as only file-based information)
* errors in the extension-mapping (invalid identifiers, non-existing FPRMs) will result in invalid BCONFs


### BCONF: SRX (aka Segmentation)

* Segmentation-files (SRX) are used in the Segmentation-Step only
* We only work with translate5-adjusted or customized SRX files
* Usually source and target SRX are identical (and stored only once in the BCONF-dir although present twice in the BCONF)
* There is a versioning of SRX files so SRXs representing translate5-adjusted are automatically updated
* The versioning is achieved by comparing the hash-values of an uploaded/changed SRX with the known translate5 SRXs
* therefore all SRX files delivered with translate5 are in the SRX-inventory with their hashes
* the inventory is in `translate5/application/modules/editor/Plugins/Okapi/data/srx/translate5-segmentation.json`
* to edit SRX files / Segmentation rules, Ratel (a tool within of Rainbow) has to be used


### BCONF: FPRM (aka Filters)

* There are 3 types of Filters: okapi-default, translate5-adjusted and user-customized
* okapi & translate5 have inventories in `translate5/application/modules/editor/Plugins/Okapi/data/fprm`
* customer specific FPRMs exist only in the bconf's data-directory
* translate5-adjusted & customer-specific FPRMs must be packed with the (merging & extraction) BCONF
* FPRMS are generally named like <okapi-type>@<variant>
* okapi-default FPRMS have names like "okf_xml@okf_xml" or "okf_xml@okf_xml-docbook" (here "-docbook" hints at a variant of the general XML-filter)
* translate5-adjusted FPRMs are named like "okf_idml@translate5" (translate5-suffix)
* customized FPRMs are named like okf_xml@<domain>-<name> where domain is the customers domain as defined in runtimeOptions.server.domain and name the ascified name given on creation
* translate5-adjusted FPRMs will always be added out of the translate5-inventory to a bconf, this way we ensure updatability
* customized FPRMs will exist only in the BCONF user-data-folder
* YAML, XML and plain FPRMs will only be validated syntactically
* FPRMs are either XML-based, YAML-based, plain (= JSON) or Properties-based (Properties being an old Java format similar to "ini" files)
* only some FPRMs are editable via Frontend: all XML based, all YAML based (both being edited directly as text) and some Properties based.
* there is only one "plain" FPRM (okf_wiki), if we implement a real JSON-editor for it, we should rename it to "JSON"
* editable FPRMs are noted in Filters::GUIS
* if e.g. "okf_xml" is editable, all variants will be editable as well, the editing-capabilities apply to the base-type

### FPRM Quirks

* OpenXML filter has "Maximal attribute size of xml attributes" parameter (maxAttributeSize) which affects ability to import large files.
* Default value of maxAttributeSize.i was increased from 4MB to 40MB which worked fine for 300MB+ MS Office files   
* T5 uses native parsing for XLIFF files by default, which fails if xliff file contains CDATA blocks
* To import XLIFF with CDATA you can map the file format template “xliff 1.2 with cdata” to xlf/xliff file extension in the file format settings of T5 (and adapt the template further if necessary)

### BCONF: Properties based FPRMs

* custom properties FPRMs will be validated against the okapi-default ones after editing
* the translate5-adjusted FPRMs are not validated against the defaults but it is expected they are complete
* generally properties-based FPRMs have only 3 types of data: boolean (suffixed `.b`), integer (suffixed `.i`) and string (no suffix)
* the frontend for "properties" files is complex as lists and collections of complex items are deserialized by index:
* lists example:
```
codeFinderRules.count.i=2
codeFinderRules.rule0=</?([A-Z0-9a-z]*)\b[^>]*>
codeFinderRules.rule1=</?([0-9]*)>
```
* lists with "volatile" (=abbrevated) names example:
```
tsComplexFieldDefinitionsToExtract.i=2
cfd0=HYPERLINK
cfd1=IMG
```
* collections example:
```
ruleCount.i=4
rule0.ruleName=Comments+String
rule0.ruleType.i=0
rule0.expr=/\*(.*?)\*/\n"(.*?)"\s*=(.*?(?<!\\)(?:\\{2})*");
rule0.groupNote.i=1
rule0.preserveWS.b=true
rule0.sample=/* Menu item to make the current document plain text */$0a$"Make Plain Text" = "Make Plain \"Text";$0a$/* Menu item to make the current document rich text */$0a$"Make Rich Text" = "Make Rich Text";$0a$
rule0.codeFinderRules.count.i=3
rule0.codeFinderRules.rule0=<!\[CDATA\[
rule0.codeFinderRules.rule1=\]\]>
rule0.codeFinderRules.rule2=%(([-0+#]?)[-0+#]?)((\d\$)?)(([\d\*]*)(\.[\d\*]*)?)[dioxXucsfeEgGpn@]
rule0.codeFinderRules.sample=<![CDATA[<font color=#00A7FB><u>\"Try Later\"</u></font>]]>
rule0.codeFinderRules.useAllRulesWhenTesting.b=true
...
```


 