<?php
declare(strict_types=1);

require_once __DIR__ . '/Servico.php';

/**
 * Troca de óleo: mão de obra + óleo (por litro) + filtro.
 */
class TrocaOleo extends Servico
{
    private const PRECO_LITRO = [
        'mineral' => 28.0,
        'semissintetico' => 42.0,
        'sintetico' => 65.0,
    ];
    private const PRECO_FILTRO = 35.0;

    private float $litros;
    private string $tipoOleo;

    public function __construct(
        string $descricao,
        float $horas,
        float $valorHora,
        float $litros,
        string $tipoOleo
    ) {
        parent::__construct($descricao, $horas, $valorHora);

        // Regra de negócio 12: tipo de óleo conhecido.
        if (!array_key_exists($tipoOleo, self::PRECO_LITRO)) {
            throw new Exception('O tipo de óleo deve ser "mineral", "semissintetico" ou "sintetico".');
        }
        // Regra de negócio 13: o cárter comporta entre 1 e 10 litros.
        if ($litros < 1 || $litros > 10) {
            throw new Exception('A quantidade de óleo deve estar entre 1 e 10 litros.');
        }
        // Regra de negócio 14: troca de óleo é um serviço rápido.
        if ($horas > 2) {
            throw new Exception('A troca de óleo não pode passar de 2 h de mão de obra.');
        }

        $this->litros = $litros;
        $this->tipoOleo = $tipoOleo;
    }

    public function getLitros(): float
    {
        return $this->litros;
    }

    public function getTipoOleo(): string
    {
        return $this->tipoOleo;
    }

    public function getTipo(): string
    {
        return 'troca_oleo';
    }

    public function getNomeTipo(): string
    {
        return 'Troca de óleo';
    }

    public function calcularValor(): float
    {
        $oleo = $this->litros * self::PRECO_LITRO[$this->tipoOleo];
        return round(parent::calcularValor() + $oleo + self::PRECO_FILTRO, 2);
    }

    public function descricaoDetalhada(): string
    {
        return sprintf(
            'Troca de óleo %s (%s L) com filtro: %s.',
            $this->tipoOleo,
            number_format($this->litros, 1, ',', '.'),
            $this->descricao
        );
    }

    public function paraArray(): array
    {
        return array_merge(parent::paraArray(), [
            'litros' => $this->litros,
            'tipo_oleo' => $this->tipoOleo,
        ]);
    }

    public static function deArray(array $dados): TrocaOleo
    {
        return new TrocaOleo(
            (string) $dados['descricao'],
            (float) $dados['horas'],
            (float) $dados['valor_hora'],
            (float) $dados['litros'],
            (string) $dados['tipo_oleo']
        );
    }
}
