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

    /**
     * Regresión: un merge anterior mergeó las 4 tools de educación y
     * recommended_reason/category_gaps sin que las instructions() del agente
     * se enteraran -- el agente no sabía que debía usarlas ni cómo narrar
     * recommended_reason. Este test evita que se repita en silencio.
     */
    public function test_instructions_mention_the_education_tools_and_how_to_narrate_recommendations(): void
    {
        $instructions = (string) (new BanorteMcpAgent(Mockery::mock(Client::class)))->instructions();

        $this->assertStringContainsString('get_educational_topic', $instructions);
        $this->assertStringContainsString('get_learning_path', $instructions);
        $this->assertStringContainsString('get_learning_progress', $instructions);
        $this->assertStringContainsString('mark_topic_completed', $instructions);
        $this->assertStringContainsString('recommended_reason', $instructions);
        $this->assertStringContainsString('category_gaps', $instructions);
    }

    public function test_tools_spreads_the_mcp_clients_tools(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('tools')->once()->andReturn(collect(['a', 'b']));

        $agent = new BanorteMcpAgent($client);

        $this->assertSame(['a', 'b'], $agent->tools());
    }
}
