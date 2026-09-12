<?php

use App\Mcp\Servers\BanorteServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/banorte', BanorteServer::class)
    ->middleware(['auth:api']);
