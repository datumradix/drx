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

    Yii::import('application.core.utils.EventsBinder');
    /**
     * Base Class responsible for attaching appropriate events to request
     */
    abstract class RequestEventsBinder extends EventsBinder
    {
        const API_REQUEST                           = 1;

        const APPLICATION_REQUEST                   = 2;

        const COMMAND_REQUEST                       = 3;

        const TEST_REQUEST                          = 4;

        protected $requestType                      = null;

        abstract protected function attachApiRequestEvents();

        abstract protected function attachApplicationRequestEvents();

        abstract protected function attachCommandRequestEvents();

        abstract protected function attachTestRequestEvents();

        abstract protected function resolveDefaultHandlerForApiRequest();

        abstract protected function resolveDefaultHandlerForApplicationRequest();

        abstract protected function resolveDefaultHandlerForCommandRequest();

        abstract protected function resolveDefaultHandlerForTestRequest();

        public function __construct($requestType, CComponent $owner)
        {
            assert('is_int($requestType)');
            $this->requestType                      = $requestType;
            // we call parent at the end so we have set requestType to let resolveDefaultHandler do its job
            // without issues.
            parent::__construct($owner);
        }

        public function bind()
        {
            switch ($this->requestType)
            {
                case static::API_REQUEST:
                    $this->attachApiRequestEvents();
                    break;
                case static::APPLICATION_REQUEST:
                    $this->attachApplicationRequestEvents();
                    break;
                case static::COMMAND_REQUEST:
                    $this->attachCommandRequestEvents();
                    break;
                case static::TEST_REQUEST:
                    $this->attachTestRequestEvents();
                    break;
            }
        }

        protected function resolveDefaultHandlerClassName()
        {
            $className      = null;
            switch($this->requestType)
            {
                case static::API_REQUEST:
                    $className   = $this->resolveDefaultHandlerForApiRequest();
                    break;
                case static::APPLICATION_REQUEST:
                    $className   = $this->resolveDefaultHandlerForApplicationRequest();
                    break;
                case static::COMMAND_REQUEST:
                    $className   = $this->resolveDefaultHandlerForCommandRequest();
                    break;
                case static::TEST_REQUEST:
                    $className   = $this->resolveDefaultHandlerForTestRequest();
                    break;
                default:
                    throw new NotSupportedException('Invalid Request Type');
                    break;
            }
            return $className;
        }
    }
?>