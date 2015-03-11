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

    class MergeTagsReportRelationsAndAttributesToTreeAdapterTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function setup()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testGetData()
        {
            $moduleClassName = Contact::getModuleClassName();
            $type     = Report::TYPE_ROWS_AND_COLUMNS;
            $treeType = ComponentForReportForm::TYPE_FILTERS;
            $report   = new Report();
            $report->setModuleClassName($moduleClassName);
            $report->setType($type);
            $reportToTreeAdapter = new MergeTagsReportRelationsAndAttributesToTreeAdapter($report, $treeType, 'EmailTemplate');
            
            $emailTemplate                  = new EmailTemplate();
            $emailTemplate->type            = EmailTemplate::TYPE_CONTACT;
            $emailTemplate->subject         = 'Another Test subject';
            $emailTemplate->name            = 'Another Test Email Template';
            $emailTemplate->textContent     = 'Text Content ';
            $emailTemplate->htmlContent     = 'Html Content ';
            $emailTemplate->builtType       = EmailTemplate::BUILT_TYPE_PASTED_HTML;
            $emailTemplate->modelClassName  = 'Contact';
            
            // By testing the getData method we're actually testing that all Merge Tags are valid
            // and that we can save the EmailTemplate with each Merge Tag without having the error 'Invalid Merge Tag'.
            $data = $reportToTreeAdapter->getData('source');
            foreach($data[1]['children'] as $child)
            {
                if (isset($child['dataValue']) && $child['dataValue'])
                {
                    $emailTemplate->textContent .= ' ' . $child['dataValue'];
                    $emailTemplate->htmlContent .= ' ' . $child['dataValue'];
                }
            }
            file_put_contents('/var/www/zurmo/app/protected/runtime/boban.log', print_r($emailTemplate->textContent,true)."\n", FILE_APPEND);
            $validated                      = $emailTemplate->validate(null, false, true);
            $this->assertTrue($validated);
            $this->assertEmpty($emailTemplate->getErrors());
            $this->assertTrue($emailTemplate->save());
            
            $data = $reportToTreeAdapter->getData('EmailTemplate_createdByUser');
            $emailTemplate->textContent = '';
            $emailTemplate->htmlContent = '';
            foreach($data as $child)
            {
                if (isset($child['dataValue']) && $child['dataValue'])
                {
                    $emailTemplate->textContent .= ' ' . $child['dataValue'];
                    $emailTemplate->htmlContent .= ' ' . $child['dataValue'];
                }
            }
            $validated                      = $emailTemplate->validate(null, false, true);
            $this->assertTrue($validated);
            $this->assertEmpty($emailTemplate->getErrors());
            $this->assertTrue($emailTemplate->save());
            
            $data = $reportToTreeAdapter->getData('EmailTemplate_owner');
            $emailTemplate->textContent = '';
            $emailTemplate->htmlContent = '';
            foreach($data as $child)
            {
                if (isset($child['dataValue']) && $child['dataValue'])
                {
                    $emailTemplate->textContent .= ' ' . $child['dataValue'];
                    $emailTemplate->htmlContent .= ' ' . $child['dataValue'];
                }
            }
            $validated                      = $emailTemplate->validate(null, false, true);
            $this->assertTrue($validated);
            $this->assertEmpty($emailTemplate->getErrors());
            $this->assertTrue($emailTemplate->save());
            
            $data = $reportToTreeAdapter->getData('EmailTemplate_owner___primaryEmail');
            $emailTemplate->textContent = '';
            $emailTemplate->htmlContent = '';
            foreach($data as $child)
            {
                if (isset($child['dataValue']) && $child['dataValue'])
                {
                    $emailTemplate->textContent .= ' ' . $child['dataValue'];
                    $emailTemplate->htmlContent .= ' ' . $child['dataValue'];
                }
            }
            $validated                      = $emailTemplate->validate(null, false, true);
            $this->assertTrue($validated);
            $this->assertEmpty($emailTemplate->getErrors());
            $this->assertTrue($emailTemplate->save());
            
            $data = $reportToTreeAdapter->getData('EmailTemplate_primaryAddress');
            $emailTemplate->textContent = '';
            $emailTemplate->htmlContent = '';
            foreach($data as $child)
            {
                if (isset($child['dataValue']) && $child['dataValue'])
                {
                    $emailTemplate->textContent .= ' ' . $child['dataValue'];
                    $emailTemplate->htmlContent .= ' ' . $child['dataValue'];
                }
            }
            $validated                      = $emailTemplate->validate(null, false, true);
            $this->assertTrue($validated);
            $this->assertEmpty($emailTemplate->getErrors());
            $this->assertTrue($emailTemplate->save());
            
            // Test against invalid Merge Tags
            $emailTemplate->textContent     = 'Text Content [[TEXT__INVALID^MERGE^TAG]]';
            $emailTemplate->htmlContent     = 'Html Content [[HTMLINVALIDMERGETAG]]';
            $validated                      = $emailTemplate->validate(null, false, true);
            $this->assertFalse($validated);
            $errorMessages = $emailTemplate->getErrors();
            $this->assertEquals(2, count($errorMessages));
            $this->assertTrue(array_key_exists('textContent', $errorMessages));
            $this->assertTrue(array_key_exists('htmlContent', $errorMessages));
            $this->assertEquals(1, count($errorMessages['textContent']));
            $this->assertEquals(1, count($errorMessages['htmlContent']));
            $this->assertContains('TEXT__INVALID^MERGE^TAG', $errorMessages['textContent'][0]);
            $this->assertContains('HTMLINVALIDMERGETAG', $errorMessages['htmlContent'][0]);
        }   
    }
?>
