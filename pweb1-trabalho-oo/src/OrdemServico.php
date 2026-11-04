<?php
declare(strict_types=1);

require_once __DIR__ . '/Cliente.php';
require_once __DIR__ . '/Veiculo.php';
require_once __DIR__ . '/Servico.php';
require_once __DIR__ . '/Revisao.php';
require_once __DIR__ . '/TrocaOleo.php';
require_once __DIR__ . '/Funilaria.php';

/**
 * Ordem de serviço da oficina.
 * Composição: a ordem TEM um cliente, um veículo e uma lista de serviços.
 */
class OrdemServico
{
    public const STATUS_ROTULOS = [
        'aberta' => 'Aberta',
        'em_andamento' => 'Em andamento',
        'finalizada' => 'Finalizada',
        'cancelada' => 'Cancelada',
    ];

    private const TRANSICOES = [
        'aberta' => ['em_andamento', 'cancelada'],
        'em_andamento' => ['finalizada', 'cancelada'],
        'finalizada' => [],
        'cancelada' => [],
    ];

    private const MAX_SERVICOS = 5;
    private const MINIMO_PARA_DESCONTO = 3;
    private const PERCENTUAL_DESCONTO = 0.10;

    private int $id;
    private Cliente $cliente;
    private Veiculo $veiculo;
    /** @var Servico[] */
    private array $servicos = [];
    private string $status = 'aberta';
    private string $dataAbertura;

    public function __construct(int $id, Cliente $cliente, Veiculo $veiculo, string $dataAbertura = '')
    {
        if ($id <= 0) {
            throw new Exception('O número da ordem de serviço deve ser maior que zero.');
        }
        $this->id = $id;
        $this->cliente = $cliente;
        $this->veiculo = $veiculo;
        $this->dataAbertura = $dataAbertura !== '' ? $dataAbertura : date('d/m/Y H:i');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function getVeiculo(): Veiculo
    {
        return $this->veiculo;
    }

    /** @return Servico[] */
    public function getServicos(): array
    {
        return $this->servicos;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusRotulo(): string
    {
        return self::STATUS_ROTULOS[$this->status];
    }

    public function getDataAbertura(): string
    {
        return $this->dataAbertura;
    }

    public function getQuantidadeServicos(): int
    {
        return count($this->servicos);
    }

    /** Status para os quais a ordem pode ir a partir do atual. */
    public function getProximosStatus(): array
    {
        return self::TRANSICOES[$this->status];
    }

    /**
     * Adiciona um serviço à lista.
     * Regras de negócio 17, 18 e 19 (recusadas com Exception).
     */
    public function adicionarServico(Servico $servico): void
    {
        // Regra 17: só aceita serviços enquanto a ordem está aberta ou em andamento.
        if (!in_array($this->status, ['aberta', 'em_andamento'], true)) {
            throw new Exception("Não é possível adicionar serviços a uma ordem {$this->getStatusRotulo()}.");
        }
        // Regra 18: no máximo 5 serviços por ordem.
        if (count($this->servicos) >= self::MAX_SERVICOS) {
            throw new Exception('Uma ordem de serviço aceita no máximo ' . self::MAX_SERVICOS . ' serviços.');
        }
        // Regra 19: não repetir o mesmo tipo de serviço (exceto mão de obra avulsa).
        if ($servico->getTipo() !== 'generico') {
            foreach ($this->servicos as $existente) {
                if ($existente->getTipo() === $servico->getTipo()) {
                    throw new Exception("A ordem já possui um serviço do tipo {$servico->getNomeTipo()}.");
                }
            }
        }
        $this->servicos[] = $servico;
    }

    /** Soma dos serviços. Polimorfismo: cada objeto calcula o seu valor. */
    public function calcularSubtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->servicos as $servico) {
            $subtotal += $servico->calcularValor();
        }
        return round($subtotal, 2);
    }

    /** Desconto de 10% quando a ordem tem 3 ou mais serviços. */
    public function calcularDesconto(): float
    {
        if (count($this->servicos) >= self::MINIMO_PARA_DESCONTO) {
            return round($this->calcularSubtotal() * self::PERCENTUAL_DESCONTO, 2);
        }
        return 0.0;
    }

    public function calcularTotal(): float
    {
        return round($this->calcularSubtotal() - $this->calcularDesconto(), 2);
    }

    public function calcularHoras(): float
    {
        $horas = 0.0;
        foreach ($this->servicos as $servico) {
            $horas += $servico->getHoras();
        }
        return $horas;
    }

    /**
     * Muda o status respeitando o fluxo:
     * aberta -> em_andamento -> finalizada (cancelada a qualquer momento antes do fim).
     * Regras de negócio 20, 21 e 22.
     */
    public function alterarStatus(string $novoStatus): void
    {
        // Regra 20: status precisa existir.
        if (!array_key_exists($novoStatus, self::STATUS_ROTULOS)) {
            throw new Exception('Status inválido.');
        }
        // Regra 21: só são permitidas as transições do fluxo.
        if (!in_array($novoStatus, self::TRANSICOES[$this->status], true)) {
            throw new Exception(
                "Não é possível mudar a ordem de {$this->getStatusRotulo()} para " . self::STATUS_ROTULOS[$novoStatus] . '.'
            );
        }
        // Regra 22: não se inicia nem finaliza uma ordem sem serviços.
        if ($novoStatus !== 'cancelada' && count($this->servicos) === 0) {
            throw new Exception('Adicione pelo menos um serviço antes de avançar a ordem.');
        }
        $this->status = $novoStatus;
    }

    public function paraArray(): array
    {
        $servicos = [];
        foreach ($this->servicos as $servico) {
            $servicos[] = $servico->paraArray();
        }

        return [
            'id' => $this->id,
            'data_abertura' => $this->dataAbertura,
            'status' => $this->status,
            'status_rotulo' => $this->getStatusRotulo(),
            'cliente' => $this->cliente->paraArray(),
            'veiculo' => $this->veiculo->paraArray(),
            'servicos' => $servicos,
            'horas' => $this->calcularHoras(),
            'subtotal' => $this->calcularSubtotal(),
            'desconto' => $this->calcularDesconto(),
            'total' => $this->calcularTotal(),
        ];
    }

    /** Reconstrói a ordem inteira a partir do array salvo no JSON. */
    public static function deArray(array $dados): OrdemServico
    {
        $ordem = new OrdemServico(
            (int) $dados['id'],
            Cliente::deArray($dados['cliente']),
            Veiculo::deArray($dados['veiculo']),
            (string) $dados['data_abertura']
        );

        foreach ($dados['servicos'] as $dadosServico) {
            $classe = match ($dadosServico['tipo'] ?? 'generico') {
                'revisao' => Revisao::class,
                'troca_oleo' => TrocaOleo::class,
                'funilaria' => Funilaria::class,
                default => Servico::class,
            };
            $ordem->adicionarServico($classe::deArray($dadosServico));
        }

        // O status salvo já foi validado quando a ordem foi alterada.
        $ordem->status = (string) $dados['status'];

        return $ordem;
    }
}
