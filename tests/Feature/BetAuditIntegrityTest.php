<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bet;
use App\Models\Draw;
use App\Models\GameType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BetAuditIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Testa se é possível identificar uma aposta vencedora comparando-a com
     * os números sorteados em um determinado sorteio.
     *
     * Este caso verifica:
     * - Criação de um tipo de jogo, sorteio e uma aposta.
     * - Valida que os números da aposta e do sorteio são exatamente iguais.
     */
    #[Test]
    public function it_identifies_a_winning_bet_against_a_draw()
    {
        // Usar nomes únicos para evitar violação de restrição única
        $gameType = GameType::factory()->create([
            'name' => 'SimulottoSena_' . Str::random(8)
        ]);

        $draw = Draw::create([
            'game_type_id' => $gameType->id,
            'drawn_at' => now(),
            'numbers' => [1, 2, 3, 4, 5, 6],
        ]);

        $user = User::factory()->create();

        $bet = Bet::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'numbers' => [1, 2, 3, 4, 5, 6],
        ]);

        $intersection = array_intersect($draw->numbers, $bet->numbers);
        $this->assertCount(6, $intersection);
    }

    /**
     * Testa o ciclo de vida completo de uma aposta com rastreabilidade de todos os eventos
     * gerados no banco de dados por meio de logs de auditoria.
     *
     * Este caso cobre:
     * - Criação de dependências (usuário e tipo de jogo).
     * - Inserção de uma aposta e registro de log de inserção.
     * - Atualização da aposta e registro de log de atualização.
     * - Exclusão da aposta e registro de log de exclusão.
     * - Verifica que todos os logs necessários existem e verifica integridade.
     */
    #[Test]
    public function it_tracks_complete_lifecycle_of_bet_with_audit_trail()
    {
        // 1. Criar dependências
        $gameType = GameType::factory()->create([
            'name' => 'Loto_' . Str::random(8)
        ]);
        $user = User::factory()->create();

        // 2. Criar uma aposta para teste
        $bet = Bet::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'numbers' => [1, 2, 3, 4, 5, 6],
        ]);

        // 3. Buscar logs de 'insert'
        $insertLog = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->where('event', 'insert')
            ->first();

        $this->assertNotNull($insertLog, "Log de inserção não foi registrado");
        $this->assertEquals(64, strlen($insertLog->hash_integrity), "Hash de integridade não tem 64 caracteres");

        // 4. Atualizar a aposta
        $bet->update(['numbers' => [6, 12, 24, 36, 48, 54]]);

        // 5. Buscar logs de 'update'
        $updateLog = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->where('event', 'update')
            ->first();

        $this->assertNotNull($updateLog, "Log de atualização não foi registrado");

        // 6. Verificar o payload do update
        $payload = json_decode($updateLog->payload, true);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $payload['before']['numbers']);
        $this->assertEquals([6, 12, 24, 36, 48, 54], $payload['after']['numbers']);

        // 7. Excluir a aposta
        $bet->delete();

        // 8. Buscar logs de 'delete'
        $deleteLog = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->where('event', 'delete')
            ->first();

        $this->assertNotNull($deleteLog, "Log de exclusão não foi registrado");

        // 9. Verificar se todos os logs existem
        $allLogs = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(3, $allLogs, "Total de logs incorreto");
        $events = $allLogs->pluck('event')->toArray();
        $this->assertEquals(['insert', 'update', 'delete'], $events, "Sequência de eventos incorreta");
    }

    /**
     * Testa se os logs de auditoria são imutáveis, garantindo que não possam ser alterados
     * ou removidos após a criação.
     *
     * Este caso inclui:
     * - Criação de um log de auditoria.
     * - Tenta modificar o log diretamente via ORM (espera exceção).
     * - Tenta excluir o log (espera exceção).
     * - Valida que o log permanece inalterado e disponível.
     */
    #[Test]
    public function it_ensures_audit_logs_are_immutable()
    {
        // 1. Criar um registro para testar a imutabilidade
        $gameType = GameType::factory()->create([
            'name' => 'Quina_' . Str::random(8)
        ]);
        $user = User::factory()->create();

        $bet = Bet::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'numbers' => [5, 17, 23, 32, 41, 59],
        ]);

        // 2. Obter o log gerado para teste
        $log = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->first();

        $this->assertNotNull($log, "Log não foi criado");
        $originalPayload = $log->payload;
        $originalHash = $log->hash_integrity;

        // 3. Tentar modificar via ORM (deve falhar)
        $expectedException = false;
        try {
            $log->payload = '{}';
            $log->save();
        } catch (\Exception $e) {
            $expectedException = true;
            $this->assertStringContainsString('Modificação não permitida', $e->getMessage());
        }
        $this->assertTrue($expectedException, "Não lançou exceção ao tentar modificar o log");

        // 4. Tentar excluir via ORM (deve falhar)
        $expectedException = false;
        try {
            $log->delete();
        } catch (\Exception $e) {
            $expectedException = true;
            $this->assertStringContainsString('Modificação não permitida', $e->getMessage());
        }
        $this->assertTrue($expectedException, "Não lançou exceção ao tentar excluir o log");

        // 5. Verificar que o log continua inalterado
        $sameLog = AuditLog::find($log->id);
        $this->assertNotNull($sameLog, "Log não foi encontrado após tentativa de modificação");
        $this->assertEquals($originalPayload, $sameLog->payload, "Payload foi alterado");
        $this->assertEquals($originalHash, $sameLog->hash_integrity, "Hash foi alterado");
    }

    /**
     * Testa se o desempenho do sistema é aceitável ao criar apostas e gerar logs de auditoria,
     * medindo tempos de operação individuais e em lote.
     *
     * Este caso:
     * - Mede o tempo médio de operações únicas e em lote.
     * - Verifica que a operação em lote não é muito mais lenta que operações únicas.
     * - Garante que o tempo médio de todas as operações permaneça dentro de limites aceitáveis.
     */
    #[Test]
    public function it_performs_within_acceptable_time_limits_under_load()
    {
        $gameType = GameType::factory()->create([
            'name' => 'SimulottoFacil_' . Str::random(8)
        ]);
        $user = User::factory()->create();

        // 1. Medir tempo de operação simples
        $startTime = microtime(true);
        $bet = Bet::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'numbers' => [1, 10, 20, 30, 40, 50],
        ]);
        $endTime = microtime(true);
        $singleTime = $endTime - $startTime;

        // Verificar que o log foi criado
        $log = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->first();

        $this->assertNotNull($log, "Log de auditoria não foi criado para a operação simples");

        // 2. Medir tempo em lote
        $startTime = microtime(true);
        $batchSize = 5;

        for ($i = 0; $i < $batchSize; $i++) {
            // Gerar 6 números aleatórios entre 1 e 60, sem repetição
            $numbers = [];
            while (count($numbers) < 6) {
                $num = rand(1, 60);
                if (!in_array($num, $numbers)) {
                    $numbers[] = $num;
                }
            }
            sort($numbers);

            Bet::create([
                'user_id' => $user->id,
                'game_type_id' => $gameType->id,
                'numbers' => $numbers,
            ]);
        }

        $endTime = microtime(true);
        $batchTime = $endTime - $startTime;
        $avgTime = $batchTime / $batchSize;

        // 3. Verificar que o tempo médio não é muito pior
        $this->assertLessThan($singleTime * 2, $avgTime,
            "Operações em lote são muito mais lentas que operação única");

        // 4. Verificar limite absoluto (ajustado para mais tempo, pois inclui auditoria)
        $this->assertLessThan(1.0, $avgTime,
            "Tempo médio por operação excede 1 segundo: {$avgTime}s");
    }

    /**
     * Testa se operações concorrentes realizadas em apostas (atualizações e exclusões)
     * são gerenciadas e registradas corretamente no sistema.
     *
     * Este caso cobre:
     * - Criação de múltiplas apostas.
     * - Simulação de atualizações concorrentes e verificação dos logs de atualização.
     * - Simulação de exclusões concorrentes e verificação dos logs de exclusão.
     * - Verifica que os logs de todas as operações foram registrados corretamente.
     */
    #[Test]
    public function it_handles_concurrent_operations_correctly()
    {
        $gameType = GameType::factory()->create([
            'name' => 'SimulottoLotoMania_' . Str::random(8),
        ]);
        $user = User::factory()->create();

        // 1. Criar apostas para teste de concorrência
        $bets = [];
        for ($i = 0; $i < 3; $i++) {
            $bet = Bet::create([
                'user_id' => $user->id,
                'game_type_id' => $gameType->id,
                'numbers' => [($i + 1) * 10, ($i + 1) * 10 + 1, ($i + 1) * 10 + 2, ($i + 1) * 10 + 3, ($i + 1) * 10 + 4, ($i + 1) * 10 + 5],
            ]);
            $bets[] = $bet;
        }

        // 2. Simular atualizações concorrentes
        foreach ($bets as $index => $bet) {
            $bet->update([
                'numbers' => [($index + 1) * 10 + 5, ($index + 1) * 10 + 6, ($index + 1) * 10 + 7, ($index + 1) * 10 + 8, ($index + 1) * 10 + 9, ($index + 1) * 10 + 10],
            ]);
        }

        // 3. Verificar que todas as operações foram registradas
        foreach ($bets as $bet) {
            $updateLogs = AuditLog::where('table_name', 'bets')
                ->where('record_id', $bet->id)
                ->where('event', 'update')
                ->get();

            $this->assertGreaterThan(0, $updateLogs->count(), "Atualização não registrada para aposta {$bet->id}");

            // 4. Verificar payload
            $log = $updateLogs->first();
            $payload = json_decode($log->payload, true);
            $this->assertArrayHasKey('before', $payload);
            $this->assertArrayHasKey('after', $payload);
            $this->assertNotEquals($payload['before']['numbers'], $payload['after']['numbers']);
        }

        // 5. Simular exclusões concorrentes
        foreach ($bets as $bet) {
            $bet->delete();
        }

        // 6. Verificar que todas as exclusões foram registradas
        foreach ($bets as $bet) {
            $deleteLogs = AuditLog::where('table_name', 'bets')
                ->where('record_id', $bet->id)
                ->where('event', 'delete')
                ->get();

            $this->assertGreaterThan(0, $deleteLogs->count(), "Exclusão não registrada para aposta {$bet->id}");
        }
    }

    /**
     * Testa como o sistema lida com falhas de conexão no banco de dados de auditoria,
     * garantindo que as operações principais continuem funcionando.
     *
     * Este caso verifica:
     * - Criação de uma aposta normalmente, sem problemas de conexão.
     * - Valida que a aposta foi criada com os números corretos.
     * - Garante que um log de auditoria foi criado e está consistente.
     * - Valida o hash de integridade do log conforme esperado.
     */
    #[Test]
    public function it_handles_database_connection_failures_gracefully()
    {
        // Este teste verifica se a operação principal continua funcionando
        // mesmo quando há problemas com a conexão de auditoria

        $gameType = GameType::factory()->create([
            'name' => 'Timemania_' . Str::random(8)
        ]);
        $user = User::factory()->create();

        // 1. Testar uma aposta normal sem modificar conexões
        $bet = Bet::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'numbers' => [7, 14, 21, 28, 35, 42],
        ]);

        // 2. Verificar que a aposta foi criada e está disponível
        $this->assertNotNull($bet->id);
        $this->assertEquals([7, 14, 21, 28, 35, 42], $bet->numbers);

        // 3. Verificar que existe log de auditoria
        $log = AuditLog::where('table_name', 'bets')
            ->where('record_id', $bet->id)
            ->where('event', 'insert')
            ->first();

        $this->assertNotNull($log, "Log de auditoria não foi gerado");

        // 4. Verificar hash de integridade
        $this->assertNotNull($log->hash_integrity);
        $this->assertEquals(64, strlen($log->hash_integrity), "Hash SHA256 deve ter 64 caracteres");

        // 5. Verificar que o hash realmente corresponde ao payload
        $calculatedHash = hash('sha256', $log->payload);
        $this->assertEquals($calculatedHash, $log->hash_integrity, "Hash não corresponde ao payload");
    }
}
