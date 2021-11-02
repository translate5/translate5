<?php
trait editor_Controllers_Traits_TermportalTrait {

    /**
     * Alias for editor_Utils::jcheck(), except that if $data arg is not given - request params will be used by default
     *
     * @param $ruleA
     * @param array|null|ZfExtended_Models_Entity_Abstract $data
     * @return array
     * @see editor_Utils::jcheck
     * @throws ZfExtended_Mismatch
     */
    public function jcheck($ruleA, $data = null) {
        return editor_Utils::jcheck($ruleA, $data ?? $this->getRequest()->getParams());
    }
}
