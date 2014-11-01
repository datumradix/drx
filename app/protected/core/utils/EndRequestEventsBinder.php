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

    /**
     * Class responsible for attaching appropriate events to request
     */
    class EndRequestEventsBinder extends RequestEventsBinder
    {
        const EVENT_NAME                            = 'onEndRequest';

        protected function resolveDefaultHandlerForApiRequest()
        {
        }

        protected function resolveDefaultHandlerForCommandRequest()
        {
        }

        protected function attachApiRequestEvents()
        {
        }

        protected function attachCommandRequestEvents()
        {
        }

        protected function resolveDefaultHandlerForApplicationRequest()
        {
            Yii::import('application.core.utils.ApplicationEndRequestBehaviors');
            return 'ApplicationEndRequestBehaviors';
        }

        protected function resolveDefaultHandlerForTestRequest()
        {
            Yii::import('application.core.utils.TestEndRequestBehaviors');
            return 'TestEndRequestBehaviors';
        }

        protected function attachApplicationRequestEvents()
        {
            $this->attachInstalledApplicationRequestEvents();
            $this->attachApplicationRequestCommonEvents();
        }

        protected function attachInstalledApplicationRequestEvents()
        {
            if ($this->installed)
            {
                $this->resolveEventsAttachment($this->resolveInstallationApplicationRequestEvents());
            }
        }

        protected function resolveInstallationApplicationRequestEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleGamification'),
                $this->resolveEventDefinition('handleJobQueue'),
            );
            return $eventDefinition;
        }

        protected function attachApplicationRequestCommonEvents()
        {
            $this->resolveEventsAttachment($this->resolveApplicationRequestCommonEvents());
        }

        protected function resolveApplicationRequestCommonEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleSaveGlobalStateCheck'),
                $this->resolveEventDefinition('handleEndLogRouteEvents'),
                $this->resolveEventDefinition('handleResolveRedBeanQueriesToFile'),
                $this->resolveEventDefinition('handleEndRequest'),
            );
            return $eventDefinition;
        }

        protected function attachTestRequestEvents()
        {
            $this->resolveEventsAttachment($this->resolveTestRequestEvents());
        }

        protected function resolveTestRequestEvents()
        {
            $eventDefinition    = array(
                $this->resolveEventDefinition('handleGamification'),
                $this->resolveEventDefinition('handleJobQueue'),
                $this->resolveEventDefinition('handleEndRequest'),
            );
            return $eventDefinition;
        }
    }
?>