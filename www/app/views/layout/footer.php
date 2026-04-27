</div>

    <!-- Scripts -->
    <script src="/libs/jquery/jquery/dist/jquery.js"></script>
    <script>
      // CSRF: inyecta el token en cada AJAX (jQuery + fetch). El backend
      // espera el token en el header X-CSRF-Token o en el campo _csrf del POST.
      (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) return;
        var token = meta.getAttribute('content');
        window.SMARTLABS_CSRF = token;

        if (window.jQuery) {
          jQuery.ajaxSetup({
            beforeSend: function (xhr) {
              xhr.setRequestHeader('X-CSRF-Token', token);
            }
          });
        }

        if (window.fetch) {
          var origFetch = window.fetch;
          window.fetch = function (input, init) {
            init = init || {};
            var method = (init.method || (typeof input === 'object' && input.method) || 'GET').toUpperCase();
            if (method !== 'GET' && method !== 'HEAD') {
              init.headers = new Headers(init.headers || {});
              if (!init.headers.has('X-CSRF-Token')) {
                init.headers.set('X-CSRF-Token', token);
              }
            }
            return origFetch.call(this, input, init);
          };
        }
      })();
    </script>
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
    <script src="/public/js/session-keepalive.js"></script>
    
    <!-- Connection Watchdog System -->
    <script src="/public/js/connection-watchdog.js"></script>
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