#/bin/bash
ZIP=translate5-de-de.zip
if [ -f $ZIP ]; then
    rm $ZIP
fi
find . -not -path "*/data/*" -iname "de.xliff"|while read FILENAME; do
  # add to zip
  zip $ZIP $FILENAME
  FILENAME=`echo $FILENAME | sed -e "s/^.\///"`
  NEWNAME=`echo "proofRead/$FILENAME" | sed -e "s/xliff$/zxliff/"`
  # rename in zip, add proofread and convert to zxliff
  printf "@ ${FILENAME}\n@=${NEWNAME}\n" | zipnote -w $ZIP
done


