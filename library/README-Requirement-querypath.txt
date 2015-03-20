For using translate5 the additional library "Querypath" is needed: 
- Download the package from: https://github.com/technosophos/querypath/archive/3.0.3.zip
- Unpack it. Move or link the "querypath-X.Y.Z" versioned directory to a unversioned "querypath" directory. 

Example under linux:
Assuming you are in Directory "translate5_webroot/library":  

$ wget https://github.com/technosophos/querypath/archive/3.0.3.zip
$ unzip 3.0.3.zip 
$ ln -s querypath-3.0.3 querypath

Instead of using a SymLink you can also rename the directory:

$ wget https://github.com/technosophos/querypath/archive/3.0.3.zip
$ unzip 3.0.3.zip 
$ mv querypath-3.0.3 querypath