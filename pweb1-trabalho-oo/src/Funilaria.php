<?php
declare(strict_types=1);

require_once __DIR__ . '/Servico.php';

/**
 * Funilaria: mão de obra + material por painel + pintura opcional.
 */
class Funilaria extends Servico
{
    private const MATERIAL_PAINEL = 350.0;
    private const PINTURA_PAINEL = 420.0;
    private const HORAS_POR_PAINEL = 2.0;

    private int $paineis;
    private bool $pintura;

    public function __construct(
        string $descricao,
        float $horas,
        float $valorHora,
        int $paineis,
        bool $pintura
    ) {
        parent::__construct($descricao, $horas, $valorHora);

        // Regra de negócio 15: entre 1 e 8 painéis por serviço.
        if ($paineis < 1 || $paineis > 8) {
            throw new Exception('A funilaria deve atender entre 1 e 8 painéis.');
        }
        // Regra de negócio 16: cada painel exige pelo menos 2 h de mão de obra.
        $minimo = $paineis * self::HORAS_POR_PAINEL;
        if ($horas < $minimo) {
            throw new Exception(
                sprintf('Para %d painel(is) são necessárias pelo menos %s h de mão de obra.', $paineis, number_format($minimo, 1, ',', '.'))
            );
        }

        $this->paineis = $paineis;
        $this->pintura = $pintura;
    }

    public function getPaineis(): int
    {
        return $this->paineis;
    }

    public function temPintura(): bool
    {
        return $this->pintura;
    }

    public function getTipo(): string
    {
        return 'funilaria';
    }

    public function getNomeTipo(): string
    {
        return 'Funilaria';
    }

    public function calcularValor(): float
    {
        $valor = parent::calcularValor() + $this->paineis * self::MATERIAL_PAINEL;
        if ($this->pintura) {
            $valor += $this->paineis * self::PINTURA_PAINEL;
        }
        return round($valor, 2);
    }

    public function descricaoDetalhada(): string
    {
        return sprintf(
            'Funilaria em %d painel(is) %s: %s.',
            $this->paineis,
            $this->pintura ? 'com pintura' : 'sem pintura',
            $this->descricao
        );
    }

    public function paraArray(): array
    {
        return array_merge(parent::paraArray(), [
            'paineis' => $this->paineis,
            'pintura' => $this->pintura,
        ]);
    }

    public static function deArray(array $dados): Funilaria
    {
        return new Funilaria(
            (string) $dados['descricao'],
            (float) $dados['horas'],
            (float) $dados['valor_hora'],
            (int) $dados['paineis'],
            (bool) $dados['pintura']
        );
    }
}
