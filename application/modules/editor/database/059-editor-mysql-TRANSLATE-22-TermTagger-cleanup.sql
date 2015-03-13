delete from Zf_configuration where name = 'runtimeOptions.termTagger.javaExec';
delete from Zf_configuration where name = 'runtimeOptions.termTagger.dir';

update Zf_configuration set description = 'Ruft den TermTagger im Debugmodus auf.' where name = 'runtimeOptions.termTagger.debug';
update Zf_configuration set description = 'Aktiviert den Fuzzy Match Modus beim taggen von Termen.' where name = 'runtimeOptions.termTagger.fuzzy';
update Zf_configuration set description = 'Fuzzy Match Wert, eine Zahl zwischen 0 und 100' where name = 'runtimeOptions.termTagger.fuzzyPercent';
update Zf_configuration set description = 'removes termTagging on diff export, because Studio sometimes seems to destroy change-history otherwhise' where name = 'runtimeOptions.termTagger.removeTaggingOnExport.diffExport';
update Zf_configuration set description = 'removes termTagging on normal export, because Studio sometimes seems to destroy change-history otherwhise' where name = 'runtimeOptions.termTagger.removeTaggingOnExport.normalExport';
update Zf_configuration set description = 'Aktiviert den Stemmer beim taggen von Termen' where name = 'runtimeOptions.termTagger.stemmed';
update Zf_configuration set description = 'Comma separated list of available TermTagger-URLs. At least one available URL must be defined. Example: ["http://localhost:9000"]' where name = 'runtimeOptions.termTagger.url.default';
update Zf_configuration set description = 'connection timeout in seconds when parsing tbx' where name = 'runtimeOptions.termTagger.timeOut.tbxParsing';
update Zf_configuration set description = 'connection timeout in seconds when tagging segments' where name = 'runtimeOptions.termTagger.timeOut.segmentTagging';

update Zf_configuration set description = 'define mid column-header for csv-file-import, all other columns are used as (alternate) translation(s)' where name = 'runtimeOptions.import.csv.fields.mid';
update Zf_configuration set description = 'define source column-header for csv-file-import, all other columns are used as (alternate) translation(s)' where name = 'runtimeOptions.import.csv.fields.source';