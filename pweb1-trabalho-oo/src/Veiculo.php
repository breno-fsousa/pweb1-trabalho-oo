<?php
declare(strict_types=1);

/**
 * Veículo que está na oficina.
 */
class Veiculo
{
    private string $placa;
    private string $marca;
    private string $modelo;
    private int $ano;
    private int $quilometragem;

    public function __construct(string $placa, string $marca, string $modelo, int $ano, int $quilometragem)
    {
        $this->setPlaca($placa);
        $this->setMarca($marca);
        $this->setModelo($modelo);
        $this->setAno($ano);
        $this->setQuilometragem($quilometragem, true);
    }

    public function getPlaca(): string
    {
        return $this->placa;
    }

    public function getMarca(): string
    {
        return $this->marca;
    }

    public function getModelo(): string
    {
        return $this->modelo;
    }

    public function getAno(): int
    {
        return $this->ano;
    }

    public function getQuilometragem(): int
    {
        return $this->quilometragem;
    }

    // Regra de negócio 3: placa no padrão antigo (ABC1234) ou Mercosul (ABC1D23).
    public function setPlaca(string $placa): void
    {
        $placa = strtoupper(str_replace(['-', ' '], '', trim($placa)));
        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa)) {
            throw new Exception('Placa inválida. Use ABC-1234 ou ABC1D23.');
        }
        $this->placa = $placa;
    }

    public function setMarca(string $marca): void
    {
        $marca = trim($marca);
        if ($marca === '') {
            throw new Exception('Informe a marca do veículo.');
        }
        $this->marca = $marca;
    }

    public function setModelo(string $modelo): void
    {
        $modelo = trim($modelo);
        if ($modelo === '') {
            throw new Exception('Informe o modelo do veículo.');
        }
        $this->modelo = $modelo;
    }

    // Regra de negócio 4: ano entre 1980 e o ano que vem.
    public function setAno(int $ano): void
    {
        $limite = (int) date('Y') + 1;
        if ($ano < 1980 || $ano > $limite) {
            throw new Exception("O ano do veículo deve estar entre 1980 e {$limite}.");
        }
        $this->ano = $ano;
    }

    // Regra de negócio 5: a quilometragem não pode ser negativa nem diminuir.
    public function setQuilometragem(int $km, bool $primeiraVez = false): void
    {
        if ($km < 0) {
            throw new Exception('A quilometragem não pode ser negativa.');
        }
        if (!$primeiraVez && $km < $this->quilometragem) {
            throw new Exception('A quilometragem não pode ser menor que a já registrada.');
        }
        $this->quilometragem = $km;
    }

    public function getIdade(): int
    {
        return (int) date('Y') - $this->ano;
    }

    public function getDescricao(): string
    {
        return "{$this->marca} {$this->modelo} {$this->ano}";
    }

    public function paraArray(): array
    {
        return [
            'placa' => $this->placa,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'ano' => $this->ano,
            'quilometragem' => $this->quilometragem,
        ];
    }

    public static function deArray(array $dados): Veiculo
    {
        return new Veiculo(
            (string) $dados['placa'],
            (string) $dados['marca'],
            (string) $dados['modelo'],
            (int) $dados['ano'],
            (int) $dados['quilometragem']
        );
    }
}
