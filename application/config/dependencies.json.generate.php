#!/usr/bin/php
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
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
     * Since long strings are hard to maintain in JSON (all in one line, no line breaks)
     * and no comments are possible in JSON, I decided to create a PHP based generator of 
     * the dependencies JSON. The same config structure is just build up in PHP and saved to disk as JSON in the end
     * 
     * Assoc arrays are used instead of stdClass for better readability.
     */

$dep = new stdClass();

//the idea was to provide different channes, the problem is, how to choose the channel!
// Conclusion: the default channel can come from the dep.json, but if the user wants another this must be configured elsewhere
$dep->channels = [
    "RELEASE" => "http://www.translate5.net/downloads/"
];

//The path to the md5hashtable which is used for comparing if a package was changed on server or not
$dep->md5hashtable = "RELEASE:md5hashtable";

//basic application configuration
$dep->application = [
    "name" => "translate5",
    "label" => "Translate5 - latest version",
    "url" => "RELEASE:translate5.zip"
];

/*
 * For an explanation of each fields see the inline comments below!
 */

$dep->dependencies = [[
        "name" => "translate5-DB-init",                     //used as internal name of the package / dependency
        "label" => "translate5 DB init",                    //shown as name to the user
        "url" => "RELEASE:translate5-DB-init.zip",          //URL of the package to be downloaded
        "target" => "dbinit/"                               //due to a bug in the downloader a target always must be given!
                                                            // without a target the whole application is getting deleted!
                                                            // that means we can provide only ZIP packages at the moment
                                                            // with version 2.5.10 this is fixed, but lazy updaters will still have the problem!
                                                            // so in near future target will be optional, and without a target 
                                                            // nothing will be unzipped, just downloaded
    ],[
        "name" => "horde_text_diff",                        // see above
        "label" => "Horde Text Diff",                       // see above
        "url" => "RELEASE:Horde_Text_Diff-2.0.1.zip",       // see above
        "version" => "2.0.1",                               // currently not used, just for the sake of completeness
        "target" => "library/Horde_Text_Diff/",             // unzip target, see above
        "licenses" => [[                                    // list of licenses to be confirmed for this package
            "uses" => "the Horde Text Diff library",        // is parsed into license title and agreement
            "license" => "LGPL",                            // is parsed into license title and agreement
            "relpath" => "docs/third-party-licenses/Horde_Text_Diff-2.0.1.license.txt" 
                                                            // relpath file is checked for existence, 
                                                            //  then the path (not the content) parsed into license agreement
            // agreement   optional, overwrites default agreement (defined in ZfExtended_Models_Installer_License)
            // title       optional, overwrites default title (defined in ZfExtended_Models_Installer_License)
        ]]
    ],[
        "name" => "zend",
        "label" => "PHP Zend",
        "url" => "RELEASE:Zend-1.12-11.zip",
        "version" => "1.12-11",
        "target" => "library/zend/"
    ],[
        "name" => "querypath",
        "label" => "QueryPath",
        "url" => "RELEASE:querypath-3.0.3-p1.zip",
        "version" => "3.0.3-p1",
        "target" => "library/querypath/",
        "licenses" => [[
            "uses" => "the QueryPath library",
            "license" => "LGPL",
            "relpath" => "docs/third-party-licenses/querypath-3.0.3.license.txt"
        ]]
    ],[
        "name" => "Open_Sans",
        "label" => "Open Sans Fonts",
        "url" => "RELEASE:Open_Sans.zip",
        "target" => "application/modules/editor/ThirdParty/Open_Sans/",
        "licenses" => [[
            "uses" => "the Open Sans font",
            "license" => "Apache License 2.0",
            "relpath" => "docs/third-party-licenses/Open_Sans-license.txt"
        ]]
    ],[
        "name" => "termtagger",
        "label" => "openTMS TermTagger",
        "url" => "RELEASE:openTMStermTagger.zip",
        "target" => "application/modules/editor/ThirdParty/XliffTermTagger/",
        "licenses" => [[
            "uses" => "the openTMS TermTagger",
            "license" => "Apache License 2.0",
            "relpath" => "docs/third-party-licenses/openTMStermTagger-license.txt"
        ],[
            "label" => "openTMS TermTagger libraries",
            "license" => "CDDL 1.1",
            "relpath" => "docs/third-party-licenses/CDDL-license.txt",
            "agreement" => 'Grizzly project and others license agreement description: 
  Some of the libraries openTMS TermTagger builds on are licensed 
  under the CDDL license. Please read the following license agreement
  and accept it for these libraries (like the Grizzly project and 
  others). Which library uses which license is listed in the openTMS 
  TermTagger installation directory which you will find in your 
  translate5 application directory beneath

    application/modules/editor/ThirdParty/XliffTermTagger/

  after installation. You must accept the terms of this agreement for
  these components by typing "y" before continuing with the 
  installation.'
        ]]
    ],[
        "name" => "opentm2",
        "label" => "OpenTM2",
        "url" => "RELEASE:OpenTM2-Community-Edition-Setup.zip",
        "target" => "OpenTM2-Installer",
        "version" => "1.5.1",
        "licenses" => [[
            "uses" => "OpenTM2 Community Edition",
            "license" => "Eclipse Public License 1.0",
            "relpath" => "docs/third-party-licenses/OpenTM2-Community-Edition-license.txt",
            "agreement" => 'translate5 uses {USES} (version {VERSION}).
  Please read the following license agreement and accept it for 
  OpenTM2.

    {RELPATH}

  You must accept the terms of this agreement for {LABEL} by typing
  "y" and <ENTER> before continuing with the installation.
  If you type "y", the translate5 installer will download {LABEL} 
  for you. 

  !!!!!!!!!!!!! ATTENTION !!!!!!!!!!!!!!!!!!!!!!
    Since {LABEL} is executable only under Microsoft Windows
    Operating Systems the installer can only download it for you.
  !!!!!!!!!!!!! ATTENTION !!!!!!!!!!!!!!!!!!!!!!

  The Installation has to be started manually! 

  Please stop an already running {LABEL} instance before 
  installing / updating it!
  The downloaded file will be located in the folder
                
    "OpenTM2-Installer/"

  {SUFFIX}'
        ]]
    ],[
        "name" => "phpexcel",
        "label" => "PHPExcel",
        "version" => "1.8.1",
        "url" => "RELEASE:PHPExcel.zip",
        "target" => "library/ZfExtended/ThirdParty/PHPExcel/",
        "licenses" => [[
            "uses" => "PHPExcel",
            "license" => "LGPL",
            "relpath" => "docs/third-party-licenses/PHPExcel-1.8.1.license.txt"
        ]]
    ],[
        "name" => "extjs-62",
        "label" => "ExtJS (Version 6)",
        "version" => "6.2.0",
        "url" => "RELEASE:ext-6.2.0-gpl.zip",
        "target" => "public/ext-6.2.0",
        "licenses" => [[
            "uses" => "ExtJS",
            "license" => "GPL 3.0",
            "relpath" => "docs/third-party-licenses/ExtJs6-license.txt"
        ]]
    ],[
        "name" => "rangy",
        "label" => "Rangy",
        "version" => "1.3.1-dev",
        "url" => "RELEASE:rangy.zip",
        "target" => "public/js/rangy",
        "licenses" => [[
            "uses" => "Rangy",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/rangy-license.txt"
        ]]
    ],[
        "name" => "jquery-ui",
        "label" => "JQuery UI",
        "version" => "1.12.1",
        "url" => "RELEASE:jquery-ui.zip",
        "target" => "public/js/jquery-ui",
        "licenses" => [[
            "uses" => "JQuery",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/jquery-license.txt"
        ],[
            "uses" => "JQuery UI",
            "license" => "JQuery UI",
            "relpath" => "docs/third-party-licenses/jquery-ui-license.txt"
        ],[
            "uses" => "Future Imperfect by HTML5 UP",
            "license" => "CCA 3.0",
            "relpath" => "docs/third-party-licenses/jquery-ui-license.txt"
        ],[
            "uses" => "Skel",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/skel-license.txt"
        ]]
    ],[
        "name" => "phpppreadsheet",
        "label" => "PhpSpreadsheet",
        "version" => "1.6",
        "url" => "RELEASE:PhpSpreadsheet-and-dependencies.zip",
        "target" => "library/PhpSpreadsheet/",
        "licenses" => [[
            "uses" => "Composer",
            "license" => "Expat",
            "relpath" => "docs/third-party-licenses/Composer-license.txt"
        ],[
            "uses" => "Composer File Util/TlsHelper.php",
            "license" => "Expat and BSD-2-Clause",
            "relpath" => "docs/third-party-licenses/Composer-license.txt"
        ],[
            "uses" => "PHPComplex",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/MarkBaker-PHPComplex-license.md"
        ],[
            "uses" => "PHPMatrix",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/MarkBaker-PHPMatrix-license.md"
        ],[
            "uses" => "PhpSpreadsheet",
            "license" => "LGPL",
            "relpath" => "docs/third-party-licenses/PhpSpreadsheet-license.txt"
        ],[
            "uses" => "PHP FIG Simple Cache PSR",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/Simple-Cache-license.md"
        ]]
    ],[
        "name" => "OpenID-Connect-PHP",
        "label" => "OpenID-Connect-PHP",
        "version" => "0.8.0",
        "url" => "RELEASE:OpenID-Connect-PHP.zip",
        "target" => "library/OpenID-Connect-PHP/",
        "licenses" => [[
            "uses" => "Composer",
            "license" => "Expat",
            "relpath" => "docs/third-party-licenses/OpenID-Connect-PHP-Composer-license.txt"
        ],[
            "uses" => "OpenID Connect PHP",
            "license" => "Apache License 2.0",
            "relpath" => "docs/third-party-licenses/OpenID-Connect-PHP-license.txt"
        ],[
            "uses" => "phpseclib - PHP Secure Communications Library",
            "license" => "MIT",
            "relpath" => "docs/third-party-licenses/OpenID-Connect-PHP-phpseclib-license.txt"
        ]]
    ]
];

$dep = json_encode($dep, JSON_PRETTY_PRINT);
file_put_contents('dependencies.json', $dep);