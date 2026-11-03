<?php
declare(strict_types=1);

/**
 * Classe mãe da hierarquia de serviços.
 * Sozinha representa uma mão de obra avulsa (tipo "generico").
 * As filhas (Revisao, TrocaOleo, Funilaria) sobrescrevem os métodos
 * getTipo(), getNomeTipo(), calcularValor(), descricaoDetalhada(),
 * paraArray() e deArray().
 */
class Servico
{
    protected string $descricao;
    protected float $horas;
    protected float $valorHora;

    public function __construct(string $descricao, float $horas, float $valorHora = 90.0)
    {
        $descricao = trim($descricao);
        // Regra de negócio 6: descrição obrigatória.
        if ((int) preg_match_all('/./us', $descricao) < 3) {
            throw new Exception('A descrição do serviço precisa ter pelo menos 3 caracteres.');
        }
        // Regra de negócio 7: horas de mão de obra entre 0,5 e 40.
        if ($horas < 0.5 || $horas > 40) {
            throw new Exception('As horas de mão de obra devem estar entre 0,5 e 40.');
        }
        // Regra de negócio 8: valor da hora positivo.
        if ($valorHora <= 0) {
            throw new Exception('O valor da hora de mão de obra deve ser maior que zero.');
        }
        $this->descricao = $descricao;
        $this->horas = $horas;
        $this->valorHora = $valorHora;
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function getHoras(): float
    {
        return $this->horas;
    }

    public function getValorHora(): float
    {
        return $this->valorHora;
    }

    /** Identificador usado no JSON e nos formulários. */
    public function getTipo(): string
    {
        return 'generico';
    }

    /** Nome para exibir ao usuário. */
    public function getNomeTipo(): string
    {
        return 'Mão de obra avulsa';
    }

    /** Valor do serviço. As filhas somam peças e materiais. */
    public function calcularValor(): float
    {
        return round($this->horas * $this->valorHora, 2);
    }

    /** Resumo do que será feito. Cada filha detalha do seu jeito. */
    public function descricaoDetalhada(): string
    {
        return sprintf('%s (%s h de mão de obra)', $this->descricao, number_format($this->horas, 1, ',', '.'));
    }

    public function paraArray(): array
    {
        return [
            'tipo' => $this->getTipo(),
            'nome_tipo' => $this->getNomeTipo(),
            'descricao' => $this->descricao,
            'detalhes' => $this->descricaoDetalhada(),
            'horas' => $this->horas,
            'valor_hora' => $this->valorHora,
            'valor' => $this->calcularValor(),
        ];
    }

    /** Reconstrói o objeto a partir do array salvo no arquivo JSON. */
    public static function deArray(array $dados): Servico
    {
        return new Servico((string) $dados['descricao'], (float) $dados['horas'], (float) $dados['valor_hora']);
    }
}
