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
     * Reports module walkthrough tests for super users.
     */
    class ReportsSuperUserWalkthroughTest extends ZurmoWalkthroughBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;


            //Setup test data owned by the super user.
            $account = AccountTestHelper::createAccountByNameForOwner('superAccount', $super);
            AccountTestHelper::createAccountByNameForOwner('superAccount2', $super);
            ContactTestHelper::createContactWithAccountByNameForOwner('superContact', $super, $account);
        }

        public static function makeRowsAndColumnsReportPostDataForAccounts()
        {
            return array(
                'validationScenario' => 'ValidateForDisplayAttributes',
                'RowsAndColumnsReportWizardForm' => array(
                    'moduleClassName' => 'AccountsModule',
                    'Filters' => array(
                        '0' => array(
                            'structurePosition' => 1,
                            'attributeIndexOrDerivedType' => 'name',
                            'operator' => 'isNotNull',
                            'value' => '',
                            'availableAtRunTime' => '0')),
                    'filtersStructure' => '1',
                    'displayAttributes' => '',
                    'DisplayAttributes' => array(
                        '0' => array(
                            'attributeIndexOrDerivedType' => 'name',
                            'label' => 'Name')),

                    'name' => 'some rows and columns report for accounts',
                    'description' => 'some rows and columns report description for accounts',
                    'currencyConversionType' => '1',
                    'spotConversionCurrencyCode' => '',
                    'ownerId' => Yii::app()->user->userModel->id,
                    'ownerName' => 'Super User',
                    'explicitReadWriteModelPermissions' => array(
                        'type' => '',
                        'nonEveryoneGroup' => '4')),
                'FiltersRowCounter' => '1',
                'DisplayAttributesRowCounter' => '1',
                'OrderBysRowCounter' => '0',
            );
        }

        public static function makeRowsAndColumnsReportPostData()
        {
            return array(
                'validationScenario' => 'ValidateForDisplayAttributes',
                'RowsAndColumnsReportWizardForm' => array(
                    'moduleClassName' => 'ReportsTestModule',
                    'Filters' => array(
                        '0' => array(
                            'structurePosition' => 1,
                            'attributeIndexOrDerivedType' => 'string',
                            'operator' => 'isNotNull',
                            'value' => '',
                            'availableAtRunTime' => '0')),
                    'filtersStructure' => '1',
                    'displayAttributes' => '',
                    'DisplayAttributes' => array(
                        '0' => array(
                            'attributeIndexOrDerivedType' => 'string',
                            'label' => 'String')),

                    'name' => 'some rows and columns report',
                    'description' => 'some rows and columns report description',
                    'currencyConversionType' => '1',
                    'spotConversionCurrencyCode' => '',
                    'ownerId' => Yii::app()->user->userModel->id,
                    'ownerName' => 'Super User',
                    'explicitReadWriteModelPermissions' => array(
                        'type' => '',
                        'nonEveryoneGroup' => '4')),
                'FiltersRowCounter' => '1',
                'DisplayAttributesRowCounter' => '1',
                'OrderBysRowCounter' => '0',
            );
        }

        public static function makeSummationReportPostData()
        {
            return array(
                'validationScenario' => 'ValidateForDisplayAttributes',
                'SummationReportWizardForm' => array(
                    'moduleClassName' => 'ReportsTestModule',
                    'Filters' => array(
                        '0' => array(
                            'structurePosition' => 1,
                            'attributeIndexOrDerivedType' => 'string',
                            'operator' => 'isNotNull',
                            'value' => '',
                            'availableAtRunTime' => '0')),
                    'filtersStructure' => '1',
                    'displayAttributes' => '',
                    'DisplayAttributes' => array(
                        '0' => array(
                            'attributeIndexOrDerivedType' => 'string',
                            'label' => 'Name')),

                    'name' => 'some summation report',
                    'description' => 'some summation report description',
                    'currencyConversionType' => '1',
                    'spotConversionCurrencyCode' => '',
                    'ownerId' => Yii::app()->user->userModel->id,
                    'ownerName' => 'Super User',
                    'explicitReadWriteModelPermissions' => array(
                        'type' => '',
                        'nonEveryoneGroup' => '4')),
                'FiltersRowCounter' => '1',
                'DisplayAttributesRowCounter' => '1',
                'OrderBysRowCounter' => '0',
            );
        }

        public static function getDependentTestModelClassNames()
        {
            return array('ReportModelTestItem', 'ReportModelTestItem2');
        }

        public function setUp()
        {
            parent::setUp();
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
        }

        public function testSuperUserAllDefaultControllerActions()
        {
            $this->runControllerWithNoExceptionsAndGetContent      ('reports/default/list');
            $this->runControllerWithExitExceptionAndGetContent     ('reports/default/create');
            $this->runControllerWithNoExceptionsAndGetContent      ('reports/default/selectType');
        }

        /**
         * @depends testSuperUserAllDefaultControllerActions
         */
        public function testCreateActionForRowsAndColumns()
        {
            $savedReports = SavedReport::getAll();
            $this->assertEquals(0, count($savedReports));
            $content = $this->runControllerWithExitExceptionAndGetContent     ('reports/default/create');
            $this->assertContains('Rows and Columns Report', $content);
            $this->assertContains('Summation Report', $content);
            $this->assertContains('Matrix Report', $content);

            $this->setGetArray(array('type' => 'RowsAndColumns'));
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent     ('reports/default/create');
            $this->assertContains('Accounts', $content);

            $this->setGetArray(array('type' => 'RowsAndColumns'));
            $postData = static::makeRowsAndColumnsReportPostData();
            $postData['save'] = 'save';
            $postData['ajax'] = 'edit-form';
            $this->setPostArray($postData);
            $content = $this->runControllerWithExitExceptionAndGetContent('reports/default/save');
            $this->assertEquals('[]', $content);
            $postData = static::makeRowsAndColumnsReportPostData();
            $postData['save'] = 'save';
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('reports/default/save');
            $savedReports = SavedReport::getAll();
            $this->assertEquals(1, count($savedReports));
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('reports/default/details');
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('reports/default/edit');
            //Save an existing report
            $this->setGetArray(array('type' => 'RowsAndColumns', 'id' => $savedReports[0]->id));
            $postData = static::makeRowsAndColumnsReportPostData();
            $postData['save'] = 'save';
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('reports/default/save');
            $this->assertEquals(1, count($savedReports));
            //Clone existing report
            $this->setGetArray(array('type' => 'RowsAndColumns', 'id' => $savedReports[0]->id, 'isBeingCopied' => '1'));
            $postData = static::makeRowsAndColumnsReportPostData();
            $postData['save'] = 'save';
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('reports/default/save');
            $savedReports     = SavedReport::getAll();
            $this->assertEquals(2, count($savedReports));
        }

        /**
         * @depends testCreateActionForRowsAndColumns
         */
        public function testExportAction()
        {
            $notificationsBeforeCount        = Notification::getCount();
            $notificationMessagesBeforeCount = NotificationMessage::getCount();

            $savedReports = SavedReport::getAll();
            $this->assertEquals(2, count($savedReports));
            $this->setGetArray(array('id' => $savedReports[0]->id));
            //Test where there is no data to export
            $this->runControllerWithRedirectExceptionAndGetContent('reports/default/export');
            $this->assertContains('There is no data to export.',
                Yii::app()->user->getFlash('notification'));

            $reportModelTestItem            = new ReportModelTestItem();
            $reportModelTestItem->string    = 'string1';
            $reportModelTestItem->lastName  = 'xLast1';
            $this->assertTrue($reportModelTestItem->save());

            $reportModelTestItem            = new ReportModelTestItem();
            $reportModelTestItem->string    = 'string2';
            $reportModelTestItem->lastName  = 'xLast2';
            $this->assertTrue($reportModelTestItem->save());

            $content = $this->runControllerWithExitExceptionAndGetContent('reports/default/export');
            $this->assertEquals('Testing download.', $content);

            ExportModule::$asynchronousThreshold = 1;
            $this->runControllerWithRedirectExceptionAndGetUrl('reports/default/export');

            // Start background job
            $job = new ExportJob();
            $this->assertTrue($job->run());

            $exportItems = ExportItem::getAll();
            $this->assertEquals(1, count($exportItems));
            $fileModel = $exportItems[0]->exportFileModel;
            $this->assertEquals(1, $exportItems[0]->isCompleted);
            $this->assertEquals('csv', $exportItems[0]->exportFileType);
            $this->assertEquals('reports', $exportItems[0]->exportFileName);
            $this->assertTrue($fileModel instanceOf ExportFileModel);

            $this->assertEquals($notificationsBeforeCount + 1, Notification::getCount());
            $this->assertEquals($notificationMessagesBeforeCount + 1, NotificationMessage::getCount());
        }

        /**
         * @depends testExportAction
         */
        public function testActionRelationsAndAttributesTree()
        {
            $this->setGetArray(array('type' => 'RowsAndColumns', 'treeType' => ComponentForReportForm::TYPE_FILTERS));
            $postData = static::makeRowsAndColumnsReportPostData();
            $this->setPostArray($postData);
            $content = $this->runControllerWithNoExceptionsAndGetContent('reports/default/relationsAndAttributesTree');
            $this->assertContains('<div class="ReportRelationsAndAttributesTreeView', $content);
            //With node id
            $this->setGetArray(array('type'     => 'RowsAndColumns', 'treeType' => ComponentForReportForm::TYPE_FILTERS,
                                     'nodeId'   => 'Filters_hasOne'));
            $postData = static::makeRowsAndColumnsReportPostData();
            $this->setPostArray($postData);
            $content = $this->runControllerWithExitExceptionAndGetContent('reports/default/relationsAndAttributesTree');
            $this->assertContains('{"id":"Filters_hasOne___createdByUser__User",', $content); // Not Coding Standard
        }

        /**
         * @depends testActionRelationsAndAttributesTree
         */
        public function testActionAddAttributeFromTree()
        {
            $this->setGetArray(array('type'      => 'RowsAndColumns',
                                     'treeType'  => ComponentForReportForm::TYPE_FILTERS,
                                     'nodeId'    => 'Filters_phone',
                                     'rowNumber' => 4));
            $postData = static::makeRowsAndColumnsReportPostData();
            $this->setPostArray($postData);
            $content = $this->runControllerWithNoExceptionsAndGetContent('reports/default/addAttributeFromTree');
            $this->assertContains('<option value="equals">Equals</option>', $content);
        }

        /**
         * @depends testActionAddAttributeFromTree
         */
        public function testGetAvailableSeriesAndRangesForChart()
        {
            $this->setGetArray(array('type'      => 'Summation'));
            $postData = static::makeSummationReportPostData();
            $this->setPostArray($postData);
            $content = $this->runControllerWithNoExceptionsAndGetContent('reports/default/getAvailableSeriesAndRangesForChart');
            $this->assertContains('{"firstSeriesDataAndLabels":{"":"(None)"},"firstRangeDataAndLabels":', $content); // Not Coding Standard
        }

        /**
         * @depends testGetAvailableSeriesAndRangesForChart
         */
        public function testApplyAndResetRuntimeFilters()
        {
            $savedReports = SavedReport::getAll();
            $this->assertEquals(2, count($savedReports));
            $this->setGetArray(array('id' => $savedReports[0]->id));
            //validate filters, where it doesn't validate, the value is missing
            $this->setPostArray(array('RowsAndColumnsReportWizardForm' => array('Filters' => array(
                                       array('attributeIndexOrDerivedType' => 'string',
                                             'operator'                    => 'equals'))),
                                      'ajax' => 'edit-form'));
            $this->runControllerWithExitExceptionAndGetContent('reports/default/applyRuntimeFilters');
            //apply filters
            $this->setPostArray(array('RowsAndColumnsReportWizardForm' => array('Filters' => array(
                                        array('attributeIndexOrDerivedType' => 'string',
                                              'operator'                    => 'equals',
                                              'value'                       => 'text')))));
            $this->runControllerWithNoExceptionsAndGetContent('reports/default/applyRuntimeFilters', true);
            //Reset filters
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $this->resetPostArray();
            $this->runControllerWithNoExceptionsAndGetContent('reports/default/resetRuntimeFilters', true);
        }

        /**
         * @depends testApplyAndResetRuntimeFilters
         */
        public function testDrillDownDetails()
        {
            $savedReport = SavedReportTestHelper::makeSummationWithDrillDownReport();
            $this->setGetArray(array('id'                         => $savedReport->id,
                                     'rowId'                      => 2,
                                     'runReport'                  => true,
                                     'groupByRowValueowner__User' => Yii::app()->user->userModel->id));
            $postData = static::makeSummationReportPostData();
            $this->setPostArray($postData);
            $content = $this->runControllerWithNoExceptionsAndGetContent('reports/default/drillDownDetails');
            $this->assertContains('<th id="report-results-grid-view2_c2">Currency Value</th>', $content);
            $this->assertContains('No results found', $content);

            //Check drillDown works with runtime filters
            $this->setPostArray(array('SummationReportWizardForm' => array('Filters' => array(
                array('attributeIndexOrDerivedType' => 'string',
                    'operator'                    => OperatorRules::TYPE_EQUALS,
                    'value'                       => 'string1')))));
            $this->runControllerWithNoExceptionsAndGetContent('reports/default/applyRuntimeFilters', true);
            $content = $this->runControllerWithNoExceptionsAndGetContent('reports/default/drillDownDetails');
            $this->assertContains('<th id="report-results-grid-view2_c2">Currency Value</th>', $content);
            $this->assertContains('1 result(s)', $content);
        }

        /**
         * @depends testDrillDownDetails
         */
        public function testAutoComplete()
        {
            $this->setGetArray(array('term'            => 'a test',
                                     'moduleClassName' => 'ReportsModule',
                                     'type'            => Report::TYPE_SUMMATION));
            $content = $this->runControllerWithNoExceptionsAndGetContent('reports/default/autoComplete');
            $this->assertEquals('[]', $content);
        }

        /**
         * @depends testAutoComplete
         */
        public function testDelete()
        {
            $savedReports = SavedReport::getAll();
            $this->assertEquals(3, count($savedReports));
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $this->runControllerWithRedirectExceptionAndGetContent('reports/default/delete');
            $savedReports = SavedReport::getAll();
            $this->assertEquals(2, count($savedReports));
        }

        public function testCloneSetsRightPermissions()
        {
            // create users and groups.
            $everyoneGroup              = Group::getByName(Group::EVERYONE_GROUP_NAME);
            $everyoneGroup->setRight('ReportsModule', ReportsModule::getAccessRight());
            $everyoneGroup->setRight('AccountsModule', AccountsModule::RIGHT_ACCESS_ACCOUNTS);
            $this->assertTrue($everyoneGroup->save());
            $everyoneGroup->forgetAll();

            $jim                        = UserTestHelper::createBasicUser('jim');
            $john                       = UserTestHelper::createBasicUser('john');
            $johnGroup                  = new Group();
            $johnGroup->name            = 'John Group';
            $johnGroup->users->add($john);
            $this->assertTrue($johnGroup->save());
            $john->forgetAll();
            $johnGroup->forgetAll();
            $john                       = User::getByUsername('john');
            $johnGroup                  = Group::getByName('John Group');

            $basePostData               = static::makeRowsAndColumnsReportPostDataForAccounts();
            $basePostData['save']       = 'save';
            $description                = StringUtil::generateRandomString(40);

            // create a report with everyone group permission, ensure everyone can access it.
            // clone it, ensure everyone group can still access it.
            $super                      = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            SavedReport::deleteAll();
            $modelSpecificPostData      = array('RowsAndColumnsReportWizardForm' => array(
                        'name'          => 'Everyone Report 01',
                        'description'   => $description,
                        'ownerId'       => $super->id,
                        'ownerName'     => strval($super),
                        'explicitReadWriteModelPermissions' => array(
                            'type'              => ExplicitReadWriteModelPermissionsUtil::MIXED_TYPE_EVERYONE_GROUP,
                            'nonEveryoneGroup'  => null,
                        )
                    ),
                );
            $postData               = CMap::mergeArray($basePostData, $modelSpecificPostData);
            $this->setGetArray(array('type' => 'RowsAndColumns'));
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('/reports/default/save');
            ForgetAllCacheUtil::forgetAllCaches();
            $savedReports                       = SavedReport::getAll();
            $this->assertCount(1, $savedReports);
            $this->assertEquals(Permission::ALL, $savedReports[0]->getEffectivePermissions($super));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[0]->getEffectivePermissions($everyoneGroup));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[0]->getEffectivePermissions($jim));

            // ensure jim can access it.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('jim');
            $this->resetPostArray();
            $this->resetGetArray();
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/list');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 1);

            $this->setGetArray(array('id' => $savedReports[0]->id));
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/details');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 3);

            // clone the report.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $this->setGetArray(array('type' => 'RowsAndColumns', 'id' => $savedReports[0]->id, 'isBeingCopied' => '1'));
            $postData['RowsAndColumnsReportWizardForm']['name'] = 'Cloned ' . $postData['RowsAndColumnsReportWizardForm']['name'];
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('/reports/default/save');
            ForgetAllCacheUtil::forgetAllCaches();
            $savedReports               = SavedReport::getAll();
            $this->assertCount(2, $savedReports);
            $sourcePermissions          = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($savedReports[0]);
            $clonePermissions           = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($savedReports[1]);
            $this->assertEquals($sourcePermissions, $clonePermissions);
            $this->assertEquals(Permission::ALL, $savedReports[1]->getEffectivePermissions($super));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[1]->getEffectivePermissions($everyoneGroup));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[1]->getEffectivePermissions($jim));

            // ensure jim can access it.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('jim');
            $this->resetPostArray();
            $this->resetGetArray();
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/list');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 1);

            $this->setGetArray(array('id' => $savedReports[1]->id));
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/details');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 3);

            // create a report with specific group permissions, ensure only members of that group can access it.
            // clone it, ensure only that specific group members can access it.
            $super                      = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            SavedReport::deleteAll();
            $modelSpecificPostData      = array('RowsAndColumnsReportWizardForm' => array(
                        'name'          => 'Group Specific Report 01',
                        'description'   => $description,
                        'ownerId'       => $super->id,
                        'ownerName'     => strval($super),
                        'explicitReadWriteModelPermissions' => array(
                            'type'              => ExplicitReadWriteModelPermissionsUtil::MIXED_TYPE_NONEVERYONE_GROUP,
                            'nonEveryoneGroup'  => $johnGroup->id,
                        )
                    ),
                );

            $postData               = CMap::mergeArray($basePostData, $modelSpecificPostData);
            $this->setGetArray(array('type' => 'RowsAndColumns'));
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('/reports/default/save');
            ForgetAllCacheUtil::forgetAllCaches();
            $savedReports                       = SavedReport::getAll();
            $this->assertCount(1, $savedReports);
            $this->assertEquals(Permission::ALL, $savedReports[0]->getEffectivePermissions($super));
            $this->assertEquals(Permission::NONE, $savedReports[0]->getEffectivePermissions($everyoneGroup));
            $this->assertEquals(Permission::NONE, $savedReports[0]->getEffectivePermissions($jim));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[0]->getEffectivePermissions($johnGroup));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[0]->getEffectivePermissions($john));

            // ensure john can access it.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('john');
            $this->resetPostArray();
            $this->resetGetArray();
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/list');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 1);
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/details');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 3);

            // ensure jim can not access it.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('jim');
            $this->resetGetArray();
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/list');
            $this->assertNotContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $this->runControllerWithAccessDeniedSecurityExceptionAndGetContent('/reports/default/details');

            // clone the report.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $this->setGetArray(array('type' => 'RowsAndColumns', 'id' => $savedReports[0]->id, 'isBeingCopied' => '1'));
            $postData['RowsAndColumnsReportWizardForm']['name'] = 'Cloned ' . $postData['RowsAndColumnsReportWizardForm']['name'];
            $this->setPostArray($postData);
            $this->runControllerWithExitExceptionAndGetContent('/reports/default/save');
            ForgetAllCacheUtil::forgetAllCaches();
            $savedReports               = SavedReport::getAll();
            $this->assertCount(2, $savedReports);
            $sourcePermissions          = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($savedReports[0]);
            $clonePermissions           = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($savedReports[1]);
            $this->assertEquals($sourcePermissions, $clonePermissions);
            $this->assertEquals(Permission::ALL, $savedReports[1]->getEffectivePermissions($super));
            $this->assertEquals(Permission::NONE, $savedReports[1]->getEffectivePermissions($everyoneGroup));
            $this->assertEquals(Permission::NONE, $savedReports[1]->getEffectivePermissions($jim));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[1]->getEffectivePermissions($johnGroup));
            $this->assertEquals(Permission::READ_WRITE_CHANGE_PERMISSIONS_CHANGE_OWNER, $savedReports[1]->getEffectivePermissions($john));

            // ensure john can access it.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('john');
            $this->resetPostArray();
            $this->resetGetArray();
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/list');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 1);

            $this->setGetArray(array('id' => $savedReports[1]->id));
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/details');
            $this->assertContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->assertEquals(substr_count($content, $postData['RowsAndColumnsReportWizardForm']['name']), 3);

            // ensure jim can not access it.
            $this->logoutCurrentUserLoginNewUserAndGetByUsername('jim');
            $this->resetGetArray();
            $content        = $this->runControllerWithNoExceptionsAndGetContent('/reports/default/list');
            $this->assertNotContains($postData['RowsAndColumnsReportWizardForm']['name'], $content);
            $this->setGetArray(array('id' => $savedReports[0]->id));
            $this->runControllerWithAccessDeniedSecurityExceptionAndGetContent('/reports/default/details');
        }

        //todo: test saving a report and changing owner so you don't have permissions anymore. it should do a flashbar and redirect you to the list view.
        //todo: test details view comes up ok when user cant delete or edit report, make sure options button doesn't blow up since it shouldn't display
    }
?>