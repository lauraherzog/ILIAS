<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilObjContentPage
 */
class ilObjContentPage extends \ilObject2 implements \ilContentPageObjectConstants
{
    /**
     * @var int
     */
    protected $styleId = 0;

    /**
     * @inheritdoc
     */
    protected function initType()
    {
        $this->type = self::OBJ_TYPE;
    }

    /**
     * @return int
     */
    public function getStyleSheetId() : int
    {
        return (int) $this->styleId;
    }

    /**
     * @param int $styleId
     */
    public function setStyleSheetId(int $styleId)
    {
        $this->styleId = $styleId;
    }

    /**
     * @param int $styleId
     */
    public function writeStyleSheetId(int $styleId)
    {
        $this->db->manipulateF(
            'UPDATE content_object SET stylesheet = %s WHERE id = %s',
            ['integer', 'integer'],
            [(int) $styleId, $this->getId()]
        );

        $this->setStyleSheetId($styleId);
    }

    /**
     * @inheritdoc
     */
    protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = null)
    {
        /** @var $new_obj self */
        parent::doCloneObject($new_obj, $a_target_id, $a_copy_id);

        if (\ilContentPagePage::_exists($this->getType(), $this->getId())) {
            $originalPageObject = new \ilContentPagePage($this->getId());
            $copiedXML = $originalPageObject->copyXmlContent();

            $duplicatePageObject = new \ilContentPagePage();
            $duplicatePageObject->setId($new_obj->getId());
            $duplicatePageObject->setParentId($new_obj->getId());
            $duplicatePageObject->setXMLContent($copiedXML);
            $duplicatePageObject->createFromXML();
        }

        $styleId = $this->getStyleSheetId();
        if ($styleId > 0 && !\ilObjStyleSheet::_lookupStandard($styleId)) {
            $style = \ilObjectFactory::getInstanceByObjId($styleId, false);
            if ($style) {
                $new_id = $style->ilClone();
                $new_obj->setStyleSheetId($new_id);
                $new_obj->update();
            }
        }

        \ilContainer::_writeContainerSetting(
            $new_obj->getId(),
            \ilObjectServiceSettingsGUI::INFO_TAB_VISIBILITY,
            \ilContainer::_lookupContainerSetting(
                $this->getId(),
                \ilObjectServiceSettingsGUI::INFO_TAB_VISIBILITY,
                true
            )
        );

        $lpSettings = new ilLPObjSettings($this->getId());
        $lpSettings->cloneSettings($new_obj->getId());
    }

    /**
     * @inheritdoc
     */
    protected function doRead()
    {
        parent::doRead();

        $res = $this->db->queryF(
            'SELECT * FROM content_page_data WHERE content_page_id = %s',
            ['integer'],
            [$this->getId()]
        );

        while ($data = $this->db->fetchAssoc($res)) {
            $this->setStyleSheetId((int) $data['stylesheet']);
        }
    }

    /**
     * @inheritdoc
     */
    protected function doCreate()
    {
        parent::doCreate();

        $this->db->manipulateF(
            '
			INSERT INTO content_page_data 
			( 
			 	content_page_id,
				stylesheet
			)
			VALUES(%s, %s)',
            ['integer', 'integer'],
            [$this->getId(), 0]
        );
    }


    /**
     * @inheritdoc
     */
    protected function doUpdate()
    {
        parent::doUpdate();

        $this->db->manipulateF(
            '
			UPDATE content_page_data
			SET
				stylesheet = %s
			WHERE content_page_id = %s',
            ['integer', 'integer'],
            [$this->getStyleSheetId(), $this->getId()]
        );
    }

    /**
     * @inheritdoc
     */
    protected function doDelete()
    {
        parent::doDelete();

        if (\ilContentPagePage::_exists($this->getType(), $this->getId())) {
            $originalPageObject = new \ilContentPagePage($this->getId());
            $originalPageObject->delete();
        }
    }

    /**
     * @return int[]
     */
    public function getPageObjIds() : array
    {
        $pageObjIds = [];

        $sql = "SELECT page_id FROM page_object WHERE parent_id = %s AND parent_type = %s";
        $res = $this->db->queryF(
            $sql,
            ['integer', 'text'],
            [$this->getId(), $this->getType()]
        );

        while ($row = $this->db->fetchAssoc($res)) {
            $pageObjIds[] = $row['page_id'];
        }

        return $pageObjIds;
    }

    /**
     * @param int $usrId
     */
    public function trackProgress(int $usrId)
    {
        \ilChangeEvent::_recordReadEvent(
            $this->getType(),
            $this->getRefId(),
            $this->getId(),
            $usrId
        );

        $lp = ilObjectLP::getInstance($this->getId());
        if ($lp->isActive() && ((int) $lp->getCurrentMode() === ilLPObjSettings::LP_MODE_CONTENT_VISITED)) {
            $current_status = (int) ilLPStatus::_lookupStatus($this->getId(), $usrId, false);
            if ($current_status !== ilLPStatus::LP_STATUS_COMPLETED_NUM) {
                ilLPStatusWrapper::_updateStatus(
                    $this->getId(),
                    $usrId
                );
            }
        }
    }
}
