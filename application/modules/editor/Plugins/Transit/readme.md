Informations to the transit plugin
==================================

Transit export
--------------

The transit exporter adds information about the term changes to the transit notice field.
This information contains: 
- The export date (configured for this task, or the current date)
- The set Quality State
- Informations about the changed terms:
-- In source: the terms which were changed from red to blue (transNotFound to transFound)
-- In target: the terms which were added to make a source term blue

The changed terms in source field are calculated by comparing the terms in target before and after editing. 
By this difference in used terms, the changes in source are calculated.