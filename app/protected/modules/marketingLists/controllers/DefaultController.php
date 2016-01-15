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

    class MarketingListsDefaultController extends ZurmoModuleController
    {
        const ZERO_MODELS_CHECK_FILTER_PATH =
            'application.modules.marketingLists.controllers.filters.MarketingListsZeroModelsCheckControllerFilter';

        const USER_CONTACT_OR_LEAD_ACCESS_FILTER_PATH =
            'application.modules.marketingLists.controllers.filters.UserCanAccessContactsOrLeadsForMarketingListControllerFilter';

        public static function getListBreadcrumbLinks()
        {
            $title = Zurmo::t('MarketingListsModule', 'Lists');
            return array($title);
        }

        public static function getDetailsAndEditBreadcrumbLinks()
        {
            return array(Zurmo::t('MarketingListsModule', 'Lists') => array('default/list'));
        }

        public function filters()
        {
            return array_merge(parent::filters(),
                array(
                    array(
                        static::USER_CONTACT_OR_LEAD_ACCESS_FILTER_PATH . ' + create, details',
                        'controller' => $this,
                    ),
                    array(
                        static::ZERO_MODELS_CHECK_FILTER_PATH . ' + list, index',
                        'controller'                    => $this,
                        'activeActionElementType'       => 'MarketingListsMenu',
                        'breadCrumbLinks'               => static::getListBreadcrumbLinks(),
                    ),
                )
            );
        }

        public function actionIndex()
        {
            $this->actionList();
        }

        public function actionList()
        {
            $pageSize                       = Yii::app()->pagination->resolveActiveForCurrentUserByType(
                                                                        'listPageSize', get_class($this->getModule()));
            $marketingList                  = new MarketingList(false);
            $searchForm                     = new MarketingListsSearchForm($marketingList);
            $listAttributesSelector         = new ListAttributesSelector('MarketingListsListView',
                                                                                get_class($this->getModule()));
            $searchForm->setListAttributesSelector($listAttributesSelector);
            $dataProvider = $this->resolveSearchDataProvider(
                $searchForm,
                $pageSize,
                null,
                'MarketingListsSearchView'
            );
            if (isset($_GET['ajax']) && $_GET['ajax'] == 'list-view')
            {
                $mixedView = $this->makeListView(
                    $searchForm,
                    $dataProvider
                );
                $view = new MarketingListsPageView($mixedView);
            }
            else
            {
                $mixedView = $this->makeActionBarSearchAndListView($searchForm, $dataProvider,
                             'SecuredActionBarForMarketingListsSearchAndListView', null, 'MarketingListsMenu');
                $breadCrumbLinks = static::getListBreadcrumbLinks();
                $view      = new MarketingListsPageView(MarketingDefaultViewUtil::
                                 makeViewWithBreadcrumbsForCurrentUser($this, $mixedView, $breadCrumbLinks,
                                 'MarketingBreadCrumbView'));
            }
            echo $view->render();
        }

        public function actionCreate()
        {
           $breadCrumbLinks    = static::getDetailsAndEditBreadcrumbLinks();
           $breadCrumbLinks[]  = Zurmo::t('Core', 'Create');
           $editView = new MarketingListEditView($this->getId(), $this->getModule()->getId(),
                                                 $this->attemptToSaveModelFromPost(new MarketingList()),
                                                 Zurmo::t('Default', 'Create Marketing List'));
            $view               = new MarketingListsPageView(MarketingDefaultViewUtil::
                                  makeViewWithBreadcrumbsForCurrentUser($this, $editView,
                                  $breadCrumbLinks, 'MarketingBreadCrumbView'));
            echo $view->render();
        }

        public function actionDetails($id)
        {
            $marketingList = static::getModelAndCatchNotFoundAndDisplayError('MarketingList', intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($marketingList);
            AuditEvent::logAuditEvent('ZurmoModule', ZurmoModule::AUDIT_EVENT_ITEM_VIEWED,
                                      array(strval($marketingList), 'MarketingListsModule'), $marketingList);
            $breadCrumbView             = MarketingListsStickySearchUtil::
                                          resolveBreadCrumbViewForDetailsControllerAction($this,
                                          'MarketingListsSearchView', $marketingList);
            $detailsAndRelationsView    = $this->makeDetailsAndRelationsView($marketingList, 'MarketingListsModule',
                                                                                'MarketingListDetailsAndRelationsView',
                                                                                Yii::app()->request->getRequestUri(),
                                                                                $breadCrumbView);
            $view                       = new MarketingListsPageView(MarketingDefaultViewUtil::
                                                makeStandardViewForCurrentUser($this, $detailsAndRelationsView));
            echo $view->render();
        }

        public function actionEdit($id)
        {
            $marketingList = MarketingList::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($marketingList);
            $breadCrumbLinks    = static::getDetailsAndEditBreadcrumbLinks();
            $breadCrumbLinks[]  = StringUtil::getChoppedStringContent(strval($marketingList), 25);
            $editView = new MarketingListEditView($this->getId(), $this->getModule()->getId(),
                                                 $this->attemptToSaveModelFromPost($marketingList),
                                                 strval($marketingList));
            $view               = new MarketingListsPageView(MarketingDefaultViewUtil::
                                  makeViewWithBreadcrumbsForCurrentUser($this, $editView,
                                  $breadCrumbLinks, 'MarketingBreadCrumbView'));
            echo $view->render();
        }

        public function actionDelete($id)
        {
            $marketingList = static::getModelAndCatchNotFoundAndDisplayError('MarketingList', intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserDeleteModel($marketingList);
            $marketingList->delete();
            $this->redirect(array($this->getId() . '/index'));
        }

        public function actionModalList()
        {
            $modalListLinkProvider = new SelectFromRelatedEditModalListLinkProvider(
                                            $_GET['modalTransferInformation']['sourceIdFieldId'],
                                            $_GET['modalTransferInformation']['sourceNameFieldId'],
                                            $_GET['modalTransferInformation']['modalId']
            );
            echo ModalSearchListControllerUtil::setAjaxModeAndRenderModalSearchList($this, $modalListLinkProvider);
        }

        public function actionGetInfoToCopyToCampaign($id)
        {
            $marketingList = static::getModelAndCatchNotFoundAndDisplayError('MarketingList', intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($marketingList);
            $data = array();
            $data['fromName']    = $marketingList->fromName;
            $data['fromAddress'] = $marketingList->fromAddress;
            echo CJSON::encode($data);
        }

        public function actionResolveSubscribersFromCampaign($campaignId)
        {
            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            $resolveSubscribersForm->campaignId = $campaignId;
            $campaign = Campaign::getById(intval($campaignId));
            if ($campaign->status != Campaign::STATUS_COMPLETED)
            {
                $message = Zurmo::t('MarketingListsModule', 'You can not retarget uncompleted campaigns!');
                throw new NotSupportedException($message);
            }

            $resolveSubscribersForm->newMarketingListName = MarketingListsUtil::generateRandomNameForCampaignRetargetingList($campaign);
            $introView               = new MarketingListCampaignRetargetingIntroView(get_class($this->getModule()));
            $actionBarView           = new SecuredActionBarForMarketingListCampaignRetargetingView(
                'default',
                'marketingList',
                new MarketingList(), //Just to fill in a marketing model
                'notUsed',
                'notUsed',
                false,
                null,
                $introView);

            $title                         = Zurmo::t('UsersModule', 'Retarget subscribers from existing campaign("{{campaignName}}") results',
                array("{{campaignName}}" => $campaign->name));

            $resolveSubscribersFromCampaignView                  = new MarketingListResolveSubscribersFromCampaignView(
                $this->getId(),
                $this->getModule()->getId(),
                $resolveSubscribersForm,
                $title);

            $gridView                = new GridView(2, 1);
            $gridView->setView($actionBarView, 0, 0);
            $gridView->setView($resolveSubscribersFromCampaignView, 1, 0);

            $view                       = new MarketingListsPageView(MarketingDefaultViewUtil::
                makeViewWithBreadcrumbsForCurrentUser($this, $gridView, array(Zurmo::t('MarketingListsModule', 'Lists')), 'MarketingBreadCrumbView'));
            echo $view->render();
        }

        public function actionSaveSubscribersFromCampaign($campaignId, $page = 0, $subscribedCount = 0, $skippedCount = 0, $marketingListId = null)
        {
            sleep(20);
            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            try
            {
                $campaign = Campaign::getById(intval($campaignId));
            }
            catch (NotFoundException $e)
            {
                $message = Zurmo::t('MarketingListsModule', 'Unknown campaign!');
                echo CJSON::encode(array('message' => $message, 'type' => 'message'));
                Yii::app()->end(0, false);
            }
            if ($campaign->status != Campaign::STATUS_COMPLETED)
            {
                $message = Zurmo::t('MarketingListsModule', 'You can not retarget uncompleted campaigns!');
                echo CJSON::encode(array('message' => $message, 'type' => 'message'));
                Yii::app()->end(0, false);
            }
            $resolveSubscribersForm->campaignId = $campaignId;
            $resolveSubscribersForm->attributes = $_POST['MarketingListResolveSubscribersFromCampaignForm'];
            // ToDo: For some reason sometime $resolveSubscribersForm->marketingList['id'] is not set, so code below is used to prevent this weird case. Should be checked.
            if (!isset($resolveSubscribersForm->marketingList['id']) &&
                isset($_POST['MarketingListResolveSubscribersFromCampaignForm']['marketingList']['id']) &&
                $_POST['MarketingListResolveSubscribersFromCampaignForm']['marketingList']['id'] != null
            )
            {
                $resolveSubscribersForm->marketingList['id'] = (int)$_POST['MarketingListResolveSubscribersFromCampaignForm']['marketingList']['id'];
            }
            $pageSize        = MarketingListsUtil::$pageSize;
            $page            = (int)$page;
            $subscribedCount = (int)$subscribedCount;
            $skippedCount    = (int)$skippedCount;

            if ($page > 0 && $resolveSubscribersForm->marketingList['id'] == null && $marketingListId != null)
            {
                $resolveSubscribersForm->marketingList['id'] = $marketingListId;
                $resolveSubscribersForm->newMarketingListName = null;
            }
            $this->attemptToValidateAjaxSubscriberFormFromPost($resolveSubscribersForm);
            if ($resolveSubscribersForm->validate())
            {
                $totalPages = MarketingListsUtil::getNumberOfContactPagesByResolveSubscribersFormAndCampaign($resolveSubscribersForm, $campaign);
                $marketingList = MarketingListsUtil::resolveMarketingList($resolveSubscribersForm);
                $offset = $page * $pageSize;
                $contacts = MarketingListsUtil::getContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize($resolveSubscribersForm, $campaign, $offset, $pageSize);
                $subscriberInformation = MarketingListsUtil::addNewSubscribersToMarketingList($marketingList->id, $contacts);
                if ($totalPages == ($page + 1) || $totalPages == 0)
                {
                    $subscriberInformation = array('subscribedCount' => $subscribedCount + $subscriberInformation['subscribedCount'],
                                                   'skippedCount'    => $skippedCount    + $subscriberInformation['skippedCount'],
                                                   );
                    $message = $this->renderCompleteMessageBySubscriberInformation($subscriberInformation);
                    Yii::app()->user->setFlash('notification', $message);
                    echo CJSON::encode(array('message' => $message,
                                             'type' => 'message',
                                             'redirectUrl'     => Yii::app()->createAbsoluteUrl('marketingLists/default/details/', array('id' => $marketingList->id)),
                        ));
                }
                else
                {
                    $percentageComplete = (round($page / $totalPages, 2) * 100) . ' %';
                    $message            = Zurmo::t('MarketingListsModule', 'Processing: {percentageComplete} complete',
                        array('{percentageComplete}' => $percentageComplete));
                    echo CJSON::encode(array('message'         => $message,
                                             'type'            => 'message',
                                             'nextPage'        => $page + 1,
                                             'subscribedCount' => $subscribedCount + $subscriberInformation['subscribedCount'],
                                             'skippedCount'    => $skippedCount    + $subscriberInformation['skippedCount'],
                                             'marketingListId' => $marketingList->id,
                        ));
                }
            }
            else
            {
                $errors = $resolveSubscribersForm->getErrors();
                $message = Zurmo::t('MarketingListsModule', 'There are some errors.') . '<br />';
                foreach ($errors as $attribute => $attributeErrors)
                {
                    foreach ($attributeErrors as $attributeError)
                    {
                        $message .= $attributeError . "<br />";
                    }
                }
                echo CJSON::encode(array('message' => $message, 'type' => 'message'));
            }
            Yii::app()->end(0, false);
        }

        protected function renderCompleteMessageBySubscriberInformation(array $subscriberInformation)
        {
            $message = Zurmo::t('MarketingListsModule', '{subscribedCount} contacts subscribed.',
                array('{subscribedCount}' => $subscriberInformation['subscribedCount']));
            if (array_key_exists('skippedCount', $subscriberInformation) && $subscriberInformation['skippedCount'])
            {
                $message .= ' ' . Zurmo::t('MarketingListsModule', '{skippedCount} contacts skipped, already in the list.',
                        array('{skippedCount}' => $subscriberInformation['skippedCount']));
            }
            return $message;
        }

        protected static function getSearchFormClassName()
        {
            return 'MarketingListsSearchForm';
        }

        protected function attemptToValidateAjaxSubscriberFormFromPost($resolveSubscribersForm)
        {
            if (isset($_POST['ajax']) && $_POST['ajax'] == 'edit-form')
            {
                if ($resolveSubscribersForm->validate())
                {
                    $response = CJSON::encode(array());
                }
                else
                {
                    $errorData = array();
                    foreach ($resolveSubscribersForm->getErrors() as $attribute => $errors)
                    {
                        $errorData[ZurmoHtml::activeId($resolveSubscribersForm, $attribute)] = $errors;
                    }
                    $response  = CJSON::encode($errorData);
                }
                echo $response;
                Yii::app()->end(0, false);
            }
        }
    }
?>