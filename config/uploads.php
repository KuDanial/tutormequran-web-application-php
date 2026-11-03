<?php
// Configure an absolute directory OUTSIDE the web document root in deployment.
return getenv('TMQ_UPLOAD_ROOT') ?: dirname(__DIR__, 2) . '/tutormequran-private-storage';
