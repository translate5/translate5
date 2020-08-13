Translate5 Plugins Placement
============================

Plugin Usage:
-------------
- Translate5 still searches PlugIns only in the "Plugins" folder
- Therefore Plugins developed in other places most go as Symlink there, see below.

For Developers:
--------------- 
- Only public Plugins may be saved here as a folder!
- For development private plugins may be sym linked from the non public repo ../PrivatePlugins/PLUGIN
  ln -s ../PrivatePlugins/PLUGIN 

**IMPORTANT**: Only private plugin symlinks NOT containing any client names may be committed here! 
Such client plugins may be only symlinked for development, but the symlink may never go into git!

For Deployment and Installation:
--------------------------------
- The private repo ../PricatePlugins is never deployed!
- In deployment process the needed Plugins are copied from PrivatePlugins to Plugins folder as needed by the target package
