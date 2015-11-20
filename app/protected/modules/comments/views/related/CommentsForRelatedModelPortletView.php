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
     * Base class used for wrapping a view of social items
     */
    abstract class CommentsForRelatedModelPortletView extends ConfigurableMetadataView
                                                      implements PortletViewInterface, RelatedPortletViewInterface
    {
        protected $controllerId;

        protected $moduleId;

        protected $commentsData;

        protected $relatedModel;

        protected $pageSize;

        protected $getParams;

        protected $uniquePageId;

        public function __construct($viewData, $params, $uniquePortletPageId)
        {
            assert('isset($params["controllerId"])');
            assert('isset($params["relationModuleId"])');
            assert('$params["relationModel"] instanceof RedBeanModel || $params["relationModel"] instanceof ModelForm');
            assert('isset($params["portletId"])');
            assert('isset($params["redirectUrl"])');
            //assert('$this->getRelationAttributeName() != null');
            $this->modelClassName    = get_class($params['relationModel']);
            $this->viewData          = $viewData;
            $this->params            = $params;
            $this->uniqueLayoutId    = $uniquePortletPageId;
            $this->controllerId      = $params['controllerId'];
            $this->moduleId          = $params['relationModuleId'];
            $this->relatedModel      = $params['relationModel'];
        }

        public function renderContent()
        {
            $content = $this->renderNotificationSubscribersContent();
            $content .= $this->renderCommentsContent();
            return $content;
        }

        public static function getDefaultMetadata()
        {
            $metadata = array(
                'perUser' => array(
                    'title' => "eval:Zurmo::t('CommentsModule', 'Feed Comments')",
                ),
            );
            return $metadata;
        }

        protected function renderCommentsContent()
        {
            $commentsElement = new CommentsForModelCommentsFeedPortletElement($this->relatedModel, 'null', null, array('moduleId' => $this->moduleId));
            return $commentsElement->render();
        }

        // ToDo: check TaskModalDetailsView::renderNotificationSubscribersContent, maybe we can unify code and use same function
        protected function renderNotificationSubscribersContent()
        {
            $model = $this->relatedModel;
            $content = '<div id="task-subscriber-box">';
            $content .= ZurmoHtml::tag('h4', array(), Zurmo::t('Core', 'Who is receiving notifications'));
            $content .= '<div id="subscriberList" class="clearfix">';
            if ($model->notificationSubscribers->count() > 0)
            {
                $content .= NotificationSubscriberUtil::getSubscriberData($model);
            }
            $content .= NotificationSubscriberUtil::getDetailSubscriptionLink($model, 0);
            $content .= '</div>';
            $content .= '</div>';
            NotificationSubscriberUtil::registerSubscriptionScript($this->modelClassName, $model);
            NotificationSubscriberUtil::registerUnsubscriptionScript($this->modelClassName, $model);
            return $content;
        }

        public static function canUserConfigure()
        {
            return false;
        }

        /**
         * @return bool
         */
        public static function canUserRemove()
        {
            return false;
        }

        public static function getPortletRulesType()
        {
            return 'ModelDetails';
        }

        public static function getModuleClassName()
        {

        }

        public static function getPortletDescription()
        {

        }

        public function renderPortletHeadContent()
        {
            return null;
        }

        public function getPortletParams()
        {
            return array();
        }

        public static function allowMultiplePlacement()
        {
            return false;
        }
    }
?>