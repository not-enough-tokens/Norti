<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\BanorteMcpAgent;
use Laravel\Mcp\Client;
use Mockery;
use PHPUnit\Framework\TestCase;

class BanorteMcpAgentTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_instructions_describe_the_agents_purpose(): void
    {
        $agent = new BanorteMcpAgent(Mockery::mock(Client::class));

        $this->assertStringContainsString('Banorte', (string) $agent->instructions());
    }

    public function test_tools_spreads_the_mcp_clients_tools(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('tools')->once()->andReturn(collect(['a', 'b']));

        $agent = new BanorteMcpAgent($client);

        $this->assertSame(['a', 'b'], $agent->tools());
    }
}
