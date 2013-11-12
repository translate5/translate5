Die zugehörige Mapping-Konfiguration liegt in /application/iniOverwrites/APPLICATION_AGENCY/factoryOverwrites.ini

Der Ordner /application/factoryOverwrites enthält die per factory agencyspezifisch überschriebenen Objekte.

Die Verzeichnisstruktur ist die folgende:

/application/factoryOverwrites/APPLICATION_AGENCY/MODULNAME/ und darunter die Verzeichnishierarchie,
in der das überschriebene Objekt im Modul zu finden ist


Damit ein Objekt auch geladen  wird, muss es einen dem Pfad entsprechenden Objektnamen
haben, wie z. B. factoryOverwrites_beo_default_Models_SoapAssertCall