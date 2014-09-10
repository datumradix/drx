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
     * Class responsible for attaching appropriate events
     */
    abstract class EventsBinder
    {
        const EVENT_NAME                            = null;

        protected $owner                            = null;

        protected $installed                        = null;

        protected $defaultHandler                   = null;

        abstract public function bind();

        abstract protected function resolveDefaultHandlerClassName();

        public function __construct(CComponent $owner)
        {
            assert('is_object($owner)');
            $this->owner                            = $owner;
            $this->installed                        = Yii::app()->isApplicationInstalled();
            $this->resolveDefaultHandler();
        }

        protected function attachEventsByDefinitions(array $definitions)
        {
            array_walk($definitions, array($this, 'attachEvent'));
        }

        protected function attachEvent(array $definition)
        {
            $method     = null;
            $handler    = $this->defaultHandler;
            extract($definition);
            if (!isset($method))
            {
                throw new NotSupportedException('Method name should not be empty');
            }
            if (!isset($handler))
            {
                throw new NotSupportedException('Unable to resolve handler');
            }
            if (static::EVENT_NAME === null)
            {
                throw new NotSupportedException('Event Name must be specified.');
            }
            $this->owner->attachEventHandler(static::EVENT_NAME, array($handler, $method));
        }

        protected function resolveEventDefinition($method, $handler = null)
        {
            $definition = array('method' => $method);
            if ($handler)
            {
                $definition['handler'] = $handler;
            }
            return $definition;
        }

        protected function resolveDefaultHandler()
        {
            $defaultHandlerClassName    = $this->resolveDefaultHandlerClassName();
            $this->defaultHandler       = new $defaultHandlerClassName();
        }

    }
?>