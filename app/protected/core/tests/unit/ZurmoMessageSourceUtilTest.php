<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2012 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 113 McHenry Road Suite 207,
     * Buffalo Grove, IL 60089, USA. or at email address contact@zurmo.com.
     ********************************************************************************/

    /**
     * Test class to test the ZurmoMessageSourceUtil class.
     */
    class ZurmoMessageSourceUtilTest extends BaseTest
    {
        protected static $testLanguageCode = 'de';

        protected static $testCategory = 'UtilTest';

        protected static $testMessagesNew = array(
                    'messageUtil1-source'=>'messageUtil1-translation',
                    'messageUtil2-source'=>'messageUtil2-translation',
                    'messageUtil3-source'=>'messageUtil3-translation',
                    'messageUtil4-source'=>'messageUtil4-translation',
                    'messageUtil5-source'=>'messageUtil5-translation',
                    'messageUtil6-source'=>'messageUtil6-translation'
        );

        protected static $testMessagesUpdated = array(
                    'messageUtil1-source'=>'messageUtil1-Updatedtranslation',
                    'messageUtil2-source'=>'messageUtil2-Updatedtranslation',
                    'messageUtil3-source'=>'messageUtil3-Updatedtranslation',
                    'messageUtil4-source'=>'messageUtil4-Updatedtranslation',
                    'messageUtil5-source'=>'messageUtil5-Updatedtranslation',
                    'messageUtil6-source'=>'messageUtil6-Updatedtranslation'
        );

        public function testImportMessagesArrayNew()
        {
            ZurmoMessageSourceUtil::importMessagesArray(
                                                        self::$testLanguageCode,
                                                        self::$testCategory,
                                                        self::$testMessagesNew
                                                        );
            
            $messageSource = new ZurmoMessageSource();

            foreach (self::$testMessagesNew as $source=>$compareTranslation)
            {
                $translation = $messageSource->translate(
                                                         self::$testCategory,
                                                         $source,
                                                         self::$testLanguageCode
                                                         );
                $this->assertEquals($translation, $compareTranslation);
            }
        }

        public function testImportMessagesArrayUpdated()
        {
            ZurmoMessageSourceUtil::importMessagesArray(
                                                        self::$testLanguageCode,
                                                        self::$testCategory,
                                                        self::$testMessagesUpdated
                                                        );
            
            $messageSource = new ZurmoMessageSource();

            foreach (self::$testMessagesUpdated as $source=>$compareTranslation)
            {
                $translation = $messageSource->translate(
                                                         self::$testCategory,
                                                         $source,
                                                         self::$testLanguageCode
                                                         );
                $this->assertEquals($translation, $compareTranslation);
            }
        }
    }
?>
