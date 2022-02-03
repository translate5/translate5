#/bin/bash

#
# translation package
#
ZIP=translate5-de-en-TARGET.zip
if [ -f $ZIP ]; then
    rm $ZIP
fi
find . -not -path "*/data/*" -iname "de.xliff"|while read FILENAME; do
  # copy to zxliff
  ZXLIFFNAME=`echo $FILENAME | sed -e "s/xliff$/zxliff/"`
  cp $FILENAME $ZXLIFFNAME
  # remove target content
  perl -0777 -pi -e 's/<target>.*?<\/target>/<target><\/target>/gs' $ZXLIFFNAME
  # add to zip
  zip $ZIP $ZXLIFFNAME
  rm $ZXLIFFNAME
  # rename in zip, add workfiles
  FILENAME=`echo $ZXLIFFNAME | sed -e "s/^.\///"`
  NEWNAME=`echo "workfiles/$FILENAME"`
  printf "@ ${FILENAME}\n@=${NEWNAME}\n" | zipnote -w $ZIP
done
JSON_NAME="application/modules/editor/PrivatePlugins/TermPortal/locales/de.json"
zip $ZIP $JSON_NAME
printf "@ ${JSON_NAME}\n@=workfiles/${JSON_NAME}\n" | zipnote -w $ZIP

#add en as pivot content
find . -not -path "*/data/*" -iname "en.xliff"|while read FILENAME; do
  # add to zip
  zip $ZIP $FILENAME
  # rename in zip, add pivot
  FILENAME=`echo $FILENAME | sed -e "s/^.\///"`
  NEWNAME=`echo "relais/$FILENAME" | sed -e "s/en.xliff$/de.zxliff/"`
  printf "@ ${FILENAME}\n@=${NEWNAME}\n" | zipnote -w $ZIP
done

# JSON can not be added automatically as pivot must be translated first and then the en.json.xlf must be used as pivot file

echo
echo "created $ZIP"
echo " Please remove non wanted private Plugin XLFs manually!!!"
echo " After translating and exporting from Translate5 rename *.zxliff back to *.xliff"

