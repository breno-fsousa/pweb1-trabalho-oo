<?php
declare(strict_types=1);

/**
 * Dono do veículo que chega à oficina.
 */
class Cliente
{
    private string $nome;
    private string $telefone;

    public function __construct(string $nome, string $telefone)
    {
        $this->setNome($nome);
        $this->setTelefone($telefone);
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getTelefone(): string
    {
        return $this->telefone;
    }

    // Regra de negócio 1: o nome precisa ter pelo menos 3 caracteres.
    public function setNome(string $nome): void
    {
        $nome = trim($nome);
        if ((int) preg_match_all('/./us', $nome) < 3) {
            throw new Exception('O nome do cliente precisa ter pelo menos 3 caracteres.');
        }
        $this->nome = $nome;
    }

    // Regra de negócio 2: telefone com DDD (10 ou 11 dígitos).
    public function setTelefone(string $telefone): void
    {
        $digitos = preg_replace('/\D/', '', $telefone) ?? '';
        if (strlen($digitos) !== 10 && strlen($digitos) !== 11) {
            throw new Exception('O telefone deve ter DDD e 10 ou 11 dígitos.');
        }
        $this->telefone = $digitos;
    }

    /** Telefone formatado para exibição: (88) 99999-9999 */
    public function getTelefoneFormatado(): string
    {
        $d = $this->telefone;
        $corte = strlen($d) - 4;
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, $corte - 2), substr($d, $corte));
    }

    public function paraArray(): array
    {
        return [
            'nome' => $this->nome,
            'telefone' => $this->telefone,
        ];
    }

    public static function deArray(array $dados): Cliente
    {
        return new Cliente((string) $dados['nome'], (string) $dados['telefone']);
    }
}
