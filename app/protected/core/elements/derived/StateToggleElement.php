<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2014 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2014. All rights reserved".
     ********************************************************************************/

    abstract class StateToggleElement extends Element implements DerivedElementInterface
    {
        abstract protected function assertAttributeName();

        abstract protected function assertModelClass();

        protected function renderEditable()
        {
            throw new NotSupportedException();
        }

        protected function renderControlEditable()
        {
            throw new NotSupportedException();
        }

        public static function getDropDownArray()
        {
            throw new NotImplementedException();
        }

        protected static function resolveSelectedRadioButtonListOption(RedBeanModel $model)
        {
            throw new NotImplementedException();
        }

        protected static function resolveStatusChangeUrl(RedBeanModel $model)
        {
            throw new NotImplementedException();
        }

        protected static function resolveSuccessMessage()
        {
            throw new NotImplementedException();
        }

        protected function renderLabel()
        {
            return null;
        }

        protected static function resolvePostNotificationSuccessScript()
        {
            return null;
        }

        public static function getDisplayName()
        {
            return Zurmo::t('ZurmoModule', 'Status');
        }

        protected function renderControlNonEditable()
        {
            $this->assertAttributeName();
            $this->assertModelClass();
            static::renderAjaxStatusChange($this->model);
            return static::renderStatusChangeArea($this->model);
        }

        protected static function renderAjaxStatusChange(RedBeanModel $model)
        {
            $url            = static::resolveStatusChangeUrl($model);
            $name           = static::getRadioButtonListName($model->id);
            $successScript  = static::resolveOnSuccessScript();
            $script = "
                    $('input[name=${name}]').change(function()
                    {
                        $.ajax(
                        {
                            url: '{$url}',
                            type: 'GET',
                            success: ${successScript},
                        });
                    });
                ";
            Yii::app()->clientScript->registerScript('ConversationStatusChange', $script);
        }

        protected static function resolveOnSuccessScript()
        {
            $message                        = static::resolveSuccessMessage();
            $postNotificationSuccessScript  = static::resolvePostNotificationSuccessScript();
            // Begin Not Coding Standard
            $script = "
                function(data)
                {
                    $('#FlashMessageBar').jnotifyAddMessage(
                        {
                            text: '${message}',
                            permanent: false,
                            showIcon: true,
                            type: 'ConversationsChangeStatusMessage'
                        }
                    );
                    ${postNotificationSuccessScript}
                }";
            // End Not Coding Standard
            return $script;
        }

        public static function renderStatusChangeArea(RedBeanModel $model)
        {
            $content    = static::renderStatusAreaLabel();
            $content    .= static::renderStatusButtonsContent($model);
            $content    = ZurmoHtml::tag('div', static::getStatusChangeHtmlOptions($model), $content);
            return $content;
        }

        public static function renderStatusButtonsContent(RedBeanModel $model)
        {
            $content = ZurmoHtml::radioButtonList(
                static::getRadioButtonListName($model->id),
                static::resolveSelectedRadioButtonListOption($model),
                static::getDropDownArray(),
                static::getRadioButtonListHtmlOptions($model)
            );
            return ZurmoHtml::tag('div', array('class' => 'switch'), $content);
        }

        protected static function getRadioButtonListHtmlOptions(RedBeanModel $model)
        {
            return array('separator' => '', 'template'  => '<div class="switch-state clearfix">{input}{label}</div>');
        }

        protected static function getStatusChangeHtmlOptions(RedBeanModel $model)
        {
            $id         = static::getStatusChangeDivId($model);
            $class      = static::getStatusChangeDivClassNames($model);
            $class[]    = 'clearfix';
            $class      = implode(' ', $class);
            $options    = compact('id', 'class');
            return $options;
        }

        public static function getStatusChangeDivId(RedBeanModel $model)
        {
            $classNames = static::getStatusChangeDivClassNames($model);
            return  ucfirst($classNames[0]) . '-' . $model->id;
        }

        private static function getRadioButtonListName($modelId)
        {
            return 'statusChange-' . $modelId;
        }

        protected static function renderStatusAreaLabel()
        {
            return ZurmoHtml::tag('span', array(), Zurmo::t('ZurmoModule', 'Status'));
        }

        protected static function getStatusChangeDivClassNames(RedBeanModel $model)
        {
            $modelClassName = get_class($model);
            $modelClassName = lcfirst($modelClassName);
            $modelClassName .= 'StatusChangeArea';
            return array($modelClassName);
        }
    }
?>