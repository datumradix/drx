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
     * Class containing common begin request behaviors.
     */
    abstract class BeginRequestBehaviors
    {
        public function handleSentryLogs()
        {
            if (!YII_DEBUG && defined('SUBMIT_CRASH_TO_SENTRY') && SUBMIT_CRASH_TO_SENTRY)
            {
                Yii::import('application.extensions.sentrylog.RSentryLog');
                $rSentryLog = Yii::createComponent(array('class' => 'RSentryLog', 'dsn' => Yii::app()->params['sentryDsn']));
                // Have to invoke component init(), because it is not called automatically
                $rSentryLog->init();
                $component   = Yii::app()->getComponent('log');
                $allRoutes   = $component->getRoutes();
                $allRoutes[] = $rSentryLog;
                $component->setRoutes($allRoutes);
                Yii::app()->setComponent('log', $component);
            }
        }

        /**
         * Load memcache extension if memcache extension is
         * loaded and if memcache server is available
         */
        public function handleApplicationCache()
        {
            if (MEMCACHE_ON)
            {
                //Yii::import('application.core.components.ZurmoMemCache');
                $memcacheServiceHelper = new MemcacheServiceHelper();
                if ($memcacheServiceHelper->runCheckAndGetIfSuccessful())
                {
                    $cacheComponent = Yii::createComponent(array(
                        'class'     => 'CMemCache',
                        'keyPrefix' => ZURMO_TOKEN,
                        'servers'   => Yii::app()->params['memcacheServers']));
                    Yii::app()->setComponent('cache', $cacheComponent);
                }
                // todo: Find better way to append this prefix for tests.
                // We can't put this code only in BeginRequestTestBehavior, because for API tests we are using  BeginRequestBehavior
                if (defined('IS_TEST'))
                {
                    ZurmoCache::setAdditionalStringForCachePrefix('Test');
                }
            }
        }

        /**
         * Import all files that need to be included(for lazy loading)
         */
        public function handleImports()
        {
            //Clears file cache so that everything is clean.
            if ($this->isClearCacheRequest())
            {
                GeneralCache::forgetEntry('filesClassMap');
            }
            try
            {
                // not using default value to save cpu cycles on requests that follow the first exception.
                Yii::$classMap = GeneralCache::getEntry('filesClassMap');
            }
            catch (NotFoundException $e)
            {
                $filesToInclude   = FileUtil::getFilesFromDir(Yii::app()->basePath . '/modules', Yii::app()->basePath . '/modules', 'application.modules');
                $filesToIncludeFromCore = FileUtil::getFilesFromDir(Yii::app()->basePath . '/core', Yii::app()->basePath . '/core', 'application.core');
                $totalFilesToIncludeFromModules = count($filesToInclude);

                foreach ($filesToIncludeFromCore as $key => $file)
                {
                    $filesToInclude[$totalFilesToIncludeFromModules + $key] = $file;
                }
                foreach ($filesToInclude as $file)
                {
                    Yii::import($file);
                }
                GeneralCache::cacheEntry('filesClassMap', Yii::$classMap);
            }
        }

        public function handleLibraryCompatibilityCheck()
        {
            $basePath       = Yii::app()->getBasePath();
            require_once("$basePath/../../redbean/rb.php");
            $redBeanVersion =  ZurmoRedBean::getVersion();
            $yiiVersion     =  YiiBase::getVersion();
            if ( $redBeanVersion != Yii::app()->params['redBeanVersion'])
            {
                echo Zurmo::t('ZurmoModule', 'Your RedBean version is currentVersion and it should be acceptableVersion.',
                    array(  'currentVersion' => $redBeanVersion,
                        'acceptableVersion' => Yii::app()->params['redBeanVersion']));
                Yii::app()->end(0, false);
            }
            if ( $yiiVersion != Yii::app()->params['yiiVersion'])
            {
                echo Zurmo::t('ZurmoModule', 'Your Yii version is currentVersion and it should be acceptableVersion.',
                    array(  'currentVersion' => $yiiVersion,
                        'acceptableVersion' => Yii::app()->params['yiiVersion']));
                Yii::app()->end(0, false);
            }
        }

        /**
         * In the case where you have reloaded the database, some cached items might still exist.  This is a way
         * to clear that cache. Helpful during development and testing.
         */
        public function handleClearCache()
        {
            if ($this->isClearCacheRequest())
            {
                ClearCacheDirectoriesUtil::clearCacheDirectories();
            }
        }

        public function handleStartPerformanceClock()
        {
            Yii::app()->performance->startClock();
        }

        public function handleSetupDatabaseConnection()
        {
            RedBeanDatabase::setup(Yii::app()->db->connectionString,
                Yii::app()->db->username,
                Yii::app()->db->password);
            if (!Yii::app()->isApplicationInstalled())
            {
                throw new NotSupportedException();
            }
        }

        public function handleLoadLanguage()
        {
            if (!ApiRequest::isApiRequest())
            {
                if ($lang = ArrayUtil::getArrayValue($_GET, 'lang'))
                {
                    Yii::app()->languageHelper->setActive($lang);
                }
            }
            else
            {
                if ($lang = Yii::app()->apiRequest->getLanguage())
                {
                    Yii::app()->languageHelper->setActive($lang);
                }
            }
            Yii::app()->languageHelper->load();
        }

        public function handleLoadTimeZone()
        {
            Yii::app()->timeZoneHelper->load();
        }

        public function handleCheckAndUpdateCurrencyRates()
        {
            Yii::app()->currencyHelper->checkAndUpdateCurrencyRates();
        }

        public function handleResolveCustomData()
        {
            if ($this->isResolveCustomDataRequest())
            {
                Yii::app()->custom->resolveIsCustomDataLoaded();
            }
        }

        public function handleLoadWorkflowsObserver()
        {
            Yii::app()->workflowsObserver; //runs init();
        }

        public function handleLoadReadPermissionSubscriptionObserver()
        {
            Yii::app()->readPermissionSubscriptionObserver; // runs init()
        }

        public function handleLoadContactLatestActivityDateTimeObserver()
        {
            Yii::app()->contactLatestActivityDateTimeObserver;
        }

        public function handleLoadAccountLatestActivityDateTimeObserver()
        {
            Yii::app()->accountLatestActivityDateTimeObserver;
        }

        protected function isClearCacheRequest()
        {
            return $this->isQueryStringVariableSetToOne('clearCache');
        }

        protected function isResolveCustomDataRequest()
        {
            return $this->isQueryStringVariableSetToOne('resolveCustomData');
        }

        protected function isQueryStringVariableSetToOne($key)
        {
            return Yii::app()->request->getQuery($key);
        }
    }
?>