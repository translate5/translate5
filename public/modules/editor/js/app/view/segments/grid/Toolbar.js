
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.segments.grid.Toolbar
 * @extends Ext.toolbar.Toolbar
 * @initalGenerated
 */
Ext.define('Editor.view.segments.grid.Toolbar', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.segmentsToolbar',

    requires: [
    	'Editor.view.segments.SpecialCharacters',
    	'Editor.view.segments.grid.ToolbarViewModel',
    	'Editor.view.segments.grid.ToolbarViewController'
    ],
    controller: 'segmentsToolbar',
    viewModel: {
        type:'segmentsToolbar'
    },
    initConfig: function(instanceConfig) {
            var me = this,
		        config,
		        menu;
            
            menu = {
                xtype: 'menu',
                items: [{
                    itemId: 'repetitionsBtn',
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.repetitionBtn}',
                    }
                }, '-', {
                    xtype: 'menuitem',
                    mode: {
                        type: 'editMode'
                    },
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.editModeBtn}'
                    },
                    group: 'toggleView',
                    textAlign: 'left'
                }, {
                    xtype: 'menuitem',
                    mode: {
                        type: 'ergonomicMode',
                    },
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.ergonomicModeBtn}'
                    },
                    group: 'toggleView',
                    textAlign: 'left'
                }, {
                    xtype: 'menuseparator'
                }, {
                    xtype: 'menucheckitem',
                    itemId: 'hideTagBtn',
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.hideTagBtn}'
                    },
                    tagMode: 'hide',
                    group: 'tagMode'
                }, {
                    xtype: 'menucheckitem',
                    itemId: 'shortTagBtn',
                    checked: true,
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.shortTagBtn}'
                    },
                    tagMode: 'short',
                    group: 'tagMode'
                }, {
                    xtype: 'menucheckitem',
                    itemId: 'fullTagBtn',
                    checked: true,
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.fullTagBtn}'
                    },
                    tagMode: 'full',
                    group: 'tagMode'
                }, {
                    xtype: 'menuseparator'
                }]
            };
            
            //add the available translate5 translations
            Ext.Object.each(Editor.data.translations, function(i, n) {
                menu.items.push({
                    xtype: 'menucheckitem',
                    itemId: 'localeMenuItem' + i,
                    checked: Editor.data.locale == i,
                    bind: {
                        text: n + ' {l10n.segmentGrid.toolbar.strings.interfaceTranslation}',
                    },
                    value: i,
                    tagMode: 'full',
                    group: 'localeMenuGroup'
                });
            });


            // add change user theme only if allowed
            if(Editor.data.frontend.changeUserThemeVisible){
                // add themes menu
                menu.items.push(me.getThemeMenuConfig());
            }
            var useHNavArrow = false,
                userCanModifyWhitespaceTags = Editor.app.getTaskConfig('segments.userCanModifyWhitespaceTags'),
                userCanInsertWhitespaceTags = Editor.app.getTaskConfig('segments.userCanInsertWhitespaceTags'),
                checkedItems = Ext.state.Manager.getProvider().get('editor.segmentActionMenu')?.checkedItems || '';

            config = {
                items: [{
                    xtype: 'button',
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.settings}',
                    },
                    itemId: 'viewModeMenu',
                    menu: menu
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    type: 'segment-zoom',
                    itemId: 'zoomInBtn',
                    iconCls: 'ico-zoom-in',
                    bind: {
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.zoomIn}',
                            showDelay: 0
                        }
                    },
                },{
                    xtype: 'button',
                    type: 'segment-zoom',
                    itemId: 'zoomOutBtn',
                    iconCls: 'ico-zoom-out',
                    bind: {
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.zoomOut}',
                            showDelay: 0
                        }
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    glyph: 'e17b@FontAwesome5FreeSolid',
                    itemId: 'clearSortAndFilterBtn',
                    cls: 'clearSortAndFilterBtn',
                    bind: {
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.clearSortAndFilterTooltip}',
                            showDelay: 0
                        }
                    }
                },{
                    xtype: 'tbseparator'
                },{
                    xtype: 'button',
                    itemId: 'watchListFilterBtn',
                    cls: 'watchListFilterBtn',
                    enableToggle: true,
                    bind: {
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.showBookmarkedSegments}',
                            showDelay: 0
                        }
                    },
                    icon: Editor.data.moduleFolder+'images/show_bookmarks.png'
                },{
                    xtype: 'button',
                    glyph: 'f0c5@FontAwesome5FreeSolid',
                    itemId: 'filterBtnRepeated',
                    bind: {
                        hidden: '{!taskHasDefaultLayout}',
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.showRepeatedSegments}',
                            showDelay: 0
                        }
                    },
                    enableToggle: true,
                }, {
                    xtype: 'tbseparator',
                }, {
                    itemId: 'saveBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('saveBtn'),
                    icon: Editor.data.moduleFolder + 'images/tick.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.save}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'cancelBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('cancelBtn'),
                    icon: Editor.data.moduleFolder + 'images/cross.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.cancel}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'resetSegmentBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('resetSegmentBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_undo.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.reset}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'goToUpperByWorkflowNoSaveBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('goToUpperByWorkflowNoSaveBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_up_filtered_nosave.png ',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.prevFiltered}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'saveNextByWorkflowBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('saveNextByWorkflowBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_down_filtered.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.saveAndNextFiltered}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'goToLowerByWorkflowNoSaveBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('goToLowerByWorkflowNoSaveBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_down_filtered_nosave.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.nextFiltered}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'savePreviousBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('savePreviousBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_up.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.saveAndPrevious}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'goToUpperNoSaveBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('goToUpperNoSaveBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_up_nosave.png ',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.prev}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'goAlternateLeftBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('goAlternateLeftBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_left.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.alternateLeft}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'saveNextBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('saveNextBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_down.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.saveAndNext}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'goToLowerNoSaveBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('goToLowerNoSaveBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_down_nosave.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.next}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    itemId: 'goAlternateRightBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('goAlternateRightBtn'),
                    icon: Editor.data.moduleFolder + 'images/arrow_right.png',
                    bind: {
                        tooltip: '{l10n.segmentGrid.toolbar.alternateRight}',
                        disabled: '{!isEditingSegment}',
                    }
                }, {
                    xtype: 'button',
                    itemId: 'focusSegmentShortcutBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('focusSegmentShortcutBtn'),
                    bind: {
                        tooltip:{
                            dismissDelay: 0,
                            text: '{scrollToTooltip}' //is a formula!
                        }
                    },
                    icon: Editor.data.moduleFolder + 'images/scrollTo.png',
                    iconAlign: 'right'
                }, {
                    itemId: 'watchSegmentBtn',
                    dispatcher: true,
                    hidden: !~checkedItems.indexOf('watchSegmentBtn'),
                    icon: Editor.data.moduleFolder + 'images/star.png',
                    enableToggle: true,
                    bind: {
                        pressed: '{segmentIsWatched}',
                        icon: Editor.data.moduleFolder + 'images/{segmentIsWatched ? "star_remove" : "star_add"}.png',
                        tooltip: {
                            text: '{segmentIsWatched ? l10n.segmentGrid.toolbar.stopWatchingSegment : l10n.segmentGrid.toolbar.startWatchingSegment}',
                            showDelay: 0
                        }
                    }
                }, {
                    xtype: 'button',
                    itemId: 'segmentLockBtn',
                    hidden: !~checkedItems.indexOf('segmentLockBtn'),
                    enableToggle: true,
                    bind: {
                        icon: Editor.data.moduleFolder + 'images/{segmentIsEditable ? "lock_open" : "lock"}.png',
                        pressed: '{!segmentIsEditable}',
                        // to reduce problems of update the opened segment we just prohibit usage if a segment is in editing
                        disabled: '{isEditingSegment || !selectedSegment || segmentIsBlocked}',
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.segmentLockBtn}',
                            showDelay: 0
                        }
                    },
                }, {
                    xtype: 'button',
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.all}'
                    },
                    menu: {
                        itemId: 'segmentActionMenu',
                        stateId: 'editor.segmentActionMenu',
                        stateful: {
                            checkedItems: true
                        },
                        defaults: {
                            xtype: 'menucheckitem',
                            allowCheckChange: false,
                            checkableDespiteDisabled: true,
                            bind: {
                                checkboxTooltip: '{l10n.segmentGrid.toolbar.allMenuCheckTooltip}'
                            }
                        },
                        items: [{
                            itemId: 'saveBtn',
                            checked: !!~checkedItems.indexOf('saveBtn'),
                            icon: Editor.data.moduleFolder + 'images/tick.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.save}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'cancelBtn',
                            checked: !!~checkedItems.indexOf('cancelBtn'),
                            icon: Editor.data.moduleFolder + 'images/cross.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.cancel}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'resetSegmentBtn',
                            checked: !!~checkedItems.indexOf('resetSegmentBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_undo.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.reset}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, '-', {
                            itemId: 'goToUpperByWorkflowNoSaveBtn',
                            checked: !!~checkedItems.indexOf('goToUpperByWorkflowNoSaveBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_up_filtered_nosave.png ',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.prevFiltered}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'saveNextByWorkflowBtn',
                            checked: !!~checkedItems.indexOf('saveNextByWorkflowBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_down_filtered.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.saveAndNextFiltered}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'goToLowerByWorkflowNoSaveBtn',
                            checked: !!~checkedItems.indexOf('goToLowerByWorkflowNoSaveBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_down_filtered_nosave.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.nextFiltered}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, '-', {
                            itemId: 'savePreviousBtn',
                            checked: !!~checkedItems.indexOf('savePreviousBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_up.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.saveAndPrevious}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'goToUpperNoSaveBtn',
                            checked: !!~checkedItems.indexOf('goToUpperNoSaveBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_up_nosave.png ',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.prev}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'goAlternateLeftBtn',
                            checked: !!~checkedItems.indexOf('goAlternateLeftBtn'),
                            hidden: !useHNavArrow,
                            icon: Editor.data.moduleFolder + 'images/arrow_left.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.alternateLeft}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'saveNextBtn',
                            checked: !!~checkedItems.indexOf('saveNextBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_down.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.saveAndNext}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'goToLowerNoSaveBtn',
                            checked: !!~checkedItems.indexOf('goToLowerNoSaveBtn'),
                            icon: Editor.data.moduleFolder + 'images/arrow_down_nosave.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.next}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, {
                            itemId: 'goAlternateRightBtn',
                            checked: !!~checkedItems.indexOf('goAlternateRightBtn'),
                            hidden: !useHNavArrow,
                            icon: Editor.data.moduleFolder + 'images/arrow_right.png',
                            bind: {
                                text: '{l10n.segmentGrid.toolbar.alternateRight}',
                                disabled: '{!isEditingSegment}',
                            }
                        }, '-', {
                            itemId: 'focusSegmentShortcutBtn',
                            checked: !!~checkedItems.indexOf('focusSegmentShortcutBtn'),
                            bind: {
                                text: '{scrollToTooltip}' //is a formula!
                            },
                            icon: Editor.data.moduleFolder + 'images/scrollTo.png',
                        }, {
                            itemId: 'watchSegmentBtn',
                            checked: !!~checkedItems.indexOf('watchSegmentBtn'),
                            icon: Editor.data.moduleFolder + 'images/star_add.png',
                            bind: {
                                text: '{segmentIsWatched ? l10n.segmentGrid.toolbar.stopWatchingSegment : l10n.segmentGrid.toolbar.startWatchingSegment}',
                                icon: Editor.data.moduleFolder + 'images/{segmentIsWatched ? "star_remove" : "star_add"}.png'
                            }
                        }, {
                            itemId: 'segmentLockBtn',
                            checked: !!~checkedItems.indexOf('segmentLockBtn'),
                            hidden: !Editor.app.authenticatedUser.isAllowed('lockSegmentOperation') || !Editor.app.authenticatedUser.isAllowed('unlockSegmentOperation'),
                            icon: Editor.data.moduleFolder + 'images/lock.png',
                            bind: {
                                icon: Editor.data.moduleFolder + 'images/{segmentIsEditable ? "lock_open" : "lock"}.png',
                                disabled: '{isEditingSegment || !selectedSegment || segmentIsBlocked}',
                                text: '{l10n.segmentGrid.toolbar.segmentLockBtn}'
                            }
                        }]
                    }
                }, {
                    xtype: 'button',
                    glyph: 'f141@FontAwesome5FreeSolid',
                    itemId: 'batchOperations',
                    bind: {
                        text: '{l10n.segmentGrid.batchOperations.btnText}',
                        tooltip: '{l10n.segmentGrid.batchOperations.btnTooltip}',
                        disabled: '{isEditingSegment}',
                    },
                    menu: {
                        xtype: 'menu',
                        items: [{
                            bind: {
                                text: '{l10n.segmentGrid.batchOperations.menuText}'
                            }
                        },{
                            hidden: !Editor.app.authenticatedUser.isAllowed('lockSegmentBatch') || !Editor.app.authenticatedUser.isAllowed('unlockSegmentBatch'),
                            icon: Editor.data.moduleFolder+'images/lock.png',
                            operation: 'lock',
                            bind: {
                                text: '{l10n.segmentGrid.batchOperations.menuLock}'
                            }
                        },{
                            hidden: !Editor.app.authenticatedUser.isAllowed('lockSegmentBatch') || !Editor.app.authenticatedUser.isAllowed('unlockSegmentBatch'),
                            icon: Editor.data.moduleFolder+'images/lock_open.png',
                            operation: 'unlock',
                            bind: {
                                text: '{l10n.segmentGrid.batchOperations.menuUnlock}'
                            }
                        },{
                            icon: Editor.data.moduleFolder+'images/star_add.png',
                            operation: 'bookmark',
                            bind: {
                                text: '{l10n.segmentGrid.batchOperations.menuBookmark}'
                            }
                        },{
                            icon: Editor.data.moduleFolder+'images/star_remove.png',
                            operation: 'unbookmark',
                            bind: {
                                text: '{l10n.segmentGrid.batchOperations.menuUnbookmark}'
                            }
                        }]
                    }
                },{
                    xtype: 'button',
                    itemId: 'specialChars',
                    hidden: !(userCanModifyWhitespaceTags && userCanInsertWhitespaceTags),
                    bind: {
                        text: '{l10n.segmentGrid.toolbar.chars}',
                        disabled: '{!isEditingSegment}',
                    },
                    menu: {
                        setOwnerCmp: function(ownerCmp) {
                            this.ownerCmp = ownerCmp;
                        },
                        floating: true,
                        disabled: false,
                        xtype: 'specialCharacters'
                    }
                },{
                	xtype: 'tbfill'
                },{
                    xtype: 'button',
                    //FIXME let me come from a config:
                    href: 'http://confluence.translate5.net/display/BUS/Editor+keyboard+shortcuts',
                    hrefTarget: '_blank',
                    icon: Editor.data.moduleFolder + 'images/help.png',
                    bind: {
                        tooltip: {
                            text: '{l10n.segmentGrid.toolbar.helpTooltip}',
                            showDelay: 0
                        }
                    }
                }]
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Create the theme menu picker config
     * @returns {{text: string, menu: {items: *[]}}}
     */
    getThemeMenuConfig:function(){
        var me=this,
            config,
            uiThemesRecord = Editor.app.getUserConfig('extJs.theme',true),
            menuItems = [];

        Ext.Array.each(uiThemesRecord.get('defaults'), function(i) {
            menuItems.push({
                text: (Editor.data.frontend.config.themesName[i] !== undefined) ? Editor.data.frontend.config.themesName[i]  :  Ext.String.capitalize(i),
                value: i,
                checked: uiThemesRecord.get('value') === i,
                group: 'uiTheme',
                handler: function (item){
                    Editor.app.changeUserTheme(item.value);
                }
            });
        });

        config = {
            bind: {
                text: '{l10n.segmentGrid.toolbar.themeMenuConfigText}'
            },
            menu: {
                items: menuItems
            }
        };

        return config;
    }
});