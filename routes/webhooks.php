<?php

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Each domain that exposes an inbound webhook registers its routes here
| from its service provider — keeps webhook surface area in one obvious
| place. CSRF is disabled for this route group; controllers MUST verify
| the request signature themselves.
|
| Domain service providers will append routes from their boot() method
| in later phases. Phase 0 keeps this file intentionally empty.
*/
