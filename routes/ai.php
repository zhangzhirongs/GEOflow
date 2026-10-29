<?php

use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;

// The local `boost:mcp` command remains the default. Enable this explicitly
// in production before exposing the same server to remote MCP clients.
if (config('geoflow.mcp_http_enabled', false)) {
    Mcp::web('/api/v1/mcp', Boost::class)
        ->middleware(['api.request_id', 'api.auth', 'api.scope:mcp:use']);
}
