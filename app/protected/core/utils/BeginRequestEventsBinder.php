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

    Yii::import('application.core.utils.RequestEventsBinder');
    /**
     * Class responsible for attaching appropriate events to request
     */
    class BeginRequestEventsBinder extends RequestEventsBinder
    {
        const EVENT_NAME                            = 'onBeginRequest';

        protected function resolveDefaultHandlerForApiRequest()
        {
            Yii::import('application.core.utils.ApiBeginRequestBehaviors');
            return 'ApiBeginRequestBehaviors';
        }

        protected function resolveDefaultHandlerForApplicationRequest()
        {
            Yii::import('application.core.utils.ApplicationBeginRequestBehaviors');
            return 'ApplicationBeginRequestBehaviors';
        }

        protected function resolveDefaultHandlerForCommandRequest()
        {
            Yii::import('application.core.utils.CommandBeginRequestBehaviors');
            return 'CommandBeginRequestBehaviors';
        }

        protected function resolveDefaultHandlerForTestRequest()
        {
            Yii::import('application.core.utils.TestBeginRequestBehaviors');
            return 'TestBeginRequestBehaviors';
        }

        protected function attachApiRequestEvents()
        {
            $this->attachApiRequestCommonEvents();
            if ($this->installed)
            {
                $this->attachApiRequestEventsForInstalledApplication();
            }
        }

        protected function attachApplicationRequestEvents()
        {
            $this->attachApplicationRequestCommonEvents();
            if ($this->installed)
            {
                $this->attachApplicationRequestEventsForInstalledApplication();
            }
            else
            {
                $this->attachApplicationRequestEventsForNonInstalledApplication();
            }
        }

        protected function attachCommandRequestEvents()
        {
            $this->attachCommandRequestCommonEvents();
            if ($this->installed)
            {
                $this->attachCommandEventsForInstalledApplication();
            }
        }

        protected function attachApiRequestCommonEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleSentryLogs'),
                $this->resolveEventDefinition('handleApplicationCache'),
                $this->resolveEventDefinition('validateCsrfToken', Yii::app()->request),
                $this->resolveEventDefinition('handleImports'),
                $this->resolveEventDefinition('handleSetupDatabaseConnection'),
                $this->resolveEventDefinition('handleDisableGamification'),
                $this->resolveEventDefinition('handleInitApiRequest'),
                $this->resolveEventDefinition('handleBeginApiRequest'),
                $this->resolveEventDefinition('handleLibraryCompatibilityCheck'),
                $this->resolveEventDefinition('handleStartPerformanceClock'),
            );
            $this->attachEventsByDefinitions($eventDefinition);

        }

        protected function attachApiRequestEventsForInstalledApplication()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleClearCache'),
                $this->resolveEventDefinition('handleLoadLanguage'),
                $this->resolveEventDefinition('handleLoadTimeZone'),
                $this->resolveEventDefinition('handleLoadWorkflowsObserver'),
                $this->resolveEventDefinition('handleLoadReadPermissionSubscriptionObserver'),
                $this->resolveEventDefinition('handleLoadContactLatestActivityDateTimeObserver'),
                $this->resolveEventDefinition('handleLoadAccountLatestActivityDateTimeObserver'),
                $this->resolveEventDefinition('handleCheckAndUpdateCurrencyRates'),
                $this->resolveEventDefinition('handleResolveCustomData'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }

        protected function attachApplicationRequestCommonEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleSentryLogs'),
                $this->resolveEventDefinition('handleApplicationCache'),
                $this->resolveEventDefinition('handleImports'),
                $this->resolveEventDefinition('handleLibraryCompatibilityCheck'),
                $this->resolveEventDefinition('handleStartPerformanceClock'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }

        protected function attachApplicationRequestEventsForNonInstalledApplication()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleInstanceFolderCheck'),
                $this->resolveEventDefinition('handleInstallCheck'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }

        /**
         * @see CommandBeginRequestBehavior, make sure if you change this array, you add anything needed
         * for the command behavior as well.
         */
        protected function attachApplicationRequestEventsForInstalledApplication()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleSetupDatabaseConnection'),
                $this->resolveEventDefinition('handleBeginRequest'),
                $this->resolveEventDefinition('handleClearCache'),
                $this->resolveEventDefinition('handleLoadLanguage'),
                $this->resolveEventDefinition('handleLoadTimeZone'),
                $this->resolveEventDefinition('handleUserTimeZoneConfirmed'),
                $this->resolveEventDefinition('handleLoadActivitiesObserver'),
                $this->resolveEventDefinition('handleLoadConversationsObserver'),
                $this->resolveEventDefinition('handleLoadEmailMessagesObserver'),
                $this->resolveEventDefinition('handleLoadWorkflowsObserver'),
                $this->resolveEventDefinition('handleLoadReadPermissionSubscriptionObserver'),
                $this->resolveEventDefinition('handleLoadContactLatestActivityDateTimeObserver'),
                $this->resolveEventDefinition('handleLoadAccountLatestActivityDateTimeObserver'),
                $this->resolveEventDefinition('handleLoadAccountContactAffiliationObserver'),
                $this->resolveEventDefinition('handleLoadGamification'),
                $this->resolveEventDefinition('handleCheckAndUpdateCurrencyRates'),
                $this->resolveEventDefinition('handleResolveCustomData'),
                $this->resolveEventDefinition('handlePublishLogoAssets'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }

        protected function attachCommandRequestCommonEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleApplicationCache'),
                $this->resolveEventDefinition('handleImports'),
                $this->resolveEventDefinition('handleLibraryCompatibilityCheck'),
                $this->resolveEventDefinition('handleStartPerformanceClock'),
                $this->resolveEventDefinition('handleLoadLanguage'),
                $this->resolveEventDefinition('handleLoadTimeZone'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }

        protected function attachCommandEventsForInstalledApplication()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleSetupDatabaseConnection'),
                $this->resolveEventDefinition('handleLoadActivitiesObserver'),
                $this->resolveEventDefinition('handleLoadConversationsObserver'),
                $this->resolveEventDefinition('handleLoadEmailMessagesObserver'),
                $this->resolveEventDefinition('handleLoadWorkflowsObserver'),
                $this->resolveEventDefinition('handleLoadReadPermissionSubscriptionObserver'),
                $this->resolveEventDefinition('handleLoadAccountContactAffiliationObserver'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }

        protected function attachTestRequestEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleApplicationCache'),
                $this->resolveEventDefinition('handleImports'),
                $this->resolveEventDefinition('handleLoadWorkflowsObserver'),
                $this->resolveEventDefinition('handleLoadReadPermissionSubscriptionObserver'),
            );
            $this->attachEventsByDefinitions($eventDefinition);
        }
    }
?>