<?php
declare(strict_types=1);

/**
 * Roteiro de testes no terminal: php teste.php
 * Mostra cada regra aceita e cada recusa, em sequência.
 */

date_default_timezone_set('America/Fortaleza');

require_once __DIR__ . '/src/Cliente.php';
require_once __DIR__ . '/src/Veiculo.php';
require_once __DIR__ . '/src/Servico.php';
require_once __DIR__ . '/src/Revisao.php';
require_once __DIR__ . '/src/TrocaOleo.php';
require_once __DIR__ . '/src/Funilaria.php';
require_once __DIR__ . '/src/OrdemServico.php';
require_once __DIR__ . '/src/RepositorioOrdens.php';

$falhas = 0;

function titulo(string $texto): void
{
    echo "\n=== {$texto} ===\n";
}

/** A operação precisa ser aceita (não pode lançar Exception). */
function esperaAceito(string $descricao, callable $operacao): void
{
    global $falhas;
    try {
        $operacao();
        echo "OK, aceito: {$descricao}\n";
    } catch (Exception $e) {
        $falhas++;
        echo "ERRO: {$descricao} foi recusado: {$e->getMessage()}\n";
    }
}

/** A operação precisa ser recusada com Exception. */
function esperaRecusa(string $descricao, callable $operacao): void
{
    global $falhas;
    try {
        $operacao();
        $falhas++;
        echo "ERRO: {$descricao} foi aceito\n";
    } catch (Exception $e) {
        echo "OK, recusado ({$descricao}): {$e->getMessage()}\n";
    }
}

function confere(string $descricao, mixed $obtido, mixed $esperado): void
{
    global $falhas;
    if ($obtido === $esperado) {
        echo "OK, {$descricao}: " . var_export($obtido, true) . "\n";
    } else {
        $falhas++;
        echo "ERRO, {$descricao}: esperado " . var_export($esperado, true) . ' e veio ' . var_export($obtido, true) . "\n";
    }
}

// ---------------------------------------------------------------
titulo('1. Cliente (encapsulamento e validação)');
esperaAceito('cliente Maria Souza (88) 99999-1234', function () {
    $c = new Cliente('Maria Souza', '(88) 99999-1234');
    echo "   telefone guardado: {$c->getTelefone()} | formatado: {$c->getTelefoneFormatado()}\n";
});
esperaRecusa('nome com 2 letras', fn() => new Cliente('Jo', '88999991234'));
esperaRecusa('telefone sem DDD', fn() => new Cliente('João Lima', '99991234'));

// ---------------------------------------------------------------
titulo('2. Veículo');
esperaAceito('Fiat Uno 2012, placa ABC-1234, 85.000 km', fn() => new Veiculo('ABC-1234', 'Fiat', 'Uno', 2012, 85000));
esperaAceito('placa Mercosul BRA2E19', fn() => new Veiculo('BRA2E19', 'Honda', 'Fit', 2020, 30000));
esperaRecusa('placa inválida', fn() => new Veiculo('12-ABCD', 'Fiat', 'Uno', 2012, 85000));
esperaRecusa('ano 1950', fn() => new Veiculo('ABC1234', 'Ford', 'Corcel', 1950, 1000));
esperaRecusa('quilometragem negativa', fn() => new Veiculo('ABC1234', 'Fiat', 'Uno', 2012, -5));
esperaRecusa('hodômetro diminuindo', function () {
    $v = new Veiculo('ABC1234', 'Fiat', 'Uno', 2012, 85000);
    $v->setQuilometragem(80000);
});

// ---------------------------------------------------------------
titulo('3. Serviços: regras de cada classe da hierarquia');
esperaAceito('mão de obra avulsa (Servico)', fn() => new Servico('Alinhamento de faróis', 1.0, 90.0));
esperaRecusa('Servico com 0 hora', fn() => new Servico('Alinhamento', 0.0, 90.0));
esperaRecusa('Servico com valor da hora zero', fn() => new Servico('Alinhamento', 1.0, 0.0));
esperaAceito('Revisão completa de 3 h', fn() => new Revisao('Checklist de 40 itens', 3.0, 90.0, 80000, 'completa'));
esperaRecusa('Revisão completa de 2 h', fn() => new Revisao('Checklist', 2.0, 90.0, 80000, 'completa'));
esperaRecusa('Revisão com tipo inexistente', fn() => new Revisao('Checklist', 3.0, 90.0, 80000, 'premium'));
esperaAceito('Troca de óleo sintético de 4 L', fn() => new TrocaOleo('Óleo 5W30', 1.0, 90.0, 4.0, 'sintetico'));
esperaRecusa('Troca de óleo com 0 L', fn() => new TrocaOleo('Óleo', 1.0, 90.0, 0.0, 'mineral'));
esperaRecusa('Troca de óleo com 12 L', fn() => new TrocaOleo('Óleo', 1.0, 90.0, 12.0, 'mineral'));
esperaRecusa('Troca de óleo de 3 h', fn() => new TrocaOleo('Óleo', 3.0, 90.0, 4.0, 'mineral'));
esperaAceito('Funilaria de 2 painéis com pintura (4 h)', fn() => new Funilaria('Porta e paralama', 4.0, 90.0, 2, true));
esperaRecusa('Funilaria com 0 painel', fn() => new Funilaria('Porta', 4.0, 90.0, 0, false));
esperaRecusa('Funilaria com horas insuficientes', fn() => new Funilaria('Porta e paralama', 3.0, 90.0, 2, false));

// ---------------------------------------------------------------
titulo('4. Polimorfismo: o mesmo método, cada filha responde do seu jeito');
$servicos = [
    new Servico('Alinhamento de faróis', 1.0, 90.0),
    new Revisao('Checklist de 40 itens', 3.0, 90.0, 80000, 'completa'),
    new TrocaOleo('Óleo 5W30', 1.0, 90.0, 4.0, 'sintetico'),
    new Funilaria('Porta e paralama', 4.0, 90.0, 2, true),
];
foreach ($servicos as $servico) {
    printf(
        "   [%s] R$ %s | %s\n",
        $servico->getNomeTipo(),
        number_format($servico->calcularValor(), 2, ',', '.'),
        $servico->descricaoDetalhada()
    );
}
confere('valor da Revisão completa (3 h x 90 + 220)', $servicos[1]->calcularValor(), 490.0);
confere('valor da Troca de óleo (1 h x 90 + 4 L x 65 + filtro 35)', $servicos[2]->calcularValor(), 385.0);
confere('valor da Funilaria (4 h x 90 + 2 x 350 + 2 x 420)', $servicos[3]->calcularValor(), 1900.0);

// ---------------------------------------------------------------
titulo('5. Ordem de serviço (composição e regras)');
$cliente = new Cliente('Maria Souza', '88999991234');
$veiculo = new Veiculo('ABC1D23', 'Fiat', 'Uno', 2012, 85000);
$ordem = new OrdemServico(1, $cliente, $veiculo);

esperaRecusa('iniciar ordem sem serviços', fn() => $ordem->alterarStatus('em_andamento'));
esperaAceito('adicionar Troca de óleo', fn() => $ordem->adicionarServico(new TrocaOleo('Óleo 5W30', 1.0, 90.0, 4.0, 'sintetico')));
esperaRecusa('segunda Troca de óleo na mesma ordem', fn() => $ordem->adicionarServico(new TrocaOleo('Óleo', 1.0, 90.0, 3.0, 'mineral')));
confere('total com 1 serviço (sem desconto)', $ordem->calcularTotal(), 385.0);

esperaAceito('adicionar Revisão', fn() => $ordem->adicionarServico(new Revisao('Checklist', 3.0, 90.0, 85000, 'completa')));
confere('total com 2 serviços (sem desconto)', $ordem->calcularTotal(), 875.0);

esperaAceito('adicionar Funilaria (3º serviço, ativa o desconto de 10%)', fn() => $ordem->adicionarServico(new Funilaria('Porta e paralama', 4.0, 90.0, 2, true)));
confere('subtotal com 3 serviços', $ordem->calcularSubtotal(), 2775.0);
confere('desconto de 10%', $ordem->calcularDesconto(), 277.5);
confere('total com desconto', $ordem->calcularTotal(), 2497.5);

esperaAceito('adicionar mão de obra avulsa 1', fn() => $ordem->adicionarServico(new Servico('Balanceamento', 1.0, 90.0)));
esperaAceito('adicionar mão de obra avulsa 2 (5º serviço)', fn() => $ordem->adicionarServico(new Servico('Alinhamento', 1.0, 90.0)));
esperaRecusa('6º serviço (limite de 5)', fn() => $ordem->adicionarServico(new Servico('Lavagem', 1.0, 90.0)));

// ---------------------------------------------------------------
titulo('6. Fluxo de status');
esperaRecusa('aberta -> finalizada (pulando etapa)', fn() => $ordem->alterarStatus('finalizada'));
esperaRecusa('status que não existe', fn() => $ordem->alterarStatus('pausada'));
esperaAceito('aberta -> em andamento', fn() => $ordem->alterarStatus('em_andamento'));
esperaAceito('em andamento -> finalizada', fn() => $ordem->alterarStatus('finalizada'));
esperaRecusa('adicionar serviço em ordem finalizada', fn() => $ordem->adicionarServico(new Servico('Lavagem', 1.0, 90.0)));
esperaRecusa('reabrir ordem finalizada', fn() => $ordem->alterarStatus('aberta'));

// ---------------------------------------------------------------
titulo('7. paraArray() e json_encode');
$json = json_encode($ordem->paraArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo substr((string) $json, 0, 700) . "\n   (...saída cortada)\n";

// ---------------------------------------------------------------
titulo('8. Persistência em arquivo JSON (bônus)');
$arquivoTemp = sys_get_temp_dir() . '/oficina-teste-' . uniqid() . '.json';
$repo = new RepositorioOrdens($arquivoTemp);
confere('próximo id com arquivo vazio', $repo->proximoId(), 1);
esperaAceito('salvar a ordem no arquivo', fn() => $repo->salvar($ordem));
confere('próximo id depois de salvar', $repo->proximoId(), 2);

$recarregada = (new RepositorioOrdens($arquivoTemp))->buscar(1);
confere('ordem recarregada existe', $recarregada !== null, true);
if ($recarregada !== null) {
    confere('total igual depois de recarregar', $recarregada->calcularTotal(), $ordem->calcularTotal());
    confere('status igual depois de recarregar', $recarregada->getStatus(), 'finalizada');
    confere('quantidade de serviços igual', $recarregada->getQuantidadeServicos(), 5);
}
confere('ordem inexistente retorna null', $repo->buscar(99), null);
esperaRecusa('arquivo corrompido', function () use ($arquivoTemp) {
    file_put_contents($arquivoTemp, '{ isto nao e json');
    (new RepositorioOrdens($arquivoTemp))->todas();
});
unlink($arquivoTemp);

// ---------------------------------------------------------------
echo "\n";
if ($falhas === 0) {
    echo "Resultado: todos os testes passaram.\n";
    exit(0);
}
echo "Resultado: {$falhas} teste(s) com falha.\n";
exit(1);
