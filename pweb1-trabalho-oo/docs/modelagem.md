# Modelagem: Oficina MotorPrime

```mermaid
classDiagram
    Servico <|-- Revisao
    Servico <|-- TrocaOleo
    Servico <|-- Funilaria
    OrdemServico "1" *-- "*" Servico
    OrdemServico "1" *-- "1" Cliente
    OrdemServico "1" *-- "1" Veiculo
    RepositorioOrdens ..> OrdemServico

    class Servico {
        #string descricao
        #float horas
        #float valorHora
        +getTipo() string
        +getNomeTipo() string
        +calcularValor() float
        +descricaoDetalhada() string
        +paraArray() array
    }
    class Revisao {
        -int kmAtual
        -string tipoRevisao
        +calcularValor() float
        +descricaoDetalhada() string
    }
    class TrocaOleo {
        -float litros
        -string tipoOleo
        +calcularValor() float
        +descricaoDetalhada() string
    }
    class Funilaria {
        -int paineis
        -bool pintura
        +calcularValor() float
        +descricaoDetalhada() string
    }
    class OrdemServico {
        -int id
        -Cliente cliente
        -Veiculo veiculo
        -array servicos
        -string status
        +adicionarServico(Servico servico) void
        +calcularSubtotal() float
        +calcularDesconto() float
        +calcularTotal() float
        +alterarStatus(string novo) void
        +paraArray() array
    }
    class Cliente {
        -string nome
        -string telefone
        +paraArray() array
    }
    class Veiculo {
        -string placa
        -string marca
        -string modelo
        -int ano
        -int quilometragem
        +paraArray() array
    }
    class RepositorioOrdens {
        -string arquivo
        +todas() array
        +buscar(int id) OrdemServico
        +salvar(OrdemServico ordem) void
    }
```

## Justificativas

- **Herança (Servico → Revisao, TrocaOleo, Funilaria):** os três são serviços (relação "é um"),
  compartilham descrição, horas e valor da hora, mas cada um calcula o valor de um jeito
  (taxa de checklist, litros de óleo mais filtro, material e pintura por painel).
- **Composição (OrdemServico tem Servico, Cliente e Veiculo):** os serviços só existem dentro da
  ordem ("tem um"). A ordem calcula subtotal, desconto e total percorrendo a lista.
- **Repositório:** isola a gravação em JSON, para que as classes de domínio não saibam nada de arquivos.
