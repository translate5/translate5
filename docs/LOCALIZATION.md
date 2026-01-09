# Localization of translate5

### Types of Localization files
* ZXLIFF-files, bilingual, stored in `/locales` folders, filename represents the locale (formerly this have been "XLIFF"-files, which will stay for a transitional period to support forked code)
* JSON-files, monolingual, stored in `/locales` folders, filename contains the locale before the extension like `myfile.en.json`
* JSON files are expected to be flat containing strings like `"path.in.view": "The localized text"`
* Nested structures in JSON files will cause problems when importing them into translate5 for localization
* JSON files will be handeled as one entity, there is no extraction or update of single strings

### Locations of translation-files
* By default localization normally applies for a module: `"default"`, `"editor"`, `"library"`, `"erp"` (ZfExtended here counts as "module")
* A Plugin can be localized and the Plugin-Folder then counts as scope for the extraction
* Make sure all symlinks are setup properly, otherwise some private plugins may not be extracted properly (use `t5 dev:symlinks` to set the symlinks up)

### Extraction of strings in the Code
* Two methods are used to localize contents:
```
  $translate->_("string-to-translate", $locale = null)
  $view->templateApply("template-to-translate", $data = [])
```
* The first parameter of those methods needs to be a single, unconcatenated string
* When variables are used as argument, the strings to translate must be added in a comment to enable a proper extraction
* A comment to add programmatic localizations looks like
```
  /**
   * SECTION TO INCLUDE PROGRAMMATIC LOCALIZATION
   * ============================================
   * $translate->_('My special string');
   * $translate->_('Another string');
   * $translateTable->__('LEK_languages', 'langName');
   * $translateConfig->___('runtimeOptions.segments.autoStateFlags', 'default');
   */
```

* It should be added on top of the Code-File, after the License
* A simple string will be added with `$translate->_('My special string');`
* There are two "virtual" functions available to add strings from database-data:
* `$translateTable->__("table", "column")`: All distinct column-values of the table will be added as strings
* `$translateConfig->___("config-name", "column")`: The given column of the given config will be added. As column, "value", "default" and "defaults" are possible

### Updating the ZXLIFF files with new translations
* Normally developers provide only proper english localization sources, the `t5 l10n:update` update is done in the release process
* To extract the ZXLIFF-translations from the code, use `t5 l10n:update`. This will extract all modules & add new strings with empty targets to the ZXLIFFs
* For cases where developers need to translate stuff:
* With `t5 l10n:update -m` the ZXLIFFs with new translations will have an appended section on the bottom seperated by `<!-- TRANSLATIONS MISSING -->` with the new strings having `~UNTRANSLATED~` as target. This will be done for the fallback-locale or any locale given with the `-m`-option.
* When using the `-m`-option and after amending the missing translations, a reformat of the changed files with `t5 l10n:format` to sort all strings is neccessary. This ensures a proper layout over time and reduces the risk of conflicts between branches.
* For a transition period, the "old" XLIFF files will stay in the locales-folders and will be used as secondary source for translations, so older features forked before the new systematic can have the "old" XLIFFs merged and then extracted with `l10n:update`


### Translating the localizations with translate5
* `t5 l10n:extract -e -l -o` will create proper import-zips in the folder /data/l10n for each locale defined in `/MaintenanceCli/L10n/L10nConfiguration`
* when these imports are translated, the export-zips must be stored in /data/l10n and `t5 l10n:reimport` will unzip them and update all new translations. It will not replace the XLIFF's so new translations added in the meantime stay unchanged.

### Integrating older branches still using XLIFF-files
* Merge the older branch into the new systematic simply overwriting the (outdated) XLIFF files of the new code
* Use "t5 l10n:update" to integrate all "XLIFF" strings into the new "ZXLIFF" format that not yet exist there
* manual work may is needed after we moved to ZXLIFF-sources in `en`

### Client specific translations
* client-specific localizations can be converted to zxliff with the `t5 upgrade-clientspecific` command
* this is done automatically with post-install scripts

### Adding a new locale
* All JSON-based localization files must be cloned manually according the naming-scheme.
* Missing ZXLIFF files can be created with `t5 l10n:reimport -c`
* For the TermPortal, a JS file with dates/times/measurements `application/modules/editor/Plugins/TermPortal/public/resources/modern/locales/<LOCALE>.js` must be created
* The defaults for the _Zf_configuration_ value `runtimeOptions.translation.applicationLocale` must be amended  