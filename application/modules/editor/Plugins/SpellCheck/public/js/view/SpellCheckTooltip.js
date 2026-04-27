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

/**
 * Reusable spellcheck context-menu popup.
 *
 * Usage — pass callbacks at creation time, then populate with match data before showing:
 *
 *   const tooltip = Ext.create('Editor.plugins.SpellCheck.view.SpellCheckTooltip', {
 *       callbacks: {
 *           onReplace:         (replacement) => { ... },
 *           onReplaceAll:      (replacement) => { ... },
 *           onIgnore:          ()            => { ... },
 *           onIgnoreAll:       ()            => { ... },
 *           onSaveDraftChange: (checked)     => { ... },
 *       },
 *   });
 *
 *   tooltip.loadMatch({ message, replacements, infoURLs });
 *   tooltip.showAt(x, y);
 */
Ext.define('Editor.plugins.SpellCheck.view.SpellCheckTooltip', {
    extend: 'Ext.menu.Menu',
    alias: 'widget.spellchecktooltip',

    // How many proposal rows are fully visible before the list scrolls
    PROPOSALS_VISIBLE_ROWS: 5,
    // Approximate row height in pixels (matches the CSS line-height)
    PROPOSAL_ROW_HEIGHT: 28,

    minWidth: 260,
    plain: true,

    /**
     * Callback functions for each user action.
     * All keys are optional — omitted callbacks are silently ignored.
     *
     * @cfg {{ onReplace, onReplaceAll, onIgnore, onIgnoreAll, onSaveDraftChange }} callbacks
     */
    callbacks: null,

    /** @private — set via loadMatch() before showAt() */
    _match: null,
    _segment: null,

    initComponent: function () {
        const me = this;
        const l10n = Editor.data.l10n.SpellCheck;
        const callbacks = this.callbacks || {};

        Ext.apply(me, {
            renderTo: Ext.getBody(),
            items: [
                // Message header (text updated in loadMatch)
                {
                    itemId: 'messageHeader',
                    text: '',
                    cls: 'spellcheck-tooltip-header',
                    canActivate: false,
                    hideOnClick: false,
                },
                '-',
                // Ignore
                {
                    itemId: 'ignoreItem',
                    cls: 'spellcheck-action-ignore',
                    hidden: !callbacks.onIgnore,
                    handler: () => callbacks.onIgnore?.(me._match, me._segment),
                },
                // Ignore All
                {
                    itemId: 'ignoreAllItem',
                    cls: 'spellcheck-action-ignore-all',
                    hidden: !callbacks.onIgnoreAll,
                    handler: () => callbacks.onIgnoreAll?.(me._match, me._segment),
                },
                '-',
                // Proposals placeholder — rebuilt fresh on every beforeshow
                {
                    itemId: 'proposalsItem',
                    xtype: 'component',
                    cls: 'spellcheck-proposals-wrap',
                    html: '',
                },
                '-',
                // Save as Draft checkbox
                {
                    xtype: 'menucheckitem',
                    itemId: 'saveAsDraft',
                    text: l10n.saveAsDraft,
                    cls: 'spellcheck-action-save-draft',
                    hidden: !callbacks.onSaveDraftChange,
                    checkHandler: (item, checked) => callbacks.onSaveDraftChange?.(checked),
                },
            ],
            listeners: {
                beforeshow: () => me._renderProposals(),
            },
        });

        me.callParent(arguments);
    },

    /**
     * Store the match data and update the static header text.
     * Call this before showAt().
     *
     * @param {Editor.model.Segment} segment
     * @param {{ message: string, replacements: string[], infoURLs: string[] }|null} match
     */
    loadMatch: function (segment, match) {
        this._segment = segment;
        this._match = match;

        const l10n = Editor.data.l10n.SpellCheck;
        const isFalsePositive = match && match.falsePositive;

        const header = this.down('#messageHeader');
        if (header) {
            header.setText(match ? `<b>${match.message}</b>` : '');
        }

        const ignoreItem = this.down('#ignoreItem');
        if (ignoreItem) {
            ignoreItem.setText(isFalsePositive ? l10n.unignoreError : l10n.ignoreError);
        }

        const ignoreAllItem = this.down('#ignoreAllItem');
        if (ignoreAllItem) {
            ignoreAllItem.setText(isFalsePositive ? l10n.unignoreAllSameErrors : l10n.ignoreAllSameErrors);
        }
    },

    /**
     * (Re)build the proposals HTML inside the placeholder item.
     * Called on beforeshow so it always runs against a freshly visible menu.
     * @private
     */
    _renderProposals: function () {
        const match = this._match;
        const placeholder = this.down('#proposalsItem');

        if (!placeholder) {
            return;
        }

        const l10n = Editor.data.l10n.SpellCheck;

        if (!match || !match.replacements.length) {
            placeholder.setHtml(l10n.noSuggestions);

            return;
        }

        const maxHeight = `${this.PROPOSALS_VISIBLE_ROWS * this.PROPOSAL_ROW_HEIGHT}px`;

        const rows = match.replacements
            .map((replacement, i) => {
                const displayText = Ext.htmlEncode(replacement).replace(/ /g, '&nbsp;');

                const replaceAll = this.callbacks.onReplaceAll && match.id
                    ? `<span class="spellcheck-replace-all-btn x-fa fa-retweet" data-replaceall="1" data-idx="${i}" title="${Ext.htmlEncode(l10n.replaceAllWithProposal)}"></span>`
                    : '';

                return `<div id="btnReplaceAll" class="spellcheck-proposal-row" data-idx="${i}">
                        <span class="spellcheck-proposal-label" title="${Ext.htmlEncode(l10n.replaceWithProposal)}">${displayText}</span>
                        ${replaceAll}
                    </div>`;
            })
            .join('');

        placeholder.setHtml(
            `<div class="spellcheck-proposal-list" style="overflow-y:auto;max-height:${maxHeight}">${rows}</div>`,
        );

        // Re-attach delegated click listener (un first to avoid duplicates across shows)
        placeholder.el.un('click', this._onProposalClick, this);
        placeholder.el.on('click', this._onProposalClick, this);
    },

    /**
     * Delegated click handler for the proposals HTML block.
     * @private
     */
    _onProposalClick: function (event, target) {
        const callbacks = this.callbacks || {};

        const rowEl =
            Ext.fly(target).up('.spellcheck-proposal-row', null, true) ||
            (Ext.fly(target).hasCls('spellcheck-proposal-row') ? target : null);

        if (!rowEl) {
            return;
        }

        const idx = parseInt(rowEl.getAttribute('data-idx'), 10);
        const replacement = this._match.replacements[idx];
        const isReplaceAll =
            Ext.fly(target).hasCls('spellcheck-replace-all-btn') ||
            !!Ext.fly(target).up('.spellcheck-replace-all-btn', null, true);
        const saveAsDraft = !!this.down('#saveAsDraft')?.checked;

        if (isReplaceAll) {
            callbacks.onReplaceAll?.(this._match, replacement, saveAsDraft);
        } else {
            callbacks.onReplace?.(this._match, replacement, this._segment, saveAsDraft);
        }
    },
});
