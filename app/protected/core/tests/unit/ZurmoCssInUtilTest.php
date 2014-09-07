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

    class ZurmoCssInUtilTest extends BaseTest
    {
        public function testSplitMediaQueries()
        {
            $css = "body.outlook p {display: inline /*!important*/;}
                    @media screen {body {width: 75%;}}
                    table.round td {-webkit-border-radius: 500px;-moz-border-radius: 500px;border-radius: 500px}";
            $mediaQuery = ZurmoCssInUtil::splitMediaQueries($css);
            $this->assertEquals(" @media screen {body {width: 75%;}}\n", $mediaQuery[1]);

            $css = "body.outlook p {display: inline /*!important*/;}
                    @media screen {body {width: 75%;}}\n";
            $mediaQuery = ZurmoCssInUtil::splitMediaQueries($css);
            $this->assertEquals(" @media screen {body {width: 75%;}}\n", $mediaQuery[1]);

            $css = "body.outlook p {display: inline /*!important*/;}
                    @media screen {body {width: 75%;}}";
            $mediaQuery = ZurmoCssInUtil::splitMediaQueries($css);
            $this->assertEquals(" @media screen {body {width: 75%;}}\n", $mediaQuery[1]);

            $css = "body.outlook p {display: inline /*!important*/;} @media screen {body {width: 75%;}}";
            $mediaQuery = ZurmoCssInUtil::splitMediaQueries($css);
            $this->assertEquals(" @media screen {body {width: 75%;}}\n", $mediaQuery[1]);

            $css = "@media screen {body {width: 75%;}}";
            $mediaQuery = ZurmoCssInUtil::splitMediaQueries($css);
            $this->assertEquals(" @media screen {body {width: 75%;}}\n", $mediaQuery[1]);

            $css = "@media {}";
            $mediaQuery = ZurmoCssInUtil::splitMediaQueries($css);
            $this->assertEquals(" @media {}\n", $mediaQuery[1]);
        }

        public function testReferencedInvalidCSSFilePathsAndUrls()
        {
            // test for invalid file paths
            // test for 404 urls
            $htmlContent    = <<<CSS
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <base href="http://www.zurmo.com/images/" target="_blank">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Zurmo CRM &mdash; Gamified, Social, Intuitive Customer Relationship Management</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">
        <meta name="google-site-verification" content="RAKz9Hv052t8VptkOvCyFCryGJ72VRfVVnSR-ZhkhFo" />
        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
        <link rel="shortcut icon" href="icons/favicon.ico">
        <link href="//fonts.googleapis.com/css?family=Lato:100,400,700,400italic" rel="stylesheet" type="text/css">
        <link href="//fonts.googleapis.com/css?family=Arvo:400,400italic" rel="stylesheet" type="text/css">
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.2.min.js"><\/script>')</script>
        <!--[if lt IE 9]><script src="js/vendor/html5shiv.js"></script><![endif]-->
        <link rel="stylesheet" type="text/css" href="../fusion.css">
        <link rel="stylesheet" type="text/css" href="/../style.css">
        <link href="css/zurmo.css" rel="stylesheet"  media="screen" type="text/css">
        <link href="http://www.zurmo.com/css/zurmo.css" rel="stylesheet"  media="screen" type="text/css">
        <link href="http://www.zurmo.com/css/zurmo-invalid.css" rel="stylesheet"  media="screen" type="text/css">
        <link rel="EditURI" type="application/rsd+xml" title="RSD" href="http://zurmo.com/wordpress/xmlrpc.php?rsd" />
        <link rel="wlwmanifest" type="application/wlwmanifest+xml" href="http://zurmo.com/wordpress/wp-includes/wlwmanifest.xml" />
        <meta name="generator" content="WordPress 3.8.4" />
        <script type="text/javascript">
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			  ga('create', 'UA-38681687-1', 'auto');
			  ga('send', 'pageview');
		</script>
    </head>
    <body id="home" class="com">
    </body>
</html>
CSS;

            $cssInUtil = new ZurmoCssInUtil();
            $cssInUtil->setMoveStyleBlocksToBody();
            $convertedhtmlContent = $cssInUtil->inlineCSS(null, $htmlContent);
        }
    }
?>