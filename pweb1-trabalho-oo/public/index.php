<?php
declare(strict_types=1);

/*
 * Front controller da Oficina Mecânica.
 * Toda a lógica PHP fica aqui no topo; o HTML começa depois do bloco de lógica (W5).
 *
 * Rotas:
 *   ?pagina=inicio            quadro com as ordens por status
 *   ?pagina=ordens            lista de ordens
 *   ?pagina=ordem&id=N        detalhe da ordem (adicionar serviço, mudar status)
 *   ?pagina=nova              formulário POST que cria uma ordem
 *   &formato=json             versão JSON de inicio, ordens e ordem
 */

date_default_timezone_set('America/Fortaleza');

require_once __DIR__ . '/../src/Cliente.php';
require_once __DIR__ . '/../src/Veiculo.php';
require_once __DIR__ . '/../src/Servico.php';
require_once __DIR__ . '/../src/Revisao.php';
require_once __DIR__ . '/../src/TrocaOleo.php';
require_once __DIR__ . '/../src/Funilaria.php';
require_once __DIR__ . '/../src/OrdemServico.php';
require_once __DIR__ . '/../src/RepositorioOrdens.php';

const ARQUIVO_DADOS = __DIR__ . '/../data/ordens.json';

// ------------------------------------------------------------------
// Funções auxiliares
// ------------------------------------------------------------------

/** W4: escapa qualquer dado antes de colocá-lo no HTML. */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/** Lê um parâmetro da query string (sempre como texto). */
function parametro(string $nome, string $padrao = ''): string
{
    $valor = $_GET[$nome] ?? $padrao;
    return is_string($valor) ? trim($valor) : $padrao;
}

/** Lê um campo do formulário POST (sempre como texto). */
function campo(string $nome, string $padrao = ''): string
{
    $valor = $_POST[$nome] ?? $padrao;
    return is_string($valor) ? trim($valor) : $padrao;
}

/** Valor para repreencher o formulário depois de um erro. */
function valorForm(string $nome, string $padrao = ''): string
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? campo($nome, $padrao) : $padrao;
}

function numero(string $texto, string $nomeCampo): float
{
    $texto = str_replace(',', '.', trim($texto));
    if (!is_numeric($texto)) {
        throw new Exception("O campo {$nomeCampo} deve ser um número.");
    }
    return (float) $texto;
}

function inteiro(string $texto, string $nomeCampo): int
{
    if (!preg_match('/^-?[0-9]+$/', trim($texto))) {
        throw new Exception("O campo {$nomeCampo} deve ser um número inteiro.");
    }
    return (int) $texto;
}

function redirecionar(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** W2: resposta JSON com o Content-Type correto. */
function responderJson(array $dados, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Monta o serviço certo (uma das classes filhas) a partir do formulário. */
function montarServico(): Servico
{
    $descricao = campo('servico_descricao');
    $horas = numero(campo('servico_horas'), 'horas de mão de obra');
    $valorHora = numero(campo('servico_valor_hora'), 'valor da hora');

    return match (campo('servico_tipo')) {
        'revisao' => new Revisao(
            $descricao, $horas, $valorHora,
            inteiro(campo('km_atual'), 'quilometragem da revisão'),
            campo('tipo_revisao')
        ),
        'troca_oleo' => new TrocaOleo(
            $descricao, $horas, $valorHora,
            numero(campo('litros'), 'litros de óleo'),
            campo('tipo_oleo')
        ),
        'funilaria' => new Funilaria(
            $descricao, $horas, $valorHora,
            inteiro(campo('paineis'), 'quantidade de painéis'),
            isset($_POST['pintura'])
        ),
        'generico' => new Servico($descricao, $horas, $valorHora),
        default => throw new Exception('Escolha um tipo de serviço válido.'),
    };
}

// ------------------------------------------------------------------
// Dados fixos das telas
// ------------------------------------------------------------------

$menu = [
    'inicio' => 'Quadro',
    'ordens' => 'Ordens',
    'nova' => 'Nova ordem',
];
$paginasValidas = array_merge(array_keys($menu), ['ordem']);

$tiposServico = [
    'revisao' => 'Revisão',
    'troca_oleo' => 'Troca de óleo',
    'funilaria' => 'Funilaria',
    'generico' => 'Mão de obra avulsa',
];
$tiposRevisao = ['basica' => 'Básica', 'completa' => 'Completa'];
$tiposOleo = ['mineral' => 'Mineral', 'semissintetico' => 'Semissintético', 'sintetico' => 'Sintético'];

$mensagensOk = [
    'criada' => 'Ordem de serviço criada.',
    'servico' => 'Serviço adicionado à ordem.',
    'status' => 'Status da ordem atualizado.',
];

// ------------------------------------------------------------------
// Roteamento e processamento
// ------------------------------------------------------------------

$repo = new RepositorioOrdens(ARQUIVO_DADOS);
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pagina = parametro('pagina', 'inicio');
$formato = parametro('formato', 'html');

$status = 200;
$erro = '';
$mensagem404 = '';
$mensagemOk = $mensagensOk[parametro('ok')] ?? '';
$ordem = null;
$ordens = [];
$colunas = [];
$faturamento = 0.0;

// W1: página inexistente => 404.
if (!in_array($pagina, $paginasValidas, true)) {
    $status = 404;
    $mensagem404 = 'A página que você pediu não existe.';
    $pagina = '404';
}

// A rota JSON é somente leitura!
if ($formato === 'json' && $metodo !== 'GET') {
    header('Allow: GET');
    responderJson(['erro' => 'A rota JSON aceita apenas o método GET.'], 405);
}

try {
    if ($pagina === 'inicio') {
        $ordens = array_reverse($repo->todas());
        $colunas = array_fill_keys(array_keys(OrdemServico::STATUS_ROTULOS), []);
        foreach ($ordens as $item) {
            $colunas[$item->getStatus()][] = $item;
            if ($item->getStatus() === 'finalizada') {
                $faturamento += $item->calcularTotal();
            }
        }
    } elseif ($pagina === 'ordens') {
        $ordens = array_reverse($repo->todas());
    } elseif ($pagina === 'ordem') {
        $id = (int) parametro('id', '0');
        $ordem = $repo->buscar($id);

        if ($ordem === null) {
            $status = 404;
            $mensagem404 = "A ordem de serviço nº {$id} não foi encontrada.";
            $pagina = '404';
        } elseif ($metodo === 'POST') {
            $acao = campo('acao');
            if ($acao === 'adicionar_servico') {
                $ordem->adicionarServico(montarServico());
                $repo->salvar($ordem);
                redirecionar('index.php?pagina=ordem&id=' . $ordem->getId() . '&ok=servico');
            } elseif ($acao === 'alterar_status') {
                $ordem->alterarStatus(campo('novo_status'));
                $repo->salvar($ordem);
                redirecionar('index.php?pagina=ordem&id=' . $ordem->getId() . '&ok=status');
            } else {
                throw new Exception('Ação desconhecida.');
            }
        }
    } elseif ($pagina === 'nova' && $metodo === 'POST' && $formato !== 'json') {
        // W3: o formulário cria os objetos; se uma regra recusar, a mensagem aparece na página.
        $cliente = new Cliente(campo('cliente_nome'), campo('cliente_telefone'));
        $veiculo = new Veiculo(
            campo('veiculo_placa'),
            campo('veiculo_marca'),
            campo('veiculo_modelo'),
            inteiro(campo('veiculo_ano'), 'ano do veículo'),
            inteiro(campo('veiculo_km'), 'quilometragem do veículo')
        );
        $novaOrdem = new OrdemServico($repo->proximoId(), $cliente, $veiculo);
        $novaOrdem->adicionarServico(montarServico());
        $repo->salvar($novaOrdem);
        redirecionar('index.php?pagina=ordem&id=' . $novaOrdem->getId() . '&ok=criada');
    }
} catch (Exception $excecao) {
    $erro = $excecao->getMessage();
}

// W2: rota JSON gerada com paraArray() e json_encode.
if ($formato === 'json') {
    if ($status === 404) {
        responderJson(['erro' => $mensagem404], 404);
    }
    if ($erro !== '') {
        responderJson(['erro' => $erro], 500);
    }

    if ($pagina === 'ordens') {
        $lista = [];
        foreach ($ordens as $item) {
            $lista[] = $item->paraArray();
        }
        responderJson(['total' => count($lista), 'ordens' => $lista]);
    }
    if ($pagina === 'ordem' && $ordem !== null) {
        responderJson($ordem->paraArray());
    }
    if ($pagina === 'inicio') {
        $resumo = ['total_ordens' => count($ordens), 'faturamento_finalizadas' => round($faturamento, 2), 'por_status' => []];
        foreach ($colunas as $chave => $lista) {
            $resumo['por_status'][$chave] = count($lista);
        }
        responderJson($resumo);
    }
    responderJson(['erro' => 'Esta página não possui versão JSON.'], 400);
}

$titulos = [
    'inicio' => 'Quadro da oficina',
    'ordens' => 'Ordens de serviço',
    'nova' => 'Nova ordem de serviço',
    'ordem' => $ordem !== null ? 'Ordem nº ' . $ordem->getId() : 'Ordem de serviço',
    '404' => 'Página não encontrada',
];
$tituloPagina = $titulos[$pagina];

http_response_code($status);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina) ?> | Oficina Mecânica</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <header class="topo">
        <div class="topo-conteudo">
            <a class="marca" href="index.php?pagina=inicio">Oficina Mecânica</a>
            <nav class="menu" aria-label="Menu principal">
                <?php foreach ($menu as $chave => $rotulo): ?>
                    <a href="index.php?pagina=<?= e($chave) ?>"<?= $chave === $pagina ? ' aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main class="conteudo">
        <h1><?= e($tituloPagina) ?></h1>

        <?php if ($mensagemOk !== ''): ?>
            <p class="aviso aviso-ok" role="status"><?= e($mensagemOk) ?></p>
        <?php endif; ?>
        <?php if ($erro !== ''): ?>
            <p class="aviso aviso-erro" role="alert"><?= e($erro) ?></p>
        <?php endif; ?>

        <?php if ($pagina === '404'): ?>

            <p><?= e($mensagem404) ?></p>
            <p><a href="index.php?pagina=ordens">Ver todas as ordens de serviço</a></p>

        <?php elseif ($pagina === 'inicio'): ?>

            <p class="resumo">
                <?= e(count($ordens)) ?> ordem(ns) no total.
                Faturamento das finalizadas: <strong><?= e(moeda($faturamento)) ?></strong>.
                <a href="index.php?pagina=inicio&amp;formato=json">Ver em JSON</a>
            </p>

            <div class="quadro">
                <?php foreach ($colunas as $chave => $lista): ?>
                    <section class="coluna coluna-<?= e($chave) ?>" aria-label="<?= e(OrdemServico::STATUS_ROTULOS[$chave]) ?>">
                        <h2><?= e(OrdemServico::STATUS_ROTULOS[$chave]) ?> <span class="contador"><?= e(count($lista)) ?></span></h2>
                        <?php if (count($lista) === 0): ?>
                            <p class="vazio">Nenhuma ordem aqui.</p>
                        <?php endif; ?>
                        <?php foreach ($lista as $item): ?>
                            <a class="ficha" href="index.php?pagina=ordem&amp;id=<?= e($item->getId()) ?>">
                                <span class="placa"><?= e($item->getVeiculo()->getPlaca()) ?></span>
                                <span class="ficha-linha"><?= e($item->getVeiculo()->getDescricao()) ?></span>
                                <span class="ficha-linha"><?= e($item->getCliente()->getNome()) ?></span>
                                <span class="ficha-linha ficha-total">Ordem <?= e($item->getId()) ?> · <?= e(moeda($item->calcularTotal())) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            </div>

        <?php elseif ($pagina === 'ordens'): ?>

            <p class="resumo">
                <a href="index.php?pagina=ordens&amp;formato=json">Ver em JSON</a>
            </p>

            <?php if (count($ordens) === 0): ?>
                <p class="vazio">Nenhuma ordem registrada. <a href="index.php?pagina=nova">Abra a primeira ordem</a>.</p>
            <?php else: ?>
                <div class="rolagem">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Nº</th>
                                <th scope="col">Abertura</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Veículo</th>
                                <th scope="col">Serviços</th>
                                <th scope="col" class="num">Total</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordens as $item): ?>
                                <tr>
                                    <td><a href="index.php?pagina=ordem&amp;id=<?= e($item->getId()) ?>"><?= e($item->getId()) ?></a></td>
                                    <td><?= e($item->getDataAbertura()) ?></td>
                                    <td><?= e($item->getCliente()->getNome()) ?></td>
                                    <td><span class="placa"><?= e($item->getVeiculo()->getPlaca()) ?></span> <?= e($item->getVeiculo()->getDescricao()) ?></td>
                                    <td><?= e($item->getQuantidadeServicos()) ?></td>
                                    <td class="num"><?= e(moeda($item->calcularTotal())) ?></td>
                                    <td><span class="selo selo-<?= e($item->getStatus()) ?>"><?= e($item->getStatusRotulo()) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php elseif ($pagina === 'ordem' && $ordem !== null): ?>

            <p class="resumo">
                <span class="selo selo-<?= e($ordem->getStatus()) ?>"><?= e($ordem->getStatusRotulo()) ?></span>
                Aberta em <?= e($ordem->getDataAbertura()) ?>.
                <a href="index.php?pagina=ordem&amp;id=<?= e($ordem->getId()) ?>&amp;formato=json">Ver em JSON</a>
            </p>

            <div class="duas-colunas">
                <section class="bloco">
                    <h2>Cliente</h2>
                    <p><?= e($ordem->getCliente()->getNome()) ?><br>
                       <?= e($ordem->getCliente()->getTelefoneFormatado()) ?></p>
                </section>
                <section class="bloco">
                    <h2>Veículo</h2>
                    <p><span class="placa"><?= e($ordem->getVeiculo()->getPlaca()) ?></span><br>
                       <?= e($ordem->getVeiculo()->getDescricao()) ?><br>
                       <?= e(number_format($ordem->getVeiculo()->getQuilometragem(), 0, ',', '.')) ?> km</p>
                </section>
            </div>

            <section class="bloco">
                <h2>Serviços</h2>
                <?php if ($ordem->getQuantidadeServicos() === 0): ?>
                    <p class="vazio">Nenhum serviço nesta ordem ainda.</p>
                <?php else: ?>
                    <div class="rolagem">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">O que será feito</th>
                                    <th scope="col" class="num">Horas</th>
                                    <th scope="col" class="num">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php /* Polimorfismo: o mesmo método em objetos de classes diferentes. */ ?>
                                <?php foreach ($ordem->getServicos() as $servico): ?>
                                    <tr>
                                        <td><?= e($servico->getNomeTipo()) ?></td>
                                        <td><?= e($servico->descricaoDetalhada()) ?></td>
                                        <td class="num"><?= e(number_format($servico->getHoras(), 1, ',', '.')) ?></td>
                                        <td class="num"><?= e(moeda($servico->calcularValor())) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr><th scope="row" colspan="3">Subtotal</th><td class="num"><?= e(moeda($ordem->calcularSubtotal())) ?></td></tr>
                                <tr><th scope="row" colspan="3">Desconto (10% a partir de 3 serviços)</th><td class="num">− <?= e(moeda($ordem->calcularDesconto())) ?></td></tr>
                                <tr class="total"><th scope="row" colspan="3">Total</th><td class="num"><?= e(moeda($ordem->calcularTotal())) ?></td></tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="bloco">
                <h2>Andamento</h2>
                <?php if (count($ordem->getProximosStatus()) === 0): ?>
                    <p class="vazio">Esta ordem está encerrada e não pode mais ser alterada.</p>
                <?php else: ?>
                    <form method="post" action="index.php?pagina=ordem&amp;id=<?= e($ordem->getId()) ?>" class="linha-form" novalidate>
                        <input type="hidden" name="acao" value="alterar_status">
                        <label for="novo_status">Mudar para</label>
                        <select id="novo_status" name="novo_status">
                            <?php foreach ($ordem->getProximosStatus() as $proximo): ?>
                                <option value="<?= e($proximo) ?>"><?= e(OrdemServico::STATUS_ROTULOS[$proximo]) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Atualizar status</button>
                    </form>
                <?php endif; ?>
            </section>

            <?php if (in_array($ordem->getStatus(), ['aberta', 'em_andamento'], true)): ?>
                <section class="bloco">
                    <h2>Adicionar serviço</h2>
                    <form method="post" action="index.php?pagina=ordem&amp;id=<?= e($ordem->getId()) ?>" novalidate>
                        <input type="hidden" name="acao" value="adicionar_servico">

                        <div class="grade">
                            <div class="campo">
                                <label for="servico_tipo">Tipo de serviço</label>
                                <select id="servico_tipo" name="servico_tipo">
                                    <?php foreach ($tiposServico as $valor => $rotulo): ?>
                                        <option value="<?= e($valor) ?>"<?= valorForm('servico_tipo', 'revisao') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="campo">
                                <label for="servico_descricao">Descrição</label>
                                <input id="servico_descricao" name="servico_descricao" type="text" value="<?= e(valorForm('servico_descricao')) ?>">
                            </div>
                            <div class="campo">
                                <label for="servico_horas">Horas de mão de obra</label>
                                <input id="servico_horas" name="servico_horas" type="text" inputmode="decimal" value="<?= e(valorForm('servico_horas')) ?>">
                            </div>
                            <div class="campo">
                                <label for="servico_valor_hora">Valor da hora (R$)</label>
                                <input id="servico_valor_hora" name="servico_valor_hora" type="text" inputmode="decimal" value="<?= e(valorForm('servico_valor_hora', '90')) ?>">
                            </div>
                        </div>

                        <fieldset data-tipo="revisao">
                            <legend>Dados da revisão</legend>
                            <div class="grade">
                                <div class="campo">
                                    <label for="km_atual">Quilometragem da revisão</label>
                                    <input id="km_atual" name="km_atual" type="number" value="<?= e(valorForm('km_atual')) ?>">
                                </div>
                                <div class="campo">
                                    <label for="tipo_revisao">Tipo de revisão</label>
                                    <select id="tipo_revisao" name="tipo_revisao">
                                        <?php foreach ($tiposRevisao as $valor => $rotulo): ?>
                                            <option value="<?= e($valor) ?>"<?= valorForm('tipo_revisao', 'basica') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset data-tipo="troca_oleo">
                            <legend>Dados da troca de óleo</legend>
                            <div class="grade">
                                <div class="campo">
                                    <label for="litros">Litros de óleo</label>
                                    <input id="litros" name="litros" type="text" inputmode="decimal" value="<?= e(valorForm('litros')) ?>">
                                </div>
                                <div class="campo">
                                    <label for="tipo_oleo">Tipo de óleo</label>
                                    <select id="tipo_oleo" name="tipo_oleo">
                                        <?php foreach ($tiposOleo as $valor => $rotulo): ?>
                                            <option value="<?= e($valor) ?>"<?= valorForm('tipo_oleo', 'mineral') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset data-tipo="funilaria">
                            <legend>Dados da funilaria</legend>
                            <div class="grade">
                                <div class="campo">
                                    <label for="paineis">Painéis atendidos</label>
                                    <input id="paineis" name="paineis" type="number" value="<?= e(valorForm('paineis')) ?>">
                                </div>
                                <div class="campo campo-check">
                                    <input id="pintura" name="pintura" type="checkbox" value="1"<?= (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['pintura'])) ? ' checked' : '' ?>>
                                    <label for="pintura">Inclui pintura</label>
                                </div>
                            </div>
                        </fieldset>

                        <button type="submit">Adicionar serviço</button>
                    </form>
                </section>
            <?php endif; ?>

        <?php elseif ($pagina === 'nova'): ?>

            <p class="resumo">Preencha o cliente, o veículo e o primeiro serviço. Os outros serviços entram depois, na página da ordem.</p>

            <form method="post" action="index.php?pagina=nova" novalidate>
                <fieldset>
                    <legend>Cliente</legend>
                    <div class="grade">
                        <div class="campo">
                            <label for="cliente_nome">Nome</label>
                            <input id="cliente_nome" name="cliente_nome" type="text" value="<?= e(valorForm('cliente_nome')) ?>">
                        </div>
                        <div class="campo">
                            <label for="cliente_telefone">Telefone com DDD</label>
                            <input id="cliente_telefone" name="cliente_telefone" type="text" inputmode="tel" value="<?= e(valorForm('cliente_telefone')) ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Veículo</legend>
                    <div class="grade">
                        <div class="campo">
                            <label for="veiculo_placa">Placa</label>
                            <input id="veiculo_placa" name="veiculo_placa" type="text" value="<?= e(valorForm('veiculo_placa')) ?>">
                        </div>
                        <div class="campo">
                            <label for="veiculo_marca">Marca</label>
                            <input id="veiculo_marca" name="veiculo_marca" type="text" value="<?= e(valorForm('veiculo_marca')) ?>">
                        </div>
                        <div class="campo">
                            <label for="veiculo_modelo">Modelo</label>
                            <input id="veiculo_modelo" name="veiculo_modelo" type="text" value="<?= e(valorForm('veiculo_modelo')) ?>">
                        </div>
                        <div class="campo">
                            <label for="veiculo_ano">Ano</label>
                            <input id="veiculo_ano" name="veiculo_ano" type="number" value="<?= e(valorForm('veiculo_ano')) ?>">
                        </div>
                        <div class="campo">
                            <label for="veiculo_km">Quilometragem atual</label>
                            <input id="veiculo_km" name="veiculo_km" type="number" value="<?= e(valorForm('veiculo_km')) ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Primeiro serviço</legend>
                    <div class="grade">
                        <div class="campo">
                            <label for="servico_tipo">Tipo de serviço</label>
                            <select id="servico_tipo" name="servico_tipo">
                                <?php foreach ($tiposServico as $valor => $rotulo): ?>
                                    <option value="<?= e($valor) ?>"<?= valorForm('servico_tipo', 'revisao') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="servico_descricao">Descrição</label>
                            <input id="servico_descricao" name="servico_descricao" type="text" value="<?= e(valorForm('servico_descricao')) ?>">
                        </div>
                        <div class="campo">
                            <label for="servico_horas">Horas de mão de obra</label>
                            <input id="servico_horas" name="servico_horas" type="text" inputmode="decimal" value="<?= e(valorForm('servico_horas')) ?>">
                        </div>
                        <div class="campo">
                            <label for="servico_valor_hora">Valor da hora (R$)</label>
                            <input id="servico_valor_hora" name="servico_valor_hora" type="text" inputmode="decimal" value="<?= e(valorForm('servico_valor_hora', '90')) ?>">
                        </div>
                    </div>
                </fieldset>

                <fieldset data-tipo="revisao">
                    <legend>Dados da revisão</legend>
                    <div class="grade">
                        <div class="campo">
                            <label for="km_atual">Quilometragem da revisão</label>
                            <input id="km_atual" name="km_atual" type="number" value="<?= e(valorForm('km_atual')) ?>">
                        </div>
                        <div class="campo">
                            <label for="tipo_revisao">Tipo de revisão</label>
                            <select id="tipo_revisao" name="tipo_revisao">
                                <?php foreach ($tiposRevisao as $valor => $rotulo): ?>
                                    <option value="<?= e($valor) ?>"<?= valorForm('tipo_revisao', 'basica') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset data-tipo="troca_oleo">
                    <legend>Dados da troca de óleo</legend>
                    <div class="grade">
                        <div class="campo">
                            <label for="litros">Litros de óleo</label>
                            <input id="litros" name="litros" type="text" inputmode="decimal" value="<?= e(valorForm('litros')) ?>">
                        </div>
                        <div class="campo">
                            <label for="tipo_oleo">Tipo de óleo</label>
                            <select id="tipo_oleo" name="tipo_oleo">
                                <?php foreach ($tiposOleo as $valor => $rotulo): ?>
                                    <option value="<?= e($valor) ?>"<?= valorForm('tipo_oleo', 'mineral') === $valor ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset data-tipo="funilaria">
                    <legend>Dados da funilaria</legend>
                    <div class="grade">
                        <div class="campo">
                            <label for="paineis">Painéis atendidos</label>
                            <input id="paineis" name="paineis" type="number" value="<?= e(valorForm('paineis')) ?>">
                        </div>
                        <div class="campo campo-check">
                            <input id="pintura" name="pintura" type="checkbox" value="1"<?= (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['pintura'])) ? ' checked' : '' ?>>
                            <label for="pintura">Inclui pintura</label>
                        </div>
                    </div>
                </fieldset>

                <button type="submit">Abrir ordem de serviço</button>
            </form>

        <?php endif; ?>
    </main>

    <footer class="rodape">
        <p>Programação Web I · IFCE Campus Boa Viagem</p>
    </footer>

    <script>
        // Mostra só os campos do tipo de serviço escolhido (sem JavaScript, todos aparecem).
        const seletorTipo = document.getElementById('servico_tipo');
        if (seletorTipo) {
            const atualizar = () => {
                document.querySelectorAll('fieldset[data-tipo]').forEach((grupo) => {
                    grupo.hidden = grupo.dataset.tipo !== seletorTipo.value;
                });
            };
            seletorTipo.addEventListener('change', atualizar);
            atualizar();
        }
    </script>
</body>
</html>
