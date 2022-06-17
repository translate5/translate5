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
    ?>

<!--// TODO add a comment about apache alias -->
    <base href="<?php echo APPLICATION_RUNDIR ?>/editor/plugins/resources/TMMaintenance/">

    <link rel="shortcut icon" href="<?php echo APPLICATION_RUNDIR . $config->runtimeOptions->server->pathToIMAGES; ?>/favicon.ico" type="image/x-icon">

    <!-- The line below must be kept intact for Sencha Cmd to build your application -->
    <script id="microloader" data-app="c6b88c3f-c52d-48ab-b369-15896285f643" type="text/javascript" src="bootstrap.js"></script>
    <script id="microloader" data-app="c6b88c3f-c52d-48ab-b369-15896285f643" type="text/javascript" src="editor.js"></script>

</head>
<body>
</body>
</html>
