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
    * Meetings API Controller
    */
    class MeetingsMeetingApiController extends ZurmoSecurableItemApiController
    {
        protected static function getSearchFormClassName()
        {
            return 'MeetingsSearchForm';
        }

        /**
         * Get array or models and send response
         */
        public function actionGetDeletedItems()
        {
            $params = Yii::app()->apiRequest->getParams();
            $result    =  $this->processGetDeletedItems($params['data']);
            Yii::app()->apiHelper->sendResponse($result);
        }

        /**
         * Get array or models and send response
         */
        public function actionGetCreatedItems()
        {
            $params = Yii::app()->apiRequest->getParams();
            $result    =  $this->processGetCreatedItems($params['data']);
            Yii::app()->apiHelper->sendResponse($result);
        }

        /**
         * Get array or models and send response
         */
        public function actionGetModifiedItems()
        {
            $params = Yii::app()->apiRequest->getParams();
            $result    =  $this->processGetModifiedItems($params['data']);
            Yii::app()->apiHelper->sendResponse($result);
        }
        
        /**
         * Get all meetings attendees including users, contacts, opportunities and accounts
         */
        public function actionGetAttendees()
        {
            $params     = Yii::app()->apiRequest->getParams();
            $result     =  $this->processAttendees($params['data']);
            Yii::app()->apiHelper->sendResponse($result);
        }

        /**
         * Get all attendees
         * Function get user attendees and attendees from activity items and merge them into one array
         * @param $params
         * @return ApiResult
         * @throws ApiException
         */
        protected function processAttendees($params)
        {
            try
            {
                $meetingId            = $params['id'];
                $meeting               = Meeting::getById(intval($meetingId));
                $activityItemAttendees = $this->getAttendeesFromActivityItems($meeting);
                $userAttendees         = $this->getUserAttendees($meeting);
                $data                  = array_merge($activityItemAttendees, $userAttendees);
                $result = new ApiResult(ApiResponse::STATUS_SUCCESS, $data, null, null);
            }
            catch (Exception $e)
            {
                $message = $e->getMessage();
                throw new ApiException($message);
            }
            return $result;

        }

        /**
         * Get user attendees
         * @param $meeting
         * @return array
         */
        protected function getUserAttendees($meeting)
        {
            $data         = array();
            foreach ($meeting->userAttendees as $item)
            {
                $data['User'][] = array('id' => $item->id);
            }
            return $data;
        }

        /**
         * Get meeting attendees from activity items. It returns Contact, Opportunity or Account ids
         * @param $meeting
         * @return array
         * @throws Exception
         */
        public function getAttendeesFromActivityItems($meeting)
        {
            $data         = array();
            $activityClassNames = ActivitiesUtil::getActivityItemsModelClassNames();
            try
            {
                foreach ($activityClassNames as $activityClassName)
                {
                    foreach ($meeting->activityItems as $activityItem)
                    {
                        try
                        {
                            $modelDerivationPathToItem  = RuntimeUtil::getModelDerivationPathToItem($activityClassName);
                            $castedDownModel            = $activityItem->castDown(array($modelDerivationPathToItem));
                            $data[$activityClassName][] = array('id' => $castedDownModel->id);
                        }
                        catch (NotFoundException $e)
                        {
                        }
                    }
                }
            }
            catch (Exception $e)
            {
                $message = $e->getMessage();
                throw new Exception($message);
            }
            return $data;
        }
    }
?>
