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
    * Users API Controller
    */
    class UsersUserApiController extends ZurmoModuleApiController
    {
        // Alter parent filters to allow access to Users module when auser is trying to access getAuthenticatedUser via API
        public function filters()
        {
            $filters = parent::filters();
            foreach ($filters as $key => $filter)
            {
                if (is_array($filter) &&
                    isset($filter[0]) && $filter[0] == self::getRightsFilterPath() &&
                    isset($filter['moduleClassName']) && $filter['moduleClassName'] == 'UsersModule' &&
                    isset($filter['rightName']) && $filter['rightName'] == UsersModule::getAccessRight())
                {
                    $filters[$key][0] = $filters[$key][0] . ' - getAuthenticatedUser, searchUsersByEmails';
                }
            }
            return $filters;
        }

        protected static function getSearchFormClassName()
        {
            return 'UsersSearchForm';
        }

        /**
         * Create new model, and send response
         * @throws ApiException
         */
        public function actionCreate()
        {
            $params = Yii::app()->apiRequest->getParams();
            if (!isset($params['data']))
            {
                $message = Zurmo::t('ZurmoModule', 'Please provide data.');
                throw new ApiException($message);
            }
            $this->resolvePasswordParameter($params);
            $result    =  $this->processCreate($params['data']);
            Yii::app()->apiHelper->sendResponse($result);
        }

        /**
         * Update model and send response
         * @throws ApiException
         */
        public function actionUpdate()
        {
            $params = Yii::app()->apiRequest->getParams();
            if (!isset($params['id']))
            {
                $message = Zurmo::t('ZurmoModule', 'The ID specified was invalid.');
                throw new ApiException($message);
            }
            $this->resolvePasswordParameter($params);
            $result    =  $this->processUpdate((int)$params['id'], $params['data']);
            Yii::app()->apiHelper->sendResponse($result);
        }

        /**
         * Get current authenticated user details
         * @throws ApiException
         */
        public function actionGetAuthenticatedUser()
        {
            if (Yii::app()->user->isGuest)
            {
                $message = Zurmo::t('ZurmoModule', 'You must be logged to use this method.');
                throw new ApiException($message);
            }
            try
            {
                $data           = static::getModelToApiDataUtilData(Yii::app()->user->userModel);
                $resultClassName = Yii::app()->apiRequest->getResultClassName();
                $result                    = new $resultClassName(ApiResponse::STATUS_SUCCESS, $data, null, null);
            }
            catch (Exception $e)
            {
                $message = $e->getMessage();
                throw new ApiException($message);
            }
            Yii::app()->apiHelper->sendResponse($result);
        }

        /**
         * Get users by emails
         * @throws ApiException
         */
        public function actionSearchUsersByEmails()
        {
            $params = Yii::app()->apiRequest->getParams();
            if (!isset($params['data']))
            {
                $message = Zurmo::t('ZurmoModule', 'Please provide data.');
                throw new ApiException($message);
            }
            if (!isset($params['data']['emails']) || !is_array($params['data']['emails']) || empty($params['data']['emails']))
            {
                $message = Zurmo::t('ZurmoModule', 'Emails parameters must exist, must be an array and must contain at least one email address.');
                throw new ApiException($message);
            }
            $data = array();
            $data['users'] = array();
            try
            {
                foreach ($params['data']['emails'] as $email)
                {
                    $usersByEmail = UserSearch::getUsersByEmailAddress($email);
                    if (!empty($usersByEmail) && count($usersByEmail) == 1)
                    {
                        $user = array();
                        $user['id']        = $usersByEmail[0]->id;
                        $user['firstName'] = $usersByEmail[0]->firstName;
                        $user['lastName']  = $usersByEmail[0]->lastName;
                        $user['username']  = $usersByEmail[0]->username;
                        if ($usersByEmail[0]->primaryEmail->emailAddress != null)
                        {
                            $user['email'] = $usersByEmail[0]->primaryEmail->emailAddress;
                        }
                        $data['users'][] = $user;
                    }
                }
                $result = new ApiResult(ApiResponse::STATUS_SUCCESS, $data, null, null);
            }
            catch (Exception $e)
            {
                $message = $e->getMessage();
                throw new ApiException($message);
            }
            Yii::app()->apiHelper->sendResponse($result);
        }

        protected function resolvePasswordParameter(& $params)
        {
            // We have to encrypt password
            if (isset($params['data']['password']) && $params['data']['password'] != '')
            {
                $params['data']['hash'] = User::encryptPassword($params['data']['password']);
            }
            unset($params['data']['password']);
        }
    }
?>
