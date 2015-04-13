<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Segment TermTag Recreator
 */
class editor_Models_Segment_TermTag {
    /**
     * @var type Zend_Config
     */
    protected $config = null;

    /**
     * internal counter
     * @var integer
     */
    protected $termNr = 0;
    
    /**
     * internal container for the replacements
     * @var array
     */
    protected $replacements;
    
    /**
     */
    public function __construct() {
        $this->config = Zend_Registry::get('config');
        $this->db = Zend_Registry::get('db');
    }
    
    /**
     * recreates the term markup in the segmentContent
     * @param integer $segmentId segment id
     * @param string $segmentContent segment content to be processed
     * @param boolean $useSource optional, default false, if true terms of source column are used (instead of target)
     */
    public function recreate($segmentId, $segmentContent, $useSource = false) {
        //gibt alle Terme und zugehörige MetaDaten zu den im Segment verwendeten Terminstanzen zurück
        //sortiert nach String Länge, und bearbeitet die längsten Strings zuerst. 
        $sql = 'select i.term, i.projectTerminstanceId, t.mid, t.status, s2t.transFound, t.definition
          from LEK_terminstances i, LEK_terms t, LEK_segments2terms s2t
          where i.segmentId = ? and i.segmentId = s2t.segmentId and t.id = i.termId
              and s2t.termId = i.termId and s2t.used and s2t.isSource = ? order by length(i.term) desc';
        $isSource = $useSource ? 1 : 0;
        $stmt = $this->db->query($sql, array($segmentId, $isSource));
        $terms = $stmt->fetchAll();

        $this->termNr = 0; //laufende Nummer der Term Tags
        $this->replacements = array();

        foreach ($terms as $term) {
            $termData = new editor_Models_TermTagData();
            $termData->isSource = $useSource;
            foreach ($term as $key => $value) {
                $termData->$key = $value;
            }
            $searchLength = mb_strlen($term['term']);
            $segmentContent = $this->findTerminstancesTagAware($termData, $segmentContent, $searchLength);
        }
        //Im zweiten Schritt die Platzhalter durch die Terminstanzen ersetzen.
        //Der Umweg über die uniquen Platzhalter ist nötig, da ansonsten gleichlautende Terminstanzen mehrfach mit sich selbst ersetzt und damit mehrere divs hinzugefügt werden
        return str_replace(array_keys($this->replacements), $this->replacements, $segmentContent);
    }

    /**
     * ensures, that a term does not match content inside internal tags
     * 
     * @param editor_Models_TermTagData $termData 
     * @param string $segment
     * @param integer $searchLength
     * @return string $segment
     */
    protected function findTerminstancesTagAware($termData, $segment, $searchLength) {
        //if there is an internal tag in the term, the term will not match strings inside internal tags
        if (preg_match($this->config->runtimeOptions->editor->segment->recreateTermTags->regexInternalTags, $termData->term)) {
            return $this->findTerminstances($termData, $segment, $searchLength);
        }
        //otherwhise we ensure, that internal tags will not be matched by termRecreation
        $segmentArr = preg_split($this->config->runtimeOptions->editor->segment->recreateTermTags->regexInternalTags, $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($segmentArr);
        for ($i = 0; $i < $count; $i = $i + 2) {//the uneven numbers are the tags, we skip them
            $segmentArr[$i] = $this->findTerminstances($termData, $segmentArr[$i], $searchLength);
        }
        return implode('', $segmentArr);
    }

    /**
     * Durchsucht das Segment nach dem Term und ordnet die Fundstellen nach einer Priorisierung ob diese Fundstelle ein eigenständiges Wort ist, oder aber durch andere Zeichen abgegrenzt ist. 
     * Handelt es sich um eine Teilzeichenkette anstatt einem eigenständige Wort, wird die Fundstelle ignoriert.
     * 
     * @param editor_Models_TermTagData $termData 
     * @param string $segment
     * @param integer $searchLength
     * @param integer $offset
     * @return string $segment
     */
    protected function findTerminstances($termData, $segment, $searchLength, $offset = 0) {
        $term = $termData->term;
        $pos = mb_strpos($segment, $term, $offset);
        if ($pos === false) {
            $pos = mb_stripos($segment, $term, $offset);
        }
        $offset = $pos + $searchLength;
        if ($searchLength > 1) {
            $offset--;
        }
        $segLength = mb_strlen($segment);
        if ($pos === false || $offset >= $segLength) {
            return $segment;
        }
        //holt Term für die Ersetzung und Rückgabe
        $term2Mark = mb_substr($segment, $pos, $searchLength);

        //holt die Zeichen vor und nach dem gesuchten Term
        if ($pos === 0) {
            $leftChar = '';
        } else {
            $leftChar = trim(mb_substr($segment, $pos - 1, 1));
        }
        $rightChar = trim(mb_substr($segment, $pos + $searchLength, 1));

        /**
         * Im folgenden werden die linken und rechten Zeichen nicht per RegEx oder gegen Zeichenliste verglichen,
         * sondern mit trim eine Zeichenliste angewendet und dann das Resultat analysiert (im Regelfall Ergebnis == Leerstring.
         * Das erscheint mir pragmatischer und schneller als per RegEx o.ä.
         * Im folgenden werden die linken und rechten Zeichen in drei Prios eingeteilt 0,1,2 in dieser Reihenfolge werden Sie dann später auch ersetzt.
         * Das heißt wenn der Term "foo" ist, dann wird im String "bar foo<lala> bar foo dada" das zweite "foo" zuerst ersetzt, 
         * da ein whitespace als Wortgrenze höher als ein Tag angesehen wird.    
         */
        if ($rightChar === '' && $leftChar === '') {
            return $this->replace($termData, $segment, $pos, $searchLength, $segLength, $term2Mark);
        }

        $rightChar = trim($rightChar, '<');
        $leftChar = trim($leftChar, '>');

        if ($rightChar === '' && $leftChar === '') {
            return $this->replace($termData, $segment, $pos, $searchLength, $segLength, $term2Mark);
        }

        $wordspacers = '.,&;:?!„“\'"…·|「」『』»«›‹¡’‚';
        $rightChar = trim($rightChar, $wordspacers);
        $leftChar = trim($leftChar, $wordspacers);

        if ($rightChar === '' && $leftChar === '') {
            return $this->replace($termData, $segment, $pos, $searchLength, $segLength, $term2Mark);
        }
        //weiter suchen, aber die Fundstelle nicht erfassen.
        return $this->findTerminstances($termData, $segment, $searchLength, $offset);
    }
    
    /**
     * internal helper method
     * @param editor_Models_TermTagData $termData
     * @param string $segment
     * @param integer $pos
     * @param integer $searchLength
     * @param integer $segLength
     * @param string $term2Mark
     */
    protected function replace(editor_Models_TermTagData $termData, $segment, $pos, $searchLength, $segLength, $term2Mark) {
        $placeholder = '#~<~#' . $this->termNr++ . '#~>~#';
        $segment = mb_substr($segment, 0, $pos) . $placeholder . mb_substr($segment, $pos + $searchLength);
        $offset = $pos + strlen($placeholder) - 1;
        if ($offset < $segLength) {
            $segment = $this->findTerminstances($termData, $segment, $searchLength, $offset);
        }
        $termData->term = $term2Mark;
        $this->replacements[$placeholder] = $this->getGeneratedTermTag($termData);
        return $segment;
    }

    /**
     * erstellt den term div tag anhand den gegebene Daten
     * @param editor_Models_TermTagData $termData
     * @param boolean $transFound
     */
    public function getGeneratedTermTag(editor_Models_TermTagData $termData) {
        $class = array('term', $termData->status);
        if ($termData->isSource) {
            $class[] = ($termData->transFound ? 'transFound' : 'transNotFound');
        }
        $class = join(' ', $class);
        $id = join('-', array($termData->mid, $termData->projectTerminstanceId));
        return sprintf('<div class="%1$s" id="%2$s" title="%4$s">%3$s</div>', $class, $id, $termData->term, htmlspecialchars($termData->definition));
    }
}