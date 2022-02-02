#/bin/bash

#
# review packages
# DE-EN
#
ZIP=translate5_review-de-en.zip
if [ -f $ZIP ]; then
    rm $ZIP
fi

#add en files
find . -not -path "*/data/*" -iname "en.xliff"|while read FILENAME; do
  # add to zip
  zip $ZIP $FILENAME
  # rename in zip, add workfile
  FILENAME=`echo $FILENAME | sed -e "s/^.\///"`
  NEWNAME=`echo "workfiles/$FILENAME" | sed -e "s/en.xliff$/en.zxliff/"`
  printf "@ ${FILENAME}\n@=${NEWNAME}\n" | zipnote -w $ZIP
done

JSON_NAME="application/modules/editor/PrivatePlugins/TermPortal/locales/en.json"
zip $ZIP $JSON_NAME
printf "@ ${JSON_NAME}\n@=workfiles/${JSON_NAME}\n" | zipnote -w $ZIP

ZIP_EN=$ZIP

#
# DE-DE
#
ZIP=translate5_review-de-de.zip
if [ -f $ZIP ]; then
    rm $ZIP
fi

#add en files
find . -not -path "*/data/*" -iname "de.xliff"|while read FILENAME; do
  # add to zip
  zip $ZIP $FILENAME
  # rename in zip, add workfile
  FILENAME=`echo $FILENAME | sed -e "s/^.\///"`
  NEWNAME=`echo "workfiles/$FILENAME" | sed -e "s/de.xliff$/de.zxliff/"`
  printf "@ ${FILENAME}\n@=${NEWNAME}\n" | zipnote -w $ZIP
done

JSON_NAME="application/modules/editor/PrivatePlugins/TermPortal/locales/de.json"
zip $ZIP $JSON_NAME
printf "@ ${JSON_NAME}\n@=workfiles/${JSON_NAME}\n" | zipnote -w $ZIP

echo
echo "created $ZIP and $ZIP_EN"
echo " Please remove non wanted private Plugin XLFs manually!!!"
echo " After translating and exporting from Translate5 rename de.zxliff files to TARGETLANG.xliff (suffix without z!)"

