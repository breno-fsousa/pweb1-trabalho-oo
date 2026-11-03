<?php
declare(strict_types=1);

require_once __DIR__ . '/Servico.php';

/**
 * Revisão preventiva: soma ao valor da mão de obra a taxa do checklist.
 */
class Revisao extends Servico
{
    private const TAXA_CHECKLIST = [
        'basica' => 80.0,
        'completa' => 220.0,
    ];
    private const HORAS_MINIMAS = [
        'basica' => 1.0,
        'completa' => 3.0,
    ];
    private const INTERVALO_KM = 10000;

    private int $kmAtual;
    private string $tipoRevisao;

    public function __construct(
        string $descricao,
        float $horas,
        float $valorHora,
        int $kmAtual,
        string $tipoRevisao
    ) {
        parent::__construct($descricao, $horas, $valorHora);

        // Regra de negócio 9: tipo de revisão conhecido.
        if (!array_key_exists($tipoRevisao, self::TAXA_CHECKLIST)) {
            throw new Exception('O tipo de revisão deve ser "basica" ou "completa".');
        }
        // Regra de negócio 10: quilometragem da revisão válida.
        if ($kmAtual < 0 || $kmAtual > 1000000) {
            throw new Exception('A quilometragem da revisão deve estar entre 0 e 1.000.000 km.');
        }
        // Regra de negócio 11: a revisão completa exige mais tempo de oficina.
        $minimo = self::HORAS_MINIMAS[$tipoRevisao];
        if ($horas < $minimo) {
            throw new Exception(
                sprintf('A revisão %s exige pelo menos %s h de mão de obra.', $tipoRevisao, number_format($minimo, 1, ',', '.'))
            );
        }

        $this->kmAtual = $kmAtual;
        $this->tipoRevisao = $tipoRevisao;
    }

    public function getKmAtual(): int
    {
        return $this->kmAtual;
    }

    public function getTipoRevisao(): string
    {
        return $this->tipoRevisao;
    }

    public function getProximaRevisaoKm(): int
    {
        return $this->kmAtual + self::INTERVALO_KM;
    }

    public function getTipo(): string
    {
        return 'revisao';
    }

    public function getNomeTipo(): string
    {
        return 'Revisão';
    }

    public function calcularValor(): float
    {
        return round(parent::calcularValor() + self::TAXA_CHECKLIST[$this->tipoRevisao], 2);
    }

    public function descricaoDetalhada(): string
    {
        return sprintf(
            'Revisão %s aos %s km: %s. Próxima revisão em %s km.',
            $this->tipoRevisao,
            number_format($this->kmAtual, 0, ',', '.'),
            $this->descricao,
            number_format($this->getProximaRevisaoKm(), 0, ',', '.')
        );
    }

    public function paraArray(): array
    {
        return array_merge(parent::paraArray(), [
            'km_atual' => $this->kmAtual,
            'tipo_revisao' => $this->tipoRevisao,
            'proxima_revisao_km' => $this->getProximaRevisaoKm(),
        ]);
    }

    public static function deArray(array $dados): Revisao
    {
        return new Revisao(
            (string) $dados['descricao'],
            (float) $dados['horas'],
            (float) $dados['valor_hora'],
            (int) $dados['km_atual'],
            (string) $dados['tipo_revisao']
        );
    }
}
