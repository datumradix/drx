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
     * Adds specific input id/name/value handling for wizard-based modules filter usage. Reporting and workflow both
     * extend this element
     */
    class MixedLoggedInUserTypesAndUsersElement extends Element
    {
        public $editableTemplate = '<th>{label}</th><td colspan="{colspan}">{valueType}{content}{error}</td>';

        public $nonEditableTemplate = '<th>{label}</th><td colspan="{colspan}">{valueType}{content}</td>';

        protected function renderEditable()
        {
            $data = array();
            $data['label']     = $this->renderLabel();
            $data['valueType'] = $this->renderEditableValueTypeContent();
            $data['content']   = $this->renderControlEditable();
            $data['error']     = $this->renderError();
            $data['colspan']   = $this->getColumnSpan();
            return $this->resolveContentTemplate($this->editableTemplate, $data);
        }

        /**
         * Render a date JUI widget
         * @return The element's content as a string.
         */
        protected function renderControlEditable()
        {
            $valueTypeId                        = $this->getValueTypeEditableInputId();
            $selectUserAreaSuffix            = '-user-select-area';
            $selectUserSpanAreaId                = $valueTypeId . $selectUserAreaSuffix;
            $valueTypesRequiringSelectUserInput  = MixedLoggedInUserTypesAndUsersSearchFormAttributeMappingRules::
                getValueTypesRequiringSelectUserInput();
            Yii::app()->clientScript->registerScript('mixedLoggedInUserTypesAndUsers', "
                $('.selectUserValueType').change( function()
                    {
                        arr  = " . CJSON::encode($valueTypesRequiringSelectUserInput) . ";
                        selectUserSpanAreaQualifier = '#' + $(this).attr('id') + '" . $selectUserSpanAreaId . "';
                        if ($.inArray($(this).val(), arr) != -1)
                        {
                            $(selectUserSpanAreaQualifier).show();
                            $(selectUserSpanAreaQualifier).find('.hasDatepicker').prop('disabled', false);
                        }
                        else
                        {
                            $(selectUserSpanAreaQualifier).hide();
                            $(selectUserSpanAreaQualifier).find('.hasDatepicker').prop('disabled', true);
                        }
                    }
                );
            ");
            $startingDivStyleSelectUser   = null;
            if (!in_array($this->getValueType(), $valueTypesRequiringSelectUserInput))
            {
                $startingDivStyleSelectUser = "display:none;";
                $selectUserDisabled         = 'disabled';
            }
            else
            {
                $selectUserDisabled         = null;
            }
            $content  = ZurmoHtml::tag('span', array('id'    => $selectUserSpanAreaId,
                                                     'class' => 'user-select-area', // ToDo: add this class to css
                                                     'style' => $startingDivStyleSelectUser),
                $this->renderEditableUserContent($selectUserDisabled));
            $content .= $this->renderSelectUserContent();
            return $content;
        }

        protected function renderSelectUserContent()
        {
            $staticUserElement = new UserNameIdElement($this->model, $this->getValueUserEditableInputName(), $this->form);
            $staticUserElement->setIdAttributeId($this->getValueUserEditableInputId());
            $staticUserElement->setNameAttributeName('stringifiedModelForValue');
            $staticUserElement->editableTemplate = '<div class="value-data">{content}{error}</div>';
            return $staticUserElement->render();
        }

        protected function renderEditableFirstDateContent($disabled = null)
        {
            assert('$disabled === null || $disabled = "disabled"');
            $cClipWidget = new CClipWidget();
            $cClipWidget->beginClip("EditableDateElement");
            $cClipWidget->widget('application.core.widgets.ZurmoJuiDatePicker', array(
                'attribute'           => $this->attribute,
                'value'               => DateTimeUtil::resolveValueForDateLocaleFormattedDisplay(
                    $this->getValueFirstDate(),
                    DateTimeUtil::DISPLAY_FORMAT_FOR_INPUT),
                'htmlOptions'         => array(
                    'id'              => $this->getValueFirstDateEditableInputId(),
                    'name'            => $this->getValueFirstDateEditableInputName(),
                    'disabled'        => $disabled,
                )));
            $cClipWidget->endClip();
            $content =  $cClipWidget->getController()->clips['EditableDateElement'];
            return      ZurmoHtml::tag('div', array('class' => 'has-date-select'), $content);
        }


        /*
         * This is from MixedDateTimeElement, try with default one from Element for now
        protected function renderLabel()
        {
            $label = $this->getFormattedAttributeLabel();
            if ($this->form === null)
            {
                return $label;
            }
            return ZurmoHtml::label($label, false);
        }
        */

        protected function renderEditableValueTypeContent()
        {
            $content = ZurmoHtml::dropDownList($this->getValueTypeEditableInputName(),
                $this->getValueType(),
                $this->getValueTypeDropDownArray(),
                $this->getEditableValueTypeHtmlOptions());
            $error   = $this->form->error($this->model, 'valueType',
                array('inputID' => $this->getValueTypeEditableInputId()));
            return $content . $error;
        }

        protected function getValueTypeDropDownArray()
        {
            return MixedLoggedInUserTypesAndUsersSearchFormAttributeMappingRules::getValueTypesAndLabels();
        }

        protected function getEditableValueTypeHtmlOptions()
        {
            $htmlOptions = array(
                'id'    => $this->getValueTypeEditableInputId(),
                'class' => 'selectUserValueType',
            );
            $htmlOptions['empty']    = Zurmo::t('Core', '(None)');
            $htmlOptions['disabled'] = $this->getDisabledValue();
            return $htmlOptions;
        }


        protected function renderEditableUserContent($disabled = null)
        {
            assert('$disabled === null || $disabled = "disabled"');
            $cClipWidget = new CClipWidget();
            $cClipWidget->beginClip("EditableDateElement");
            $cClipWidget->widget('application.core.widgets.ZurmoJuiDatePicker', array(
                'attribute'           => $this->attribute,
                'value'               => DateTimeUtil::resolveValueForDateLocaleFormattedDisplay(
                    $this->getValueUser(),
                    DateTimeUtil::DISPLAY_FORMAT_FOR_INPUT),
                'htmlOptions'         => array(
                    'id'              => $this->getValueUserEditableInputId(),
                    'name'            => $this->getValueUserEditableInputName(),
                    'disabled'        => $disabled,
                )));
            $cClipWidget->endClip();
            $content = $cClipWidget->getController()->clips['EditableDateElement'];
            $content = ZurmoHtml::tag('div', array('class' => 'has-date-select'), $content);
            $error   = $this->form->error($this->model, 'value',
                array('inputID' => $this->getValueUserEditableInputId()));
            return $content . $error;
        }

        protected function getValueTypeEditableInputId()
        {
            return $this->getEditableInputId('valueType');
        }

        protected function getValueUserEditableInputId()
        {
            return $this->getEditableInputId('value');
        }

        protected function getValueTypeEditableInputName()
        {
            return $this->getEditableInputName('valueType');
        }

        protected function getValueUserEditableInputName()
        {
            return $this->getEditableInputName('value');
        }

        protected function getValueUser()
        {
            return ArrayUtil::getArrayValue($this->model, 'value');
        }

        protected function getValueType()
        {
            return ArrayUtil::getArrayValue($this->model, 'valueType');
        }

        /**
         * Renders the attribute from the model.
         * @return The element's content.
         */
        protected function renderControlNonEditable()
        {
            throw new NotSupportedException();
        }
    }
?>