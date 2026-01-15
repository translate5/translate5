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
* Three methods are used to localize contents in PHP:
```
  Localization::trans("string-to-translate", $locale = null)
  $translate->_("string-to-translate", $locale = null)
  $view->templateApply("template-to-translate", $data = [])
```
* The first argument of those methods needs to be an unconcated string or a concatenated string where ell string-delimiters are the same, either all " or '
* When variables are used as argument, the strings to translate must be added in an PHP attribute to enable a proper extraction
* To add programmatic translations, PHP attributes shall be used for PHP files and standardized comment-sections can be used in PHTML files
* The possible PHP attributes look like
```
#[\MittagQI\ZfExtended\Localization\LocalizableString('external-string-to-translate')]
#[\MittagQI\ZfExtended\Localization\LocalizableTableColumn('LEK_languages', 'langName')] 
#[\MittagQI\ZfExtended\Localization\LocalizableConfigValue('runtimeOptions.branch.name', 'value')]
class LocalizationExample
{

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    public const CONSTANT_TO_LOCALIZE = 'Now can be used to feed localization';

    #[\MittagQI\ZfExtended\Localization\LocalizableProp]
    protected string $propToLocalize = 'Now can be used to feed localization';
    
    #[\MittagQI\ZfExtended\Localization\LocalizableArrayProp]
    protected string $arrayToLocalize = [
        'localizedKey1' => 'Now can be used to feed localization',
        'localizedKey2' => 'Now can be used to feed localization',
    ]
}
* `LocalizableTableColumn('LEK_languages', 'langName')`: All distinct column-values of the table will be added as strings
* `LocalizableConfigValue('runtimeOptions.branch.name', 'column')`: The given column of the given config will be added. As column, "value", "default" and "defaults" are possible
```
* A comment to add programmatic localizations in PHTML - which should be added on top of the File - looks like
```
  /**
   * SECTION TO INCLUDE PROGRAMMATIC LOCALIZATION
   * ============================================
   * $translate->_('My special string');
   * $translate->_('Another string');
   */
```

### How Localization strings look like
* The localization generally is made in english
* The strings preferrably should be as showing up in the english localization
* Please no fancy "variables" like "what.this.string.is.for"
* Please do use typographical quotes, no real quotes like " or '
* Typographical quotes: ‘, ’, “, ”, “quoted”
* The strings in the code preferrably need to be delimited by single quotes
* If concatenation is used to format longer strings, the delimiter (" or ') needs to be identical throughout all parts !

### How Localization strings look like
* The localization generally is made in english
* The strings preferrably should be as showing up in the english localization
* Please no fancy "variables" like "what.this.string.is.for"
* Please do use typographical quotes, no real quotes like " or '
* Typographical quotes: ‘, ’, “, ”, “quoted”
* The strings in the code need to be single, unconcatenated strings preferrably wrapped in single quotes

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
* client-specific localizations can be converted to zxliff with the `t5 l10n:upgrade-clientspecific` command
* this is done automatically with post-install scripts

### Adding a new locale
* All JSON-based localization files must be cloned manually according the naming-scheme.
* Missing ZXLIFF files can be created with `t5 l10n:reimport -c`
* For the TermPortal, a JS file with dates/times/measurements `application/modules/editor/Plugins/TermPortal/public/resources/modern/locales/<LOCALE>.js` must be created
* The defaults for the _Zf_configuration_ value `runtimeOptions.translation.applicationLocale` must be amended  