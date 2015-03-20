For using the export feature of translate5 the additional library "Horde Diff"
is needed: 
- Download the package from: http://pear.horde.org/get/Horde_Text_Diff-2.0.1.tgz
- Unpack it. Move or link the "Horde_Text_Diff-X.Y.Z" versioned directory to a unversioned "Horde_Text_Diff" directory. 

Example under linux:
Assuming you are in Directory "translate5_webroot/library":  

$ wget http://pear.horde.org/get/Horde_Text_Diff-2.0.1.tgz
$ tar xvzf Horde_Text_Diff-2.0.1.tgz
$ ln -s Horde_Text_Diff-2.0.1 Horde_Text_Diff

Instead of using a SymLink you can also rename the directory:

$ wget http://pear.horde.org/get/Horde_Text_Diff-2.0.1.tgz
$ tar xvzf Horde_Text_Diff-2.0.1.tgz
$ mv Horde_Text_Diff-2.0.1 Horde_Text_Diff

