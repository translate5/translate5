#/bin/bash
ZIP=translate5-de-de.zip
if [ -f $ZIP ]; then
    rm $ZIP
fi
find . -not -path "*/data/*" -iname "de.xliff"|while read FILENAME; do
  # copy to zxliff
  ZXLIFFNAME=`echo $FILENAME | sed -e "s/xliff$/zxliff/"`
  cp $FILENAME $ZXLIFFNAME
  # remove target content
  perl -pi -e 's/<target>.*?<\/target>/<target><\/target>/gms' $ZXLIFFNAME
  # add to zip
  zip $ZIP $ZXLIFFNAME
  rm $ZXLIFFNAME
  # rename in zip, add proofread
  FILENAME=`echo $ZXLIFFNAME | sed -e "s/^.\///"`
  NEWNAME=`echo "proofRead/$FILENAME"`
  printf "@ ${FILENAME}\n@=${NEWNAME}\n" | zipnote -w $ZIP
done
echo
echo "created $ZIP"
echo " Remove non wanted private Plugin XLFs!"
echo " After translating and exporting from Translate5 rename *.zxliff back to *.xliff"

