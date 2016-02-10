<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2015 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU Affero General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
     * details.
     *
     * You should have received a copy of the GNU Affero General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU Affero General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2015. All rights reserved".
     ********************************************************************************/

    /**
     * Extends the StackedExtendedGridView to provide a 'stacked' Kanban Board format for viewing lists of data.
     */
    class KanbanBoardExtendedGridView extends StackedExtendedGridView
    {
        /**
         * Override since Kanban Board does not support pagination. It would always show all available regardless of
         * pagination.
         * @var bool
         */
        public $enablePagination = false;

        /**
         * @var string
         */
        public $groupByAttribute;

        /**
         * @var array
         */
        public $groupByAttributeVisibleValues = array();

        /**
         * @var array
         */
        public $groupByDataAndTranslatedLabels = array();

        /**
         * @var array
         */
        public $cardColumns = array();

        /**
         * @var string
         */
        public $selectedTheme;

        /**
         * @return int
         */
        public static function resolvePageSizeForMaxCount()
        {
            return static::getMaxCount();
        }

        /**
         * @return int
         */
        public static function getMaxCount()
        {
            $maxCount = Yii::app()->pagination->getGlobalValueByType('kanbanBoardPageSize');
            return (int) $maxCount;
        }

        public function init()
        {
            $this->registerScripts();
            parent::init();
        }

        /**
         * Renders the table body.
         */
        public function renderTableBody()
        {
            $data               = $this->dataProvider->getData();
            $modelsCount        = count($data);
            $totalModelsCount   = $this->dataProvider->calculateTotalItemCount();
            $columnsData        = $this->resolveDataIntoKanbanColumns();
            $width              = 100 / count($columnsData);
            echo "<tbody>";
            echo "<tr><td id=\"kanban-holder\" class='". $this->selectedTheme . "'>";
            if ($modelsCount > 0)
            {
                if ($this->isMaxCountCheckRequired() && $modelsCount == static::getMaxCount() &&
                        $totalModelsCount > static::getMaxCount())
                {
                    $this->renderOverMaxCountText($totalModelsCount);
                }
                $counter = 0;
                echo "<div id=\"kanban-board\">";
                foreach ($columnsData as $attributeValue => $attributeValueAndData)
                {
                    echo '<div class="kanban-column" style="width:'.$width.'%;">'; // Not Coding Standard
                    echo "<div data-value='" . $attributeValue . "' class='droppable-dynamic-rows-container'>";
                    echo ZurmoHtml::tag('div', array('class' => 'column-header'), $this->resolveGroupByColumnHeaderLabel($attributeValue));
                    $listItems = $this->getListItemsByAttributeValueAndData($attributeValueAndData);
                    echo $this->renderUlTagForKanbanColumn($listItems, $attributeValue);
                    $dropZone =  ZurmoHtml::tag('div', array('class' => 'drop-zone'), '');
                    echo ZurmoHtml::tag('div', array('class' => 'drop-zone-container'), $dropZone);
                    echo "</div>";
                    echo "</div>";
                    $counter++;
                }
                echo "</div>";
            }
            else
            {
                $this->renderEmptyText();
            }
            echo "</td></tr>";
            echo "</tbody>";
        }

        /**
         * Renders the message when the total count exceeds the max count of items allowed.
         * @param int $totalCount
         */
        public function renderOverMaxCountText($totalCount)
        {
            $label = Zurmo::t('Core', 
                'Too many results to display. Showing the first {maxCount} of {totalCount} records. Try filtering your search or switching to the grid view.',
                array('{maxCount}' => static::getMaxCount(), '{totalCount}' => $totalCount));
            $content  = '<div class="general-issue-notice"><span class="icon-notice"></span><p>';
            $content .= $label;
            $content .= '</p></div>';
            echo $content;
        }

        /**
         * @return int
         */
        protected function getOffset()
        {
            $pagination = $this->dataProvider->getPagination();
            if (isset($pagination))
            {
                $offset = $pagination->getOffset();
            }
            else
            {
                $offset = 0;
            }
            return $offset;
        }

        /**
         * @param $value
         * @return string
         */
        protected function resolveGroupByColumnHeaderLabel($value)
        {
            if (isset($this->groupByDataAndTranslatedLabels[$value]))
            {
                return $this->groupByDataAndTranslatedLabels[$value];
            }
            return $value;
        }

        /**
         * @return array
         */
        protected function resolveDataIntoKanbanColumns()
        {
            $columnsData = $this->makeColumnsDataAndStructure();
            foreach ($this->dataProvider->data as $row => $data)
            {
                if (isset($columnsData[$data->{$this->groupByAttribute}->value]))
                {
                    $columnsData[$data->{$this->groupByAttribute}->value][] = $row;
                }
            }
            return $columnsData;
        }

        /**
         * @return array
         */
        protected function makeColumnsDataAndStructure()
        {
            $columnsData = array();
            foreach ($this->groupByAttributeVisibleValues as $value)
            {
                $columnsData[$value] = array();
            }
            return $columnsData;
        }

        protected function registerScripts()
        {
            Yii::app()->clientScript->registerScriptFile(
                Yii::app()->getAssetManager()->publish(
                    Yii::getPathOfAlias('application.core.kanbanBoard.widgets.assets')) . '/KanbanUtils.js');
            $script = '
                $(".droppable-dynamic-rows-container").live("drop", function(event, ui)
                {
                   ' . $this->getAjaxForDroppedAttribute() . '
                   $("ul", this).append(ui.draggable);
                });
                setupKanbanDragDrop();
            ';
            Yii::app()->getClientScript()->registerScript('KanbanDragDropScript', $script);
        }

        /**
         * @return string
         */
        protected function getAjaxForDroppedAttribute()
        {
            return ZurmoHtml::ajax(array(
                'type'     => 'GET',
                'url'      => 'js:$.param.querystring("' . $this->getUpdateAttributeValueUrl() .
                              '", "id=" + ui.helper.attr("id") + "&value=" + $(this).data("value"))',
                'beforeSend' => 'js:function()
                    {
                        $(".ui-overlay-block").fadeIn(50);
                        $(this).makeLargeLoadingSpinner(true, ".ui-overlay-block"); //- add spinner to block anything else
                    }',
                'success' => 'js:function(data)
                    {
                        $(this).makeLargeLoadingSpinner(false, ".ui-overlay-block");
                        $(".ui-overlay-block").fadeOut(50);
                    }'
            ));
        }

        /**
         * @return string
         */
        protected function getUpdateAttributeValueUrl()
        {
            $modelClassName  = $this->dataProvider->getModelClassName();
            $moduleClassName = $modelClassName::getModuleClassName();
            $moduleId        = $moduleClassName::getDirectoryName();
            return  Yii::app()->createUrl($moduleId . '/default/updateAttributeValue', array('attribute' => $this->groupByAttribute));
        }

        /**
         * @param array $row
         * @return string
         */
        protected function renderCardDetailsContent($row)
        {
            $cardDetails = null;
            foreach ($this->cardColumns as $cardData)
            {
                $content      = $this->renderCardDataContent($cardData, $this->dataProvider->data[$row], $row);
                $cardDetails .= ZurmoHtml::tag('span', array('class' => $cardData['class']), $content);
            }
            $userUrl      = Yii::app()->createUrl('/users/default/details', array('id' => $this->dataProvider->data[$row]->owner->id));
            $cardDetails .= ZurmoHtml::link($this->dataProvider->data[$row]->owner->getAvatarImage(20), $userUrl,
                                            array('class' => 'opportunity-owner'));
            return $cardDetails;
        }

        protected function renderCardDataContent(array $cardData, RedBeanModel $model, $row)
        {
            assert('is_int($row)');
            return $this->evaluateExpression($cardData['value'], array('data' => $model,
                                             'offset' => ($this->getOffset() + $row)));
        }

        /**
         * @param string $listItems
         * @param string $attributeValue
         * @return string
         */
        protected function renderUlTagForKanbanColumn($listItems, $attributeValue = null)
        {
            return ZurmoHtml::tag('ul', array(), $listItems);
        }

        /**
         * @return string
         */
        protected function getRowClassForKanbanColumn()
        {
            return 'kanban-card item-to-place';
        }

        /**
         * @param int $row
         * @return string
         */
        protected function createRowForKanbanColumn($row)
        {
            return ZurmoHtml::tag('li', array('class' => $this->getRowClassForKanbanColumn(),
                                                'data-id' => $this->dataProvider->data[$row]->id),
                                                    $this->wrapCardDetailsContent($row));
        }

        /**
         * @param array $attributeValueAndData
         * @return string
         */
        protected function getListItemsByAttributeValueAndData($attributeValueAndData)
        {
            $listItems = '';
            foreach ($attributeValueAndData as $row)
            {
                $listItems .= $this->createRowForKanbanColumn($row);
            }
            return $listItems;
        }

        /**
         * Wraps card details content
         * @param int $row
         * @return string
         */
        protected function wrapCardDetailsContent($row)
        {
            return ZurmoHtml::tag('div', array(), $this->renderCardDetailsContent($row));
        }

        /**
         * Checks if max count has to be validated in the kanban view
         * @return boolean
         */
        protected function isMaxCountCheckRequired()
        {
            return true;
        }
    }
?>
