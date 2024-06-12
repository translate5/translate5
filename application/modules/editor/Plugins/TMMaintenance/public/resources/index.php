<!DOCTYPE HTML>
<html manifest="">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=10, user-scalable=yes">

    <title>TMMaintenance</title>

    <?php
        /* @var $config Zend_Config */
        $config = Zend_Registry::get('config');
        $rundir = defined('APPLICATION_RUNDIR') ? APPLICATION_RUNDIR : '';
    ?>

    <base href="<?php echo $rundir ?>/editor/plugins/resources/TMMaintenance/">

    <link rel="shortcut icon" href="<?php echo $rundir . $config->runtimeOptions->server->pathToIMAGES; ?>/favicon.ico" type="image/x-icon">

    <script>
        var Editor = {
            data: {
                segments: {
                    fullTagPath: '/modules/editor/images/imageTags/',
                    shortTagPath: '/modules/editor/images/imageTags/',
                }
            },
        };
        window.csrfToken = '<?php echo isset($this) ? $this->csrfToken : ''; ?>';
    </script>

    <script id="microloader" data-app="c6b88c3f-c52d-48ab-b369-15896285f643" type="text/javascript" src="<?php echo $rundir; ?>/modules/editor/js/HtmlEditor/editor.js"></script>
    <!-- The line below must be kept intact for Sencha Cmd to build your application -->
    <script id="microloader" type="text/javascript" src="bootstrap.js"></script>
</head>
<body>
</body>
</html>
