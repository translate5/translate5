<?php

class editor_Models_Terminology_Import_ImagesMerge {
    /** @var editor_Models_Terminology_Models_ImagesModel */
    protected editor_Models_Terminology_Models_ImagesModel $imagesModel;

    public function __construct() {
        $this->imagesModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');
    }
}
