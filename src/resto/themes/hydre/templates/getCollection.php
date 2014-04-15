<!DOCTYPE html>
<?php
$collectionUrl = $this->request['restoUrl'] . $this->request['collection'] . '/';
$templateName = 'hydre';
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title><?php echo strip_tags($this->R->getTitle()); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
        <link rel="shortcut icon" href="<?php echo $this->request['restoUrl'] ?>/favicon.ico" />
        <!-- mapshup : start -->
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/externals/mol/theme/default/style.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.css" />
        <link rel="stylesheet" type="text/css" href="<?php echo $this->request['restoUrl'] ?>/js/externals/mapshup/theme/default/mapshup.css" />
        <!-- mapshup : end -->
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/foundation/foundation.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/swipebox/css/swipebox.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/js/externals/fontawesome/css/font-awesome.min.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/style.css" type="text/css" />
        <link rel="search" type="application/opensearchdescription+xml" href="<?php echo $collectionUrl ?>$describe" hreflang="<?php echo $this->request['language'] ?>" title="<?php echo $this->description['name']; ?>" />
        <!--[if lt IE 9]>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/modernizr/modernizr.min.js"></script>
        <![endif]-->
    </head>
    <body>

        <header>
            <span id="logo"><a title="<?php echo $this->description['dictionary']->translate('_home'); ?>" href="<?php echo $this->request['restoUrl'] ?>">RESTo</a> | <?php echo $this->description['os']['ShortName']; ?></span>
            <nav>
                <a href="#" id="pull"></a>
                <!--
                <ul>
                    <li title="<?php echo $this->description['dictionary']->translate('_shareOn', 'Facebook'); ?>" class="fa fa-facebook link"></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_shareOn', 'Twitter'); ?>" class="fa fa-twitter link"></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_viewAtomFeed'); ?>" class="fa fa-rss link"></li>
                    <li></li>
                    <li title="<?php echo $this->description['dictionary']->translate('_viewCart'); ?>" class="fa fa-shopping-cart   link"></li>
                </ul>
                -->
            </nav>
	</header>

        <!-- mapshup display -->
        <div id="mapshup" class="noResizeHeight fixed"></div>
        <div id="mapshup-tools" class="fixed"></div>
        <div class="row mapshup-block-fixed">
            <div class="large-12 columns"></div>
        </div>
        <div class="row mobile-block-fixed">
            <div class="large-12 columns"></div>
        </div>

        <!-- Search bar -->
        <div class="resto-search fixed">
            <form id="resto-searchform" action="<?php echo $collectionUrl ?>">
                <input type="hidden" name="format" value="html" />
                <?php
                if ($this->request['language']) {
                    echo '<input type="hidden" name="' . $this->description['searchFiltersDescription']['language']['osKey'] . '" value="' . $this->request['language'] . '" />';
                }
                ?>
                <input type="text" class="clearable" id="search" name="<?php echo $this->description['searchFiltersDescription']['searchTerms']['osKey'] ?>" value="<?php echo str_replace('"', '&quot;', stripslashes($this->request['params'][$this->description['searchFiltersDescription']['searchTerms']['osKey']])); ?>" placeholder="<?php echo $this->description['dictionary']->translate('_placeHolder', $this->description['os']['Query']); ?>"/>
            </form>
        </div>

        <!-- query analyze result -->
        <?php if ($this->request['special']['_showQuery']) { ?>
        <div class="resto-queryanalyze fixed"></div>
        <?php } ?>

        <!-- Collection title and description -->
        <!--
        <div class="row">
            <div class="large-12 columns">
                <h1><?php echo $this->description['os']['ShortName']; ?></h1>
                <div class="resto-description">
                    <?php echo $this->description['os']['Description']; ?>
                </div>
            </div>
        </div>
        -->


        <!-- Search result -->
        <div class="row">
            <div class="large-12 columns">
                <ul class="small-block-grid-1 medium-block-grid-3 large-block-grid-4 resto-content center"></ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="row">
            <div class="small-12 columns">
                <div class="footer">
                    Powered by <a href="http://github.com/jjrom/resto">RESTo</a>, <a href="http://github.com/jjrom/itag">iTag</a> and <a href="http://mapshup.info">mapshup</a>
                </div>
            </div>
        </div>

        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mjquery/mjquery.ui.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/swipebox/js/jquery.swipebox.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/history/jquery.history.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/resto.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/theme.js"></script>
        <!-- mapshup : start -->
        <script type="text/javascript" src="http://maps.google.com/maps/api/js?v=3.14&sensor=false"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mol/OpenLayers.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mapshup/mapshup.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/js/externals/mapshup/config/default.js"></script>
        <script type="text/javascript" src="<?php echo $this->request['restoUrl'] ?>/themes/<?php echo $templateName ?>/config.js"></script>
        <!-- mapshup : end -->
        <script type="text/javascript">
            $(document).ready(function() {

                /*
                 * Initialize mapshup
                 */
                if (M) {
                    M.load();
                }

                /*
                 * Initialize RESTo
                 */
                R.init({
                    language: '<?php echo $this->request['language']; ?>',
                    data:<?php echo json_encode($this->response) ?>,
                    translation:<?php echo json_encode($this->description['dictionary']->getTranslation()) ?>,
                    restoUrl: '<?php echo $this->request['restoUrl'] ?>'
                });

            });
        </script>
    </body>
</html>
