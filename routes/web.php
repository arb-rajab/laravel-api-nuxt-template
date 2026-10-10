<?php

// This is a decoupled API-only backend: there is no server-rendered web
// frontend. The file exists because bootstrap/app.php's withRouting() call
// needs it, and intentionally carries no routes. The Nuxt app in frontend/
// is a separate application. See routes/api.php for the real endpoints.
