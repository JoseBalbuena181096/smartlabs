</div>

    <!-- Scripts -->
    <script src="/libs/jquery/jquery/dist/jquery.js"></script>
    <script src="/libs/jquery/tether/dist/js/tether.min.js"></script>
    <script src="/libs/jquery/bootstrap/dist/js/bootstrap.js"></script>
    <script src="/libs/jquery/underscore/underscore-min.js"></script>
    <script src="/libs/jquery/jQuery-Storage-API/jquery.storageapi.min.js"></script>
    <script src="/libs/jquery/PACE/pace.min.js"></script>
    <script src="/html/scripts/config.lazyload.js"></script>
    <script src="/html/scripts/palette.js"></script>
    <script src="/html/scripts/ui-load.js"></script>
    <script src="/html/scripts/ui-jp.js"></script>
    <script src="/html/scripts/ui-include.js"></script>
    <script src="/html/scripts/ui-device.js"></script>
    <script src="/html/scripts/ui-form.js"></script>
    <script src="/html/scripts/ui-nav.js"></script>
    <script src="/html/scripts/ui-screenfull.js"></script>
    <script src="/html/scripts/ui-scroll-to.js"></script>
    <script src="/html/scripts/ui-toggle-class.js"></script>
    <script src="/html/scripts/app.js"></script>
    <script src="/html/scripts/ajax.js"></script>

    <!-- MQTT y AJAX para SMARTLABS -->
    <script src="/public/js/config.js"></script>
    <script src="/public/js/mqtt-client.js"></script>
    <script src="/public/js/loan-mqtt-improved.js"></script>
    <script src="/public/js/smartlabs-ajax.js"></script>

    <!-- Scripts personalizados -->
    <?php if (isset($customScripts)): ?>
        <?php foreach ($customScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($inlineScript)): ?>
        <script>
            <?php echo $inlineScript; ?>
        </script>
    <?php endif; ?>

  </body>
</html>