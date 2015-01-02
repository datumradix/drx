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

    abstract class SelectPrimaryOrSecondaryCompositeElement extends Element
    {
        const PRIMARY_INPUT_WRAPPER_ID                      = 'primary-input-id';

        const SECONDARY_INPUT_WRAPPER_ID                    = 'secondary-input-id';

        const SELECT_PRIMARY_OR_SECONDARY_RADIO_WRAPPER_ID  = 'select-primary-or-secondary-radio';

        const SEARCH_BOX_MAGNIFIER_CLASS                    = 'search-without-scope';

        abstract protected function resolveRadioElementAttributeName();

        abstract protected function resolveRadioElementClassName();

        abstract protected function resolvePrimaryInputElementAttributeName();

        abstract protected function resolvePrimaryInputClassName();

        abstract protected function resolveSecondaryInputElementAttributeName();

        abstract protected function resolveSecondaryInputClassName();

        protected function appendAutoCompleteSpinner()
        {
            return true;
        }

        protected function prependMagnifierForPrimaryInputWrapper()
        {
            return true;
        }

        protected function prependMagnifierForSecondaryInputWrapper()
        {
            return true;
        }

        public function getActionType()
        {
            return 'Details';
        }

        protected function getDefaultLabel()
        {
            return Zurmo::t('Core', 'Select');
        }

        protected function getDefaultRoute()
        {
            return null;
        }

        public function renderControlNonEditable()
        {
            throw new NotSupportedException();
        }

        public function renderControlEditable()
        {
            $content    = $this->renderSelectPrimaryOrSecondaryRadioElement();
            $content    .= $this->renderPrimaryInput();
            $content    .= $this->renderSecondaryInput();
            return $content;
        }

        protected function resolveRadioElementParams()
        {
            $params =  CMap::mergeArray($this->params,
                array(TogglePrimaryOrSecondaryInputRadioElement::PRIMARY_INPUT_WRAPPER_PARAM_NAME => static::PRIMARY_INPUT_WRAPPER_ID,
                    TogglePrimaryOrSecondaryInputRadioElement:: SECONDARY_INPUT_WRAPPER_PARAM_NAME => static::SECONDARY_INPUT_WRAPPER_ID));
            return $params;
        }

        protected function resolveInputElementsParams()
        {
            $params     = CMap::mergeArray($this->params, array('radioButtonClass' => $this->resolveRadioElementAttributeName()));
            return $params;
        }

        protected function renderPrimaryInput()
        {
            return $this->renderInput(true, $this->resolvePrimaryInputElementEditableTemplate(), $this->prependMagnifierForPrimaryInputWrapper());
        }

        protected function renderSecondaryInput()
        {
            return $this->renderInput(false, $this->resolveSecondaryInputElementEditableTemplate(), $this->prependMagnifierForSecondaryInputWrapper());
        }

        protected function renderInput($renderPrimaryInput = true, $template = null, $addMagnifierToInput = true)
        {
            if ($renderPrimaryInput)
            {
                $attribute          = $this->resolvePrimaryInputElementAttributeName();
                $elementClassName   = $this->resolvePrimaryInputClassName();
            }
            else
            {
                $attribute          = $this->resolveSecondaryInputElementAttributeName();
                $elementClassName   = $this->resolveSecondaryInputClassName();
            }
            $params             = $this->resolveInputElementsParams();
            $element            = new $elementClassName($this->model, $attribute, $this->form, $params);
            if ($template)
            {
                $element->editableTemplate  = $template;
            }
            $content            = $this->renderPreInputContent($renderPrimaryInput);
            $content            .= $element->render();
            $content            .= $this->renderPostInputContent($renderPrimaryInput);
            if ($this->appendAutoCompleteSpinner())
            {
                $content        .= $this->renderAutoCompleteSpinner();
            }
            $content            = ZurmoHtml::tag('div', $this->resolveInputWrapperHtmlOptions($renderPrimaryInput,
                                                                                                $addMagnifierToInput),
                                                        $content);
            return $content;
        }

        protected function renderPreInputContent($renderPrimaryInput = true)
        {

        }

        protected function renderPostInputContent($renderPrimaryInput = true)
        {

        }

        protected function resolveInputWrapperHtmlOptions($primaryHtmlOptions = true, $addMagnifierToInput = true)
        {
            $htmlOptions    = array();
            if ($primaryHtmlOptions)
            {
                $htmlOptions['id']      = static::PRIMARY_INPUT_WRAPPER_ID;
            }
            else
            {
                $htmlOptions['id']      = static::SECONDARY_INPUT_WRAPPER_ID;
            }
            if ($addMagnifierToInput)
            {
                $htmlOptions['class']   = static::SEARCH_BOX_MAGNIFIER_CLASS;
            }
            return $htmlOptions;
        }

        protected function renderSelectPrimaryOrSecondaryRadioElement()
        {
            $radioElementClassName  = $this->resolveRadioElementClassName();
            $radio                  = new $radioElementClassName($this->model, $this->resolveRadioElementAttributeName(),
                                                                        $this->form, $this->resolveRadioElementParams());
            if ($this->resolveRadioElementEditableTemplate())
            {
                $radio->editableTemplate    = $this->resolveRadioElementEditableTemplate();
            }
            $content                = $radio->render();
            $content                = ZurmoHtml::tag('div', $this->resolveRadioElementWrapperHtmlOptions(), $content);
            return $content;
        }

        protected function resolveRadioElementWrapperHtmlOptions()
        {
            $htmlOptions    = array('id' => static::SELECT_PRIMARY_OR_SECONDARY_RADIO_WRAPPER_ID);
            return $htmlOptions;
        }

        protected function renderAutoCompleteSpinner()
        {
            return '<span class="z-spinner"></span>';
        }

        protected function resolvePrimaryInputElementEditableTemplate()
        {

        }

        protected function resolveSecondaryInputElementEditableTemplate()
        {

        }

        protected function resolveRadioElementEditableTemplate()
        {

        }
    }
?>
