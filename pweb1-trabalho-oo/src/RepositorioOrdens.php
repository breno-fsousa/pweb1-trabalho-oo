<?php
declare(strict_types=1);

require_once __DIR__ . '/OrdemServico.php';

/**
 * Guarda as ordens de serviço em um arquivo JSON (bônus de persistência).
 * Formato do arquivo: {"ordens": [ {...}, {...} ]}
 */
class RepositorioOrdens
{
    private string $arquivo;

    public function __construct(string $arquivo)
    {
        $this->arquivo = $arquivo;
    }

    /** @return OrdemServico[] */
    public function todas(): array
    {
        $ordens = [];
        foreach ($this->lerDados() as $dados) {
            $ordens[] = OrdemServico::deArray($dados);
        }
        return $ordens;
    }

    public function buscar(int $id): ?OrdemServico
    {
        foreach ($this->todas() as $ordem) {
            if ($ordem->getId() === $id) {
                return $ordem;
            }
        }
        return null;
    }

    public function proximoId(): int
    {
        $maior = 0;
        foreach ($this->lerDados() as $dados) {
            $maior = max($maior, (int) $dados['id']);
        }
        return $maior + 1;
    }

    /** Insere a ordem ou substitui a que tem o mesmo id. */
    public function salvar(OrdemServico $ordem): void
    {
        $lista = $this->lerDados();
        $substituiu = false;
        foreach ($lista as $i => $dados) {
            if ((int) $dados['id'] === $ordem->getId()) {
                $lista[$i] = $ordem->paraArray();
                $substituiu = true;
            }
        }
        if (!$substituiu) {
            $lista[] = $ordem->paraArray();
        }
        $this->gravarDados($lista);
    }

    private function lerDados(): array
    {
        if (!file_exists($this->arquivo)) {
            return [];
        }
        $conteudo = file_get_contents($this->arquivo);
        if ($conteudo === false || trim($conteudo) === '') {
            return [];
        }
        try {
            $dados = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('O arquivo de dados está corrompido: ' . $e->getMessage());
        }
        return $dados['ordens'] ?? [];
    }

    private function gravarDados(array $lista): void
    {
        $pasta = dirname($this->arquivo);
        if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            throw new Exception('Não foi possível criar a pasta de dados.');
        }
        $json = json_encode(
            ['ordens' => array_values($lista)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($json === false || file_put_contents($this->arquivo, $json, LOCK_EX) === false) {
            throw new Exception('Não foi possível gravar o arquivo de dados.');
        }
    }
}
