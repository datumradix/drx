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

    Yii::import('application.core.utils.BeginRequestBehaviors');
    /**
     * Class containing application/non-api begin request behaviors.
     */
    class ApplicationBeginRequestBehaviors extends BeginRequestBehaviors
    {
        protected static $allowedGuestUserRoutes = array(
                                                        'zurmo/default/unsupportedBrowser',
                                                        'zurmo/default/login',
                                                        'tracking/default/track',
                                                        'marketingLists/external/',
                                                        'contacts/external/',
                                                        'zurmo/imageModel/getImage/',
                                                        'zurmo/imageModel/getThumb/',
                                                        'min/serve');

        public function handleLoadActivitiesObserver()
        {
            $activitiesObserver = new ActivitiesObserver();
            $activitiesObserver->init();
        }

        public function handleLoadConversationsObserver()
        {
            $conversationsObserver = new ConversationsObserver();
            $conversationsObserver->init();
        }

        public function handleLoadEmailMessagesObserver()
        {
            $emailMessagesObserver = new EmailMessagesObserver();
            $emailMessagesObserver->init();
        }

        public function handleLoadAccountContactAffiliationObserver()
        {
            $accountContactAffiliationObserver = new AccountContactAffiliationObserver();
            $accountContactAffiliationObserver->init();
        }

        public function handleLoadGamification()
        {
            Yii::app()->gameHelper;
            Yii::app()->gamificationObserver; //runs init();
        }

        public function handlePublishLogoAssets()
        {
            if (null !== ZurmoConfigurationUtil::getByModuleName('ZurmoModule', 'logoFileModelId'))
            {
                $logoFileModelId        = ZurmoConfigurationUtil::getByModuleName('ZurmoModule', 'logoFileModelId');
                $logoFileModel          = FileModel::getById($logoFileModelId);
                $logoFileSrc            = Yii::app()->getAssetManager()->getPublishedUrl(Yii::getPathOfAlias('application.runtime.uploads') .
                    DIRECTORY_SEPARATOR . $logoFileModel->name);
                //logoFile is either not published or we have dangling url for asset
                if ($logoFileSrc === false || file_exists($logoFileSrc) === false)
                {
                    //Logo file is not published in assets
                    //Check if it exists in runtime/uploads
                    if (file_exists(Yii::getPathOfAlias('application.runtime.uploads') .
                            DIRECTORY_SEPARATOR . $logoFileModel->name) === false)
                    {
                        $logoFilePath    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $logoFileModel->name;
                        file_put_contents($logoFilePath, $logoFileModel->fileContent->content, LOCK_EX);
                        ZurmoUserInterfaceConfigurationFormAdapter::publishLogo($logoFileModel->name, $logoFilePath);
                    }
                    else
                    {
                        //Logo File exist in runtime/uploads but not published
                        Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('application.runtime.uploads') .
                            DIRECTORY_SEPARATOR . $logoFileModel->name);
                    }
                }
            }
        }

        /**
         * This check is required during installation since if runtime, assets and data folders are missing
         * yii web application can not be started correctly.
         */
        public function handleInstanceFolderCheck()
        {
            $instanceFoldersServiceHelper = new InstanceFoldersServiceHelper();
            if (!$instanceFoldersServiceHelper->runCheckAndGetIfSuccessful())
            {
                echo $instanceFoldersServiceHelper->getMessage();
                Yii::app()->end(0, false);
            }
        }

        public function handleInstallCheck()
        {
            $allowedInstallUrls = array (
                Yii::app()->createUrl('zurmo/default/unsupportedBrowser'),
                Yii::app()->createUrl('install/default'),
                Yii::app()->createUrl('install/default/welcome'),
                Yii::app()->createUrl('install/default/checkSystem'),
                Yii::app()->createUrl('install/default/settings'),
                Yii::app()->createUrl('install/default/runInstallation'),
                Yii::app()->createUrl('install/default/installDemoData'),
                Yii::app()->createUrl('min/serve')
            );
            $requestedUrl = Yii::app()->getRequest()->getUrl();
            $redirect = true;
            foreach ($allowedInstallUrls as $allowedUrl)
            {
                if (strpos($requestedUrl, $allowedUrl) === 0)
                {
                    $redirect = false;
                    break;
                }
            }
            if ($redirect)
            {
                $url = Yii::app()->createUrl('install/default');
                Yii::app()->request->redirect($url);
            }
        }

        /**
         * Called if installed, and logged in.
         */
        public function handleUserTimeZoneConfirmed()
        {
            if (!Yii::app()->user->isGuest && !Yii::app()->timeZoneHelper->isCurrentUsersTimeZoneConfirmed())
            {
                $allowedTimeZoneConfirmBypassUrls = array (
                    Yii::app()->createUrl('users/default/confirmTimeZone'),
                    Yii::app()->createUrl('min/serve'),
                    Yii::app()->createUrl('zurmo/default/logout'),
                );
                $reqestedUrl = Yii::app()->getRequest()->getUrl();
                $isUrlAllowedToByPass = false;
                foreach ($allowedTimeZoneConfirmBypassUrls as $url)
                {
                    if (strpos($reqestedUrl, $url) === 0)
                    {
                        $isUrlAllowedToByPass = true;
                    }
                }
                if (!$isUrlAllowedToByPass)
                {
                    $url = Yii::app()->createUrl('users/default/confirmTimeZone');
                    Yii::app()->request->redirect($url);
                }
            }
        }

        public function handleBeginRequest()
        {
            // Create list of allowed urls.
            // Those urls should be accessed during upgrade process too.
            $allowedGuestUserRoutes = static::getAllowedGuestUserRoutes();
            foreach ($allowedGuestUserRoutes as $allowedGuestUserRoute)
            {
                $allowedGuestUserUrls[] = Yii::app()->createUrl($allowedGuestUserRoute);
            }
            $requestedUrl = Yii::app()->getRequest()->getUrl();
            $isUrlAllowedToGuests = false;
            foreach ($allowedGuestUserUrls as $url)
            {
                if (strpos($requestedUrl, $url) === 0)
                {
                    $isUrlAllowedToGuests = true;
                }
            }

            if (Yii::app()->user->isGuest)
            {
                if (!$isUrlAllowedToGuests)
                {
                    Yii::app()->user->loginRequired();
                }
            }
            else
            {
                if (Yii::app()->isApplicationInMaintenanceMode())
                {
                    if (!$isUrlAllowedToGuests)
                    {
                        // Allow access only to users that belongs to Super Administrators.
                        $group = Group::getByName(Group::SUPER_ADMINISTRATORS_GROUP_NAME);
                        if (!$group->users->contains(Yii::app()->user->userModel))
                        {
                            echo Zurmo::t('ZurmoModule', 'Application is in maintenance mode. Please try again later.');
                            exit;
                        }
                        else
                        {
                            // Super Administrators can access all pages, but inform them that application is in maintenance mode.
                            Yii::app()->user->setFlash('notification', Zurmo::t('ZurmoModule', 'Application is in maintenance mode, and only Super Administrators can access it.'));
                        }
                    }
                }
            }
        }

        /**
         * Get Allowed Guest User Routes
         * @return array
         */
        protected static function getAllowedGuestUserRoutes()
        {
            return self::$allowedGuestUserRoutes;
        }
    }
?>