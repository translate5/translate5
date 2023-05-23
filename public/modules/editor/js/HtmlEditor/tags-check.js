class TagsCheck {
    constructor(referenceTags) {
        this.referenceTags = referenceTags;
    }

    checkTags(node) {
        let nodelist = node.getElementsByTagName('img');
        this.fixDuplicateImgIds(nodelist);
        if (!this.checkContentTags(nodelist)) {
            // if there are content tag errors, and we are in save anyway mode, we remove orphaned tags then
            this.disableErrorCheck && this.removeOrphanedTags(nodelist);
            return; //no more checks if missing tags found
        }
        this.removeOrphanedTags(nodelist);
        this.checkTagOrder(nodelist);
    }

    fixDuplicateImgIds(nodelist) {
        var me = this,
            ids = {},
            stackList = {},
            updateId = function (img, newQid, oldQid) {
                //dieses img mit der neuen seq versorgen.
                img.id = img.id.replace(new RegExp(oldQid + '$'), newQid);
                img.setAttribute('data-t5qid', newQid);
            };
        //duplicate id fix vor removeOrphanedLogik, da diese auf eindeutigkeit der IDs baut
        //dupl id fix benötigt checkTagOrder, welcher sich aber mit removeOrphanedLogik beißt
        Ext.each(nodelist, function (img) {
            var newQid, oldQid = me.getElementsQualityId(img), id = img.id, pid, open;
            if (!id || me.isDuplicateSaveTag(img)) {
                return;
            }
            if (!ids[id]) {
                //id noch nicht vorhanden, dann ist sie nicht doppelt => raus
                ids[id] = true;
                return;
            }

            //gibt es einen Stack mit inhalten für meine ID, dann hole die Seq vom Stack und verwende diese
            if (stackList[id] && stackList[id].length > 0) {
                newQid = stackList[id].shift();
                updateId(img, newQid, oldQid);
                return;
            }
            //wenn nein, dann:
            //partner id erzeugen
            open = new RegExp("-open");
            if (open.test(id)) {
                pid = id.replace(open, '-close');
            } else {
                pid = id.replace(/-close/, '-open');
            }
            //bei bedarf partner stack erzeugen
            if (!stackList[pid]) {
                stackList[pid] = [];
            }
            newQid = Ext.id();
            //die neue seq auf den Stack der PartnerId legen
            stackList[pid].push(newQid);
            updateId(img, newQid, oldQid);
        });
    }

    checkContentTags(nodelist) {
        var me = this,
            foundIds = [],
            ignoreWhitespace = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags');
        me.missingContentTags = [];
        me.duplicatedContentTags = [];

        Ext.each(nodelist, function (img) {
            //ignore whitespace and nodes without ids
            if (ignoreWhitespace && /whitespace/.test(img.className) || /^\s*$/.test(img.id)) {
                return;
            }
            if (Ext.Array.contains(foundIds, img.id) && img.parentNode.nodeName.toLowerCase() !== "del") {
                me.duplicatedContentTags.push(me.markupImages[img.id.replace(new RegExp('^' + me.idPrefix), '')]);
            } else {
                if (img.parentNode.nodeName.toLowerCase() !== "del") {
                    foundIds.push(img.id);
                }
            }
        });
        Ext.Object.each(this.markupImages, function (key, item) {
            if (ignoreWhitespace && item.whitespaceTag) {
                return;
            }
            if (!Ext.Array.contains(foundIds, me.idPrefix + key)) {
                me.missingContentTags.push(item);
            }
        });
        return (me.missingContentTags.length === 0 && me.duplicatedContentTags.length === 0);
    }

    removeOrphanedTags(nodelist) {
        var me = this, openers = {}, closers = {}, hasRemoves = false;

        Ext.each(nodelist, function (img) {
            if (me.isDuplicateSaveTag(img)) {
                return;
            }
            if (/-open/.test(img.id)) {
                openers[img.id] = img;
            }
            if (/-close/.test(img.id)) {
                closers[img.id] = img;
            }
        });
        Ext.iterate(openers, function (id, img) {
            var closeId = img.id.replace(/-open/, '-close');
            if (closers[closeId]) {
                //closer zum opener => aus "closer entfern" liste raus
                delete closers[closeId];
            } else {
                //kein closer zum opener => opener zum entfernen markieren
                hasRemoves = true;
                img.id = 'remove-' + img.id;
            }
        });
        Ext.iterate(closers, function (id, img) {
            hasRemoves = true;
            img.id = 'remove-' + img.id;
        });
        if (hasRemoves) {
            Editor.MessageBox.addInfo(this.strings.tagRemovedText);
        }
    }

    checkTagOrder (nodelist) {
        var me = this, open = {}, clean = true;
        Ext.each(nodelist, function (img) {
            // crucial: for the tag-order, we only have to check tags that are not already deleted
            if (!me.isDeletedTag(img)) {
                if (me.isDuplicateSaveTag(img) || /^remove/.test(img.id) || /(-single|-whitespace)/.test(img.id)) {
                    //ignore tags marked to remove
                    return;
                }
                if (/-open/.test(img.id)) {
                    open[img.id] = true;
                    return;
                }
                var o = img.id.replace(/-close/, '-open');
                if (!open[o]) {
                    clean = false;
                    return false; //break each
                }
            }
        });
        this.isTagOrderClean = clean;
    }
}
