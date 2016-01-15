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
     * View to show a create email interface that appears in a modal window.
     */
    class CreateEmailMessageModalEditView extends EditView
    {
        /**
         * Since this edit view shows in a modal, we do not want the wrapper div to display as it is unneeded.
         * @var boolean
         */
        protected $wrapContentInWrapperDiv = false;

        protected function renderTitleContent()
        {
            return null;
        }

        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'toolbar' => array(
                        'elements' => array(
                            array('type'  => 'SaveButton', 'label' => Zurmo::t('EmailMessagesModule', 'Send')),
                        ),
                    ),
                    'derivedAttributeTypes' => array(
                        'EmailMessageAllRecipientTypes',
                        'Files',
                    ),
                    'nonPlaceableAttributeNames' => array(
                        'sentDateTime',
                        'sender'
                    ),
                    'panelsDisplayType' => FormLayout::PANELS_DISPLAY_TYPE_ALL,
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'recipientsData',
                                                      'type'          => 'EmailMessageAllRecipientTypes'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'subject', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'null',
                                                      'type' => 'EmailMessageEmailTemplate')
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'content', 'type' => 'EmailMessageContent'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'null', 'type' => 'Files'),
                                            ),
                                        ),
                                    )
                                ),
                            ),
                        ),
                    ),
                ),
            );
            return $metadata;
        }

        /**
         * Override to change the editableTemplate to place the label above the input.
         * @see DetailsView::resolveElementDuringFormLayoutRender()
         */
        protected function resolveElementDuringFormLayoutRender(& $element)
        {
            if ($element->getAttribute() == 'subject')
            {
                $label = ZurmoHtml::tag('label',
                                        array('for' => get_class($this->model) . '_subject'),
                                        $this->model->getAttributeLabel('subject'));
                $element->editableTemplate = '<td><div class="overlay-label-field">' . $label . '{content}{error}</div></td>';
            }
            elseif (get_class($element) == 'EmailMessageEmailTemplateElement')
            {
                $element->editableTemplate = '<td><div class="overlay-label-field"><div class="has-model-select">{label}{content}{error}</div></td>';
            }
            else
            {
                $element->editableTemplate = '<td>{content}{error}</td>';
            }
        }

        /**
         * (non-PHPdoc)
         * @see DetailsView::doesLabelHaveOwnCell()
         */
        protected function doesLabelHaveOwnCell()
        {
            return false;
        }

        protected function resolveActiveFormAjaxValidationOptions()
        {
            $afterValidateAjax = $this->renderConfigSaveAjax(
                static::getFormId(),
                $this->moduleId,
                $this->controllerId,
                'createEmailMessage');
            return array(
                'enableAjaxValidation' => true,
                'clientOptions' => array(
                    'beforeValidate'    => 'js:$(this).beforeValidateAction',
                    'afterValidate'     => 'js:$(this).afterValidateAjaxAction',
                    'validateOnSubmit'  => true,
                    'validateOnChange'  => false,
                    'inputContainer'    => 'td',
                    'afterValidateAjax' => $afterValidateAjax,
                )
            );
        }

        protected function renderConfigSaveAjax($formName, $moduleId, $controllerId, $actionSave)
        {
            return ZurmoHtml::ajax(array(
                    'type' => 'POST',
                    'data' => 'js:$("#' . $formName . '").serialize()',
                    'url'  => Yii::app()->createUrl($moduleId . '/' . $controllerId . '/' . $actionSave, GetUtil::getData()),
                    'complete' => "function(XMLHttpRequest, textStatus){\$('#modalContainer').dialog('close');
                        //find if there is a latest activities portlet
                        $('.LatestActivitiesForPortletView').each(function()
                        {
                            $(this).find('.pager').find('.refresh').find('a').click();
                        });}"
                ));
        }
    }
?>