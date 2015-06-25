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

    class LeadConvertOpportunityView extends GridView
    {
        protected $cssClasses =  array('DetailsView');

        protected $controllerId;

        protected $moduleId;

        protected $convertToOpportunitySetting;

        protected $userCanCreateOpportunity;

        protected $title;

        protected $modelId;

        public function __construct(
                $controllerId,
                $moduleId,
                $modelId,
                $title,
                $opportunity,
                $convertToOpportunitySetting,
                $userCanCreateOpportunity
            )
        {
            assert('$convertToOpportunitySetting != LeadsModule::CONVERT_NO_OPPORTUNITY');
            assert('is_bool($userCanCreateOpportunity)');

            if ($userCanCreateOpportunity)
            {
                Yii::app()->clientScript->registerScript('leadConvert', "
                    $(document).ready(function()
                        {
                            $('#LeadConvertOpportunitySkipView').hide();
                            $('#opportunity-skip-title').hide();
                        }
                    );
                ");
            }
            else
            {
                Yii::app()->clientScript->registerScript('leadConvert', "
                    $(document).ready(function()
                        {
                            $('#opportunity-create-title').hide();
                            $('#OpportunityConvertToView').hide();
                            $('#LeadConvertOpportunitySkipView').hide();
                            $('#opportunity-skip-title').hide();
                        }
                    );
                ");
            }
            if ($convertToOpportunitySetting == LeadsModule::CONVERT_OPPORTUNITY_NOT_REQUIRED)
            {
                $gridSize = 2;
            }
            else
            {
                $gridSize = 1;
            }
            $title = Zurmo::t('LeadsModule', 'LeadsModuleSingularLabel Conversion',
                                                LabelUtil::getTranslationParamsForAllModules()) . ': ' . $title;
            parent::__construct($gridSize, 1);

            $this->setView(new OpportunityConvertToView($controllerId, $moduleId, $opportunity, $modelId), 0, 0);
            if ($convertToOpportunitySetting == LeadsModule::CONVERT_OPPORTUNITY_NOT_REQUIRED)
            {
                $this->setView(new LeadConvertOpportunitySkipView($controllerId, $moduleId, $modelId), 1, 0);
            }

            $this->controllerId            = $controllerId;
            $this->moduleId                = $moduleId;
            $this->modelId                 = $modelId;
            $this->convertToOpportunitySetting = $convertToOpportunitySetting;
            $this->userCanCreateOpportunity    = $userCanCreateOpportunity;
            $this->title                   = $title;
        }

        /**
         * Renders content for the view.
         * @return A string containing the element's content.
         */
        protected function renderContent()
        {
            Yii::app()->clientScript->registerScript('leadConvertActions', "
                $('.opportunity-create-link').click( function()
                    {
                        $('#OpportunityConvertToView').show();
                        $('#LeadConvertOpportunitySkipView').hide();
                        $('#opportunity-create-title').show();
                        $('#opportunity-skip-title').hide();
                        return false;
                    }
                );
                $('.opportunity-skip-link').click( function()
                    {
                        $('#OpportunityConvertToView').hide();
                        $('#LeadConvertOpportunitySkipView').show();
                        $('#opportunity-create-title').hide();
                        $('#opportunity-skip-title').show();
                        return false;
                    }
                );
            ");
            $createLink = ZurmoHtml::link(Zurmo::t('OpportunitiesModule', 'Create OpportunitiesModuleSingularLabel',
                            LabelUtil::getTranslationParamsForAllModules()), '#', array('class' => 'opportunity-create-link'));
            $skipLink   = ZurmoHtml::link(Zurmo::t('LeadsModule', 'Skip OpportunitiesModuleSingularLabel',
                            LabelUtil::getTranslationParamsForAllModules()), '#', array('class' => 'opportunity-skip-link'));
            $content = $this->renderTitleContent();
            $content .= '<div class="lead-conversion-actions">';
            $content .= '<div id="opportunity-create-title">';
            $content .= Zurmo::t('OpportunitiesModule', 'Create OpportunitiesModuleSingularLabel',
                                    LabelUtil::getTranslationParamsForAllModules()) . '&#160;';
            if ($this->convertToOpportunitySetting == LeadsModule::CONVERT_OPPORTUNITY_NOT_REQUIRED)
            {
                $content .= Zurmo::t('Core', 'or') . '&#160;' . $skipLink;
            }
            $content .= '</div>';
            if ($this->convertToOpportunitySetting == LeadsModule::CONVERT_OPPORTUNITY_NOT_REQUIRED)
            {
                $content .= '<div id="opportunity-skip-title">';
                if ($this->userCanCreateOpportunity)
                {
                    $content .= $createLink . '&#160;' . Zurmo::t('Core', 'or') . '&#160;';
                }
                $content .= Zurmo::t('LeadsModule', 'Skip OpportunitiesModuleSingularLabel',
                                        LabelUtil::getTranslationParamsForAllModules()) . '&#160;';
                $content .= '</div>';
            }
            $content .= '</div>'; //this was missing..
            $content  = $content . ZurmoHtml::tag('div', array('class' => 'left-column full-width clearfix'), parent::renderContent());
            return '<div class="wrapper">' . $content . '</div>';
        }

        public function isUniqueToAPage()
        {
            return true;
        }
    }
?>