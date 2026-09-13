<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetMarketQuote;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Banorte Server')]
#[Version('0.0.1')]
#[Instructions('Instructions describing how to use the server and its features.')]
class BanorteServer extends Server
{
    protected array $tools = [
        GetMarketQuote::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
