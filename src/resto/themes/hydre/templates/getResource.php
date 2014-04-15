<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><title>RESTo framework</title>
        <link rel="stylesheet" href="<?php echo $this->request["restoRootUrl"] ?>/css/default.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo $this->request["restoRootUrl"] ?>/js/lib/jquery.fancybox.css" type="text/css" />
        <link rel="search" type="application/opensearchdescription+xml" href="<?php echo $this->request["baseUrl"] ?>_describe" title="Search" />
        <!-- IE Fallbacks -->
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
    </head>
    <body>
        TODO for single product
        <!--<div id="bg"><div><table cellpadding="0" cellspacing="0"><tr><td><img alt="" src="<?php echo $this->request["restoRootUrl"] ?>/img/bg.png" /></td></tr></table></div></div>-->
        <script type="text/javascript" src="<?php echo $this->request["restoRootUrl"] ?>/js/lib/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->request["restoRootUrl"] ?>/js/lib/jquery.fancybox.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                $('a.quicklook').fancybox();
            });
        </script>
    </body>
</html>
