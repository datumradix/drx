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

    abstract class TogglePrimaryOrSecondaryInputRadioElement extends Element
    {
        const PRIMARY_INPUT_WRAPPER_PARAM_NAME      = 'primaryWrapperId';

        const SECONDARY_INPUT_WRAPPER_PARAM_NAME    = 'secondInputId';

        abstract protected function assertModelType();

        abstract protected function getDataArray();

        public function getEditableHtmlOptions()
        {
            $htmlOptions = array(
                'name'      => $this->getEditableInputName(),
                'id'        => $this->getEditableInputId(),
                'class'     => $this->attribute,
                'separator' => $this->resolveSeparator(),
            );
            if ($this->resolveEditableHtmlTemplate())
            {
                $htmlOptions['template']  = $this->resolveEditableHtmlTemplate();
            }
            return $htmlOptions;
        }

        protected function resolveSeparator()
        {
            return ' ';
        }

        protected function resolveEditableHtmlTemplate()
        {
            return '{input}{label}';
        }

        /**
         * Renders the setting as a radio list.
         * @return A string containing the element's content.
         */
        protected function renderControlEditable()
        {
            $this->registerScripts();
            $this->assertModelType();
            $content = $this->form->radioButtonList(
                                            $this->model,
                                            $this->attribute,
                                            $this->getDataArray(),
                                            $this->getEditableHtmlOptions()
                                        );
            return $content;
        }

        protected function renderControlNonEditable()
        {
            throw new NotImplementedException();
        }

        protected function registerScripts()
        {
            $primaryWrapperId     = $this->getPrimaryWrapperInputId();
            $secondaryWrapperId   = $this->getSecondaryWrapperInputId();
            Yii::app()->clientScript->registerScript(get_class($this), '
                function togglePrimaryOrSecondaryInputByRadioButtonId(radioButtonIdSuffix)
                {
                    var primaryWrapperId      = "#' . $primaryWrapperId . '";
                    var secondaryWrapperId    = "#' . $secondaryWrapperId . '";
                    var hideBoxId           = secondaryWrapperId;
                    var showBoxId           = primaryWrapperId;
                    if (radioButtonIdSuffix === undefined)
                    {
                        var radioButtonIdSuffix = 0;
                    }
                    else
                    {
                        var radioButtonIdSuffix = radioButtonIdSuffix;
                    }
                    var radioButtonId       = "#' . $this->getEditableInputId() . '_" + radioButtonIdSuffix;
                    if ($(radioButtonId).attr("checked") !== "checked")
                    {
                        $(radioButtonId).attr("checked", "checked");
                    }
                    if (radioButtonIdSuffix == 1)
                    {
                        showBoxId = secondaryWrapperId;
                        hideBoxId = primaryWrapperId;
                    }
                    $(hideBoxId).hide();
                    $(showBoxId).show();
                }
                togglePrimaryOrSecondaryInputByRadioButtonId(); // call it on page load to ensure proper radio buttons checked and divs shown
                $(".' . $this->attribute . '").unbind("change.action").bind("change.action", function(event)
                {
                    radioButtonId                                   = ($(this)).attr("id");
                    selectPrimaryOrSecondaryInputRadioButtonSuffix  = radioButtonId.charAt(radioButtonId.length - 1);
                    togglePrimaryOrSecondaryInputByRadioButtonId(selectPrimaryOrSecondaryInputRadioButtonSuffix);
                }
                );
            ');
        }

        protected function renderLabel()
        {
            return null;
        }

        protected function getParamValue($key)
        {
            return ArrayUtil::getArrayValueWithExceptionIfNotFound($this->params, $key);
        }

        protected function getPrimaryWrapperInputId()
        {
            return $this->getParamValue(static::PRIMARY_INPUT_WRAPPER_PARAM_NAME);
        }

        protected function getSecondaryWrapperInputId()
        {
            return $this->getParamValue(static::SECONDARY_INPUT_WRAPPER_PARAM_NAME);
        }
    }
?>