# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtén un token con <code>POST /api/auth/login</code> (email + password). Envíalo como <code>Authorization: Bearer {token}</code> junto con el header <code>X-Tenant-ID: {id}</code> (el id del tenant viene en la respuesta del login).
