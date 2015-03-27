Which statistics does this plugin create
========================================

- termFoundCount → counts the blue (transFound) terms
- termNotFoundCount → counts the red (transNotFound) terms
- segmentsPerFileFound → counts the segments with blue terms
- segmentsPerFileNotFound → counts the segments with red terms
- segmentsPerFile → over all segment count in one file
- That means segmentsPerFile <= segmentsPerFileFound + segmentsPerFileNotFound
- not equal: because one segment can contain red AND blue terms

same for the char counters:
- charFoundCount → counts all chars of segments with blue terms
- charNotFoundCount → counts all chars of segments with red terms
- since segments can contain blue and red terms, this segments are counted twice!

Segments with no red terms are completly ignored!
In an old version readonly segments were ignored, this was changed,
since no red terms is a subset of readonly terms.