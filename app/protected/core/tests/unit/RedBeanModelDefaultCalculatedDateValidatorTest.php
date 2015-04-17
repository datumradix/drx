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

    class ThingWithCalculatedDates extends RedBeanModel
    {
        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'now',
                    'today',
                    'tomorrow',
                    'yesterday',
                ),
                'rules' => array(
                    array('now',        'type', 'type' => 'datetime'),
                    array('today',      'type', 'type' => 'date'),
                    array('tomorrow',   'type', 'type' => 'date'),
                    array('yesterday',  'type', 'type' => 'date'),
                    array('now',       'dateTimeDefault', 'value' => DateTimeCalculatorUtil::NOW),
                    array('today',     'dateTimeDefault', 'value' => DateTimeCalculatorUtil::TODAY),
                    array('tomorrow',  'dateTimeDefault', 'value' => DateTimeCalculatorUtil::TOMORROW),
                    array('yesterday', 'dateTimeDefault', 'value' => DateTimeCalculatorUtil::YESTERDAY),
                ),
            );
            return $metadata;
        }
    }

    class RedBeanModelDefaultCalculatedDateValidatorTest extends BaseTest
    {
        public static function getDependentTestModelClassNames()
        {
            return array('ThingWithCalculatedDates');
        }

        public function testDefaultCalculatedDates()
        {
            $now = time();
            $thing            = new ThingWithCalculatedDates();
            $thingNowValue    = $thing->now;
            $dateTimeThingNow = new DateTime($thing->now);
            $this->assertEquals($now, $dateTimeThingNow->getTimestamp(), '', 2);
            $this->assertTrue($thing->save());
            $this->assertEquals($thingNowValue, $thing->now);
            $stamp = Yii::app()->dateFormatter->format(DatabaseCompatibilityUtil::getDateFormat(), $now);
            $this->assertEquals($stamp, $thing->today);
            $stamp = Yii::app()->dateFormatter->format(DatabaseCompatibilityUtil::getDateFormat(), $now + 24 * 60 * 60);
            $this->assertEquals($stamp, $thing->tomorrow);
            $stamp = Yii::app()->dateFormatter->format(DatabaseCompatibilityUtil::getDateFormat(), $now - 24 * 60 * 60);
            $this->assertEquals($stamp, $thing->yesterday);
        }
    }
?>
