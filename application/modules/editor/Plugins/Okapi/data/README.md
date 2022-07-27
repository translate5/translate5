## Updating git-based import BCONF components

### 1) translate5 adjuatedSegmentation / SRX-files

if the translate5 adjusted SRX is updated, it must be added to the folder "/translate5/application/modules/editor/Plugins/Okapi/data/srx/translate5/"
This folder holds all SRX versions from the rollout of the feature on, what may is useful to track the changes of the rules.
The naming should be "languages-N.srx" if source/target are identical or "languages-source-N.srx" / "languages-source-N.srx" if different.
"N" here relates to the counter of files in the directory and is not really relevant.
The added files then **must** be added to the SRX Inventory file "/translate5/application/modules/editor/Plugins/Okapi/data/srx/translate5-segmentation.json" as well.
This file is the Inventory of the translate5 adjusted SRX files and their hashes. Since - opposed to FPRM files - the SRX files have no database-based versioning, we need all the hash-values of default-SRX files to identify them when importing bconfs.
To add an entry there, a new JSON Item must be added to the **top** of the list like this:

`{
    "version": -1,
    "source": "languages-source-1.srx",
    "target": "languages-target-1.srx",
    "sourceHash": "",
    "targetHash": ""
}`

Hash and BCONF-Version-Index will be automatically set with the use of the "dev:okapibconfversion" command (see 4), which not only increases the version but also evaluates the hashes & the correct version of an added SRX.

### 2) translate5 adjuated FPRMs

The translate5 adjuated FPRMs are hold inside the folder "translate5/application/modules/editor/Plugins/Okapi/data/fprm/translate5/" which has a JSON inventory-file "translate5/application/modules/editor/Plugins/Okapi/data/fprm/translate5-filters.json"
To add new Verions of a already existing translate5 adjuated FPRM the FPRM-file simply is changed on file-base and the BCONF-Version-Index needs to be increased (With "dev:okapibconfversion" command, see 4).
To add a new translate5 adjusted FPRM it has to be added as file in the afromentioned folder. Note, that the naming-scheme is mandatory like "okf_openxml@translate5.fprm" or "okf_openxml@translate5-SOME_SPECIAL_PURPOSE.fprm".
The naming structure is 

`OKAPI_TYPE + "@translate5" + ["-" + FURTHER_SPECIFICATION (optionsl) ]`

Each FPRM-file must have a complementary entry in the JSON inventory-file, which looks like this:

`{
    "id": "translate5",
    "type": "okf_openoffice",
    "replaceId": "okf_openoffice",
    "name": "t5 OpenOffice.org Documents",
    "description": "translate5 adjusted filter for OpenOffice.org documents",
    "mime": "application/x-openoffice",
    "extensions": ["odp","ods","odt"]
}`
 
- "id" is always "translate5" or "translate5-SPECIALPURPOSE" for translate5 adjuated FPRMs
- "type" represents the okapi-type
- "replaceId" forces, that all OKAPI default FPRMs will this id will be replaced with this translate5 adjusted version. Note the difference betweenb okapi-type (eg. okf_xml) and okapi-id (e.g. okf_regex-macStrings)
- "name" and "description" and "mime" are shown in the "Filter"-frontend in the app
- "extensions" will be the default file-extensions this filter is meant for

After adding or updating a FPRM, the BCONF-Version-Index has to be updated. This can be done with the "dev:okapibconfversion" command (see 4).

### 3) OKAPI default FPRMs

The OKAPI default FPRMs are hold inside the folder "translate5/application/modules/editor/Plugins/Okapi/data/fprm/okapi/" which has a JSON inventory-file "translate5/application/modules/editor/Plugins/Okapi/data/fprm/okapi-filters.json"
This inventory follows the same rules as described in 2), the entries of the inventory-file are like:

`{
    "id": "okf_xml-AppleStringsdict",
    "type": "okf_xml",
    "name": "Apple Stringsdict",
    "description": "Apple Stringsdict files",
    "mime": "text/xml",
    "extensions": ["stringsdict"],
    "settings": true
}`

- "id" is the filter-ID as used in RAINBOW
- "type" represents the okapi-type
- "name" and "description" and "mime" are shown in the "Filter"-frontend in the app
- "extensions" will be the default file-extensions this filter is meant for
- "settings" define, if the filter can have a settings/FPRM file. Some filters do not have a settings file and thus can only be used in the extension-mapping

### 4) Increasing the BCONF-Version-Index by command

The CLI command "translate5.sh dev:okapibconfversion" or "t5 dev:okapibconfversion" will increase the current BCONF-Version-Index and evaluate the hashes of added SRX files/entries (see 2).
The BCONF-Version-Index is also part of the database model for a BCONF, and when a BCONF has an older version than the current BCONF-Version-Index, the BCONF file will be repacked with all current SRX and FPRM versions when accessing the BCONF (e.g. when importing a task using the BCONF) 