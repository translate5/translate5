<?php
/***
 * Handle the current active term.
 * 1. update the term if:
 *    - same termEntryId (tbx)
 *    - same termId (tbx)
 *    - same collectionId
 * 2: Get all terms with same termEntryId (tbx), same collectionId and different termId (tbx)
 *       2a. Update the term if
 *          - same termEntryId(tbx)
 *          - same language
 *          - same term Text
 *
 *       2b. Add new term if
 *          - no term from 2a is update
 * 3. Add new term if :
 *      - mergeTerms=false
 *    3a. Update the term if:
 *      - same collectionId
 *      - same language
 *      - same termText
 *    3b. Add new term(terms-all collected terms in the same term entry) if no matched terms from 3a
 *
 */

class editor_Models_Terminology_Import_TermMerge
{
    /** @var editor_Models_Terminology_Models_TermModel */
    protected editor_Models_Terminology_Models_TermModel $termModel;

    public function __construct()
    {
        $this->termModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
    }

    /**
     * @param array $preparedSql
     * @return array
     */
    public function createOrUpdateTerms(array $preparedSql): array
    {
        $sqlUpdate = $preparedSql['sqlUpdate'];
        $sqlInsert = $preparedSql['sqlInsert'];
        $sqlParam = $preparedSql['sqlParam'];

        if ($sqlUpdate) {
            $this->termModel->updateTerms($sqlUpdate);
        }

        if ($sqlInsert) {
            $this->termModel->createTerms($sqlParam, $sqlInsert['tableFields'], $sqlInsert['tableValue']);
        }

        return [$sqlUpdate];
    }
}
