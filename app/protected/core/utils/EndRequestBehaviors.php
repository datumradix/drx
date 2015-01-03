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
     * Class containing common end request behaviors.
     */
    abstract class EndRequestBehaviors
    {
        // Save global state into ZurmoConfig, before handleEndRequest event handler is called.
        // This is needed because handleEndRequest is attached to component before saveGlobalState handler
        // and therefore will be execute before, so we need to change order.
        public function handleSaveGlobalStateCheck()
        {
            $allEventHandlers = Yii::app()->getEventHandlers('onEndRequest');

            if (count($allEventHandlers))
            {
                foreach ($allEventHandlers as $eventHandler)
                {
                    if ($eventHandler[0] instanceof CApplication && $eventHandler[1] == 'saveGlobalState')
                    {
                        Yii::app()->saveGlobalState();
                    }
                }
            }
        }

        public function handleEndLogRouteEvents($event)
        {
            $allEventHandlers = Yii::app()->getEventHandlers('onEndRequest');

            if (count($allEventHandlers))
            {
                foreach ($allEventHandlers as $eventHandler)
                {
                    if ($eventHandler[0] instanceof CLogRouter && $eventHandler[1] == 'processLogs')
                    {
                        Yii::app()->log->processLogs($event);
                    }
                }
            }
        }

        public function handleResolveRedBeanQueriesToFile()
        {
            if (defined('REDBEAN_DEBUG_TO_FILE') && REDBEAN_DEBUG_TO_FILE)
            {
                if (isset(Yii::app()->queryFileLogger))
                {
                    Yii::app()->queryFileLogger->processLogs();
                }
            }
        }

        public function handleEndRequest()
        {
            RedBeanDatabase::close();
            exit;
        }

        /**
         * Process any points that need to be tabulated based on scoring that occurred during the request.
         * Use of areAllClassesImported() is to ensure the available classes are imported to run this end request.
         * If not, then an error has occurred very early in execution and these classes are not required to run.
         * Does not run if there is already an error as this can cause problems if an additional error is generated
         * during this execution.
         */
        public function handleGamification()
        {
            if (Yii::app()->errorHandler->error == null &&
                Yii::app()->areAllClassesImported() &&
                Yii::app()->user->userModel != null &&
                Yii::app()->gameHelper instanceof GameHelper)
            {
                Yii::app()->gameHelper->processDeferredPoints();
                Yii::app()->gameHelper->resolveNewBadges();
                Yii::app()->gameHelper->resolveLevelChange();
            }
        }

        /**
         * Use of areAllClassesImported() is to ensure the available classes are imported to run this end request.
         * If not, then an error has occurred very early in execution and these classes are not required to run.
         * Does not run if there is already an error as this can cause problems if an additional error is generated
         * during this execution.
         */
        public function handleJobQueue()
        {
            if (Yii::app()->errorHandler->error == null &&
                Yii::app()->areAllClassesImported() &&
                Yii::app()->user->userModel != null)
            {
                Yii::app()->jobQueue->processAll();
            }
        }
    }
?>