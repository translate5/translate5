For using the export feature of translate5 the additional library "Horde Diff"
is needed: 
- Download the package from: http://pear.horde.org/get/Horde_Text_Diff-2.0.1.tgz
- Unpack it. Move the "Horde" directory from the unpacked directory "lib" to the
current "library" directory. 

Example under linux:
Assuming you are in Directory "translate5_webroot/library":  

$ wget http://pear.horde.org/get/Horde_Text_Diff-2.0.1.tgz
$ tar xvzf Horde_Text_Diff-2.0.1.tgz
$ ln -s Horde_Text_Diff-2.0.1/lib/Horde Horde

Instead of using a SymLink you can also move the directory and delete the
original Horde_Text_Diff-2.0.1 directory:

$ wget http://pear.horde.org/get/Horde_Text_Diff-2.0.1.tgz
$ tar xvzf Horde_Text_Diff-2.0.1.tgz
$ mv Horde_Text_Diff-2.0.1/lib/Horde Horde
$ rm -f Horde_Text_Diff-2.0.1

