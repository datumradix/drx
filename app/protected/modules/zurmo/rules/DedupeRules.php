<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    /**
     * Class DedupeRules
     * Base class of dedupe rules that assist
     * Extend this class to make a set of DedupeRules that is for a specific model.
     */
    abstract class DedupeRules
    {
        //TODO: @sergio: Add tests

        protected $model;

        public function __construct(RedBeanModel $model)
        {
            $this->model = $model;
        }

        /**
         * This array should map the relation between the attribute and related attribute that should trigger the dedupe
         * @return array
         */
        protected function getDedupeAttributesAndRelatedAttributesMappedArray()
        {
            return array();
        }

        /**
         * This array contains a list of the Element names that will trigger the dedupe
         * @return array
         */
        protected function getDedupeElements()
        {
            return array();
        }

        /**
         * This array maps the relation between the attribute name and function callback for search for duplicate models
         * @return array
         */
        protected function getDedupeAttributesAndSearchForDuplicateModelsCallbackMappedArray()
        {
            return array();
        }

        /**
         * The ViewClassName used to display the results of the dedupe models list
         * @return string
         */
        public function getDedupeViewClassName()
        {
            return 'CreateModelsToMergeListAndChartView';
        }

        /**
         * Register the script that will make the ajax call to search for a dedupe and update the DedupeViewClassName
         * with the content returned. It also display a clickable flash message with the number of results found
         * @see ZurmoModuleController::actionSearchForDuplicateModels
         * @param Element $element
         * @return null
         */
        public function registerScriptForEditAndDetailsView(Element $element)
        {
            if (!$this->shouldCreateScriptForElement($element))
            {
                return null;
            }
            $id           = $this->getInputIdForDedupe($element);
            $dedupeViewId = $this->getDedupeViewClassName();
            $ajaxScript = ZurmoHtml::ajax(array(
                'type'       => 'GET',
                'data'       => array('attribute' => $this->getAttributeForDedupe($element),
                    'value'     => "js:$('#{$id}').val()",
                ),
                'url'        => 'searchForDuplicateModels',
                'success'    => "js:function(data, textStatus, jqXHR){
                                        var returnObj = jQuery.parseJSON(data);
                                        if (returnObj != null)
                                        {
                                            $('#" . $dedupeViewId . "').replaceWith(returnObj.content);
                                            $('#FlashMessageBar').jnotifyAddMessage({
                                                text: '<a href=\"#\" onclick=\"$(\'#" . $dedupeViewId . "\').show(); return false;\">' + returnObj.message + '</a>',
                                                permanent: false,
                                                showIcon: false,
                                                disappearTime: 10000,
                                                removeExisting: true
                                            })
                                        }
                                 }"
            ));
            $js = "$('#{$id}' ).blur(function() {
                        if ($('#{$id}').val() != ''){ {$ajaxScript} }
                   });
            ";

            Yii::app()->getClientScript()->registerScript(__CLASS__ . $id . '#dedupe-for-edit-and-details-view', $js);
        }

        /**
         * Returns the input id that should be used to trigger the dedupe
         * @param Element $element
         * @return null|string
         */
        protected function getInputIdForDedupe(Element $element)
        {
            $interfaces = class_implements($element);
            if(in_array('MultipleAttributesElementInterface', $interfaces))
            {
                return Element::resolveInputIdPrefixIntoString(array(get_class($this->model), $element->getAttribute(), $this->getRelatedAttributeForDedupe($element)));
            }
            else
            {
                return Element::resolveInputIdPrefixIntoString(array(get_class($this->model), $this->getAttributeForDedupe($element)));
            }
        }

        /**
         * Returns the attribute name that should be used to trigger the dedupe
         * @param Element $element
         * @return mixed
         * @throws NotSupportedException
         */
        protected function getAttributeForDedupe(Element $element)
        {
            $interfaces = class_implements($element);
            if(in_array('DerivedElementInterface', $interfaces))
            {
                $attributesForDedupeInElement = array_values(array_intersect(array_keys($this->getDedupeAttributesAndRelatedAttributesMappedArray()),
                                $element->getModelAttributeNames()));
                if (count($attributesForDedupeInElement) == 1)
                {
                    return $attributesForDedupeInElement[0];
                }
                else
                {
                    throw new NotSupportedException('Dedupe multiple attributes on the same element is not possible');
                }
            }
            return $element->getAttribute();
        }

        /**
         * Return the related attribute that should be used to trigger the dedupe
         * @param Element $element
         * @return mixed
         */
        protected function getRelatedAttributeForDedupe(Element $element)
        {
            $dedupeMappingArray = $this->getDedupeAttributesAndRelatedAttributesMappedArray();
            return $dedupeMappingArray[$element->getAttribute()];
        }

        /**
         * Returns the name of the element
         * @param Element $element
         * @return string
         */
        protected function getElementNameByElement(Element $element)
        {
            return str_replace('Element', '', get_class($element));
        }

        /**
         * Based on the Element the data from @see DedupeRules::getDedupeAttributesAndRelatedAttributesMappedArray
         * and @see DedupeRules::getDedupeElements and from the model id, decided if the script for dedupe should be
         * registered
         * @param Element $element
         * @return bool
         */
        protected function shouldCreateScriptForElement(Element $element)
        {
            if (!in_array($this->getElementNameByElement($element), $this->getDedupeElements()) ||
                !array_key_exists($this->getAttributeForDedupe($element), $this->getDedupeAttributesAndRelatedAttributesMappedArray()) ||
                $this->model->id > 0)
            {
                return false;
            }
            return true;
        }

        public function searchForDuplicateModelsAndRenderResultsObject($attribute, $value, $controllerId, $moduleId)
        {
            assert('is_string($attribute) && $attribute != null');
            assert('is_string($value)');
            $callback      = $this->getCallbackToSearchForDuplicateModelsByAttribute($attribute);
            $matchedModels = call_user_func($callback, $value, ModelsListDuplicateMergedModelForm::SELECTED_MODELS_COUNT + 1);
            if (count($matchedModels) > 0)
            {
                if (count($matchedModels) > ModelsListDuplicateMergedModelForm::SELECTED_MODELS_COUNT)
                {
                    $message =  Zurmo::t('ZurmoModule',
                                         'There are at least {n} possible matches.',
                                         ModelsListDuplicateMergedModelForm::SELECTED_MODELS_COUNT
                    );
                }
                else
                {
                    $message =  Zurmo::t('ZurmoModule',
                                         'There is {n} possible match.|There are {n} possible matches.',
                                         count($matchedModels)
                    );
                }
                $viewClassName = $this->getDedupeViewClassName();
                $summaryView = new $viewClassName($controllerId, $moduleId, $this->model, $matchedModels);
                $content = $summaryView->render();
                return CJSON::encode(array('message' => $message, 'content' => $content));
            }
        }

        protected function getCallbackToSearchForDuplicateModelsByAttribute($attribute)
        {
            $callbackMappedArray = $this->getDedupeAttributesAndSearchForDuplicateModelsCallbackMappedArray();
            return $callbackMappedArray[$attribute];
        }
    }
?>