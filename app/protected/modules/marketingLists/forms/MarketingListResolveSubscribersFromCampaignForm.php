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
     * Form to help updating marketing lists based on campaign results
     */
    class MarketingListResolveSubscribersFromCampaignForm extends CFormModel
    {
        public $newMarketingListName;

        public $marketingList;

        public $campaignId;

        public $retargetOpenedEmailRecipients;
        public $retargetClickedEmailRecipients;
        public $retargetNotViewedEmailRecipients;
        public $retargetNotClickedEmailRecipients;

        public function getId()
        {
            return null;
        }

        public function rules()
        {
            return array(
                array('campaignId',                        'required'),
                array('newMarketingListName',              'ExistingMarketingListOrNewMarketingListNameRequiredValidator'),
                array('newMarketingListName',              'type',    'type' => 'string'),
                array('newMarketingListName',              'length',  'min'  => 1, 'max' => 64),
                array('newMarketingListName',              'safe'),
                array('retargetOpenedEmailRecipients',     'boolean'),
                array('retargetOpenedEmailRecipients',     'default', 'value' => false),
                array('retargetOpenedEmailRecipients',     'AtLeastOneRetargetingOptionRequiredValidator'),
                array('retargetClickedEmailRecipients',    'boolean'),
                array('retargetClickedEmailRecipients',    'default', 'value' => false),
                array('retargetClickedEmailRecipients',    'AtLeastOneRetargetingOptionRequiredValidator'),
                array('retargetNotViewedEmailRecipients',  'boolean'),
                array('retargetNotViewedEmailRecipients',  'default', 'value' => false),
                array('retargetNotViewedEmailRecipients',  'AtLeastOneRetargetingOptionRequiredValidator'),
                array('retargetNotClickedEmailRecipients', 'boolean'),
                array('retargetNotClickedEmailRecipients', 'default', 'value' => false),
                array('retargetNotClickedEmailRecipients', 'AtLeastOneRetargetingOptionRequiredValidator'),

            );
        }

        public function attributeLabels()
        {
            return array(
                'campaignId'    => Zurmo::t('MarketingListsModule', 'Campaign'),
                'marketingList' => Zurmo::t('MarketingListsModule', 'Add to Existing Marketing List'),
                'newMarketingListName' => Zurmo::t('MarketingListsModule', 'Name for New Marketing list'),
            );
        }
    }
?>