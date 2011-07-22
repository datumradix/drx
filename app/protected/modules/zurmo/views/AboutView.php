<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2011 Zurmo Inc.
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

    class AboutView extends View
    {
        protected function renderContent()
        {
            $zurmoVersion    = '???';
            $changeSet      = '??????????';
            $yiiVersion     = YiiBase::getVersion();
            if (method_exists('R', 'getVersion'))
            {
                $redBeanVersion =  R::getVersion();
            }
            else
            {
                $redBeanVersion = '&lt; 1.2.9.1';
            }
            // TODO - put the strings in Yii::t().
            return <<<END
    <p>
        This is <b>version $zurmoVersion</b> ($changeSet), of <b>Zurmo</b>.
    </p>
    <p>
        <b>Zurmo</b> is a <b>Customer Relation Management</b> system by <b>Zurmo Inc.</b>
    </p>
    <p>
        Visit <b>Zurmo Inc.</b> online at
        <a href="http://www.zurmoinc.com">http://www.zurmoinc.com</a>.<br />
    </p>
    <p>
        Insert licensing information.
    </p>
    <p>
        <b>Zurmo</b> uses the following great Open Source tools and frameworks...
    </p>
    <p>
        <a href="http://www.yiiframework.com">The Yii Framework</a> (version $yiiVersion is installed)<br />
        <a href="http://www.redbeanphp.com">RedBean Php ORM</a>     (version $redBeanVersion is installed)<br />
        <a href="http://www.jquery.com">The JQuery Javascript Framework</a> (installed with Yii)<br />
    </p>
END;
        }
    }
?>
