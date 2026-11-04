# Diário de uso de LLM

**Projeto:** MotorPrime – Sistema Web de Oficina Mecânica  
**Ferramenta utilizada:** Claude AI

## Interação 1 – Planejamento e estrutura do sistema

- **Ferramenta e data:** Claude AI – 30/10/2026.
- **Prompt:**

```text
Estou desenvolvendo um sistema básico de oficina mecânica em PHP para um trabalho de Programação Web I. O sistema deve ser orientado a objetos e possuir cadastro de clientes, veículos, serviços e ordens de serviço. Me ajude a organizar as classes e explicar a função de cada uma de forma simples.
```

- **Resumo da resposta:** O Claude sugeriu separar o sistema em classes, como Cliente, Veiculo, Servico e OrdemServico. Também explicou a responsabilidade de cada classe e como elas poderiam se relacionar.
- **O que aceitei, modifiquei ou rejeitei, e por quê:** Aceitei a ideia de separar as funcionalidades em classes, pois isso deixa o código mais organizado e facilita a manutenção do sistema.

## Interação 2 – Criação de classes e encapsulamento

- **Ferramenta e data:** Claude AI – 30/10/2026.
- **Prompt:**

```text
Explique como criar uma classe Cliente em PHP utilizando orientação a objetos. Quero atributos privados, construtor, getters e setters com exemplos simples para entender.
```

- **Resumo da resposta:** O Claude apresentou um exemplo de classe Cliente com atributos privados, construtor e métodos para consultar e alterar informações.
- **O que aceitei, modifiquei ou rejeitei, e por quê:** Aceitei a estrutura apresentada e utilizei como referência para organizar minhas classes. Mantive os atributos privados para atender ao requisito de encapsulamento do trabalho.

## Interação 3 – Herança e polimorfismo

- **Ferramenta e data:** Claude AI – 31/10/2026.
- **Prompt:**

```text
Preciso implementar herança e polimorfismo no meu sistema de oficina mecânica em PHP. O tema possui serviços de revisão, troca de óleo e funilaria. Mostre um exemplo simples de uma classe mãe Servico e duas classes filhas, explicando extends, parent::__construct e sobrescrita de métodos.
```

- **Resumo da resposta:** O Claude explicou como criar uma classe principal e classes filhas utilizando herança. Também mostrou como utilizar o mesmo método em objetos diferentes para demonstrar o polimorfismo.
- **O que aceitei, modifiquei ou rejeitei, e por quê:** Aproveitei a explicação e adaptei a ideia para os serviços da oficina. Escolhi essa estrutura porque ela atende aos requisitos de herança e polimorfismo solicitados pelo professor.

## Interação 4 – Regras de negócio e tratamento de erros

- **Ferramenta e data:** Claude AI – 02/11/2026.
- **Prompt:**

```text
Como implementar regras de negócio em PHP utilizando throw new Exception e try/catch em um sistema de oficina mecânica? Dê exemplos simples, como impedir uma ordem de serviço com valor negativo ou sem descrição.
```

- **Resumo da resposta:** O Claude explicou como utilizar exceções para impedir dados inválidos e como tratar esses erros utilizando try/catch, apresentando mensagens para informar o usuário.
- **O que aceitei, modifiquei ou rejeitei, e por quê:** Aceitei a utilização de exceções para validar os dados. Adaptei os exemplos às regras do meu sistema, pois é importante impedir que informações incorretas sejam cadastradas.

## Interação 5 – Execução do sistema e camada web

- **Ferramenta e data:** Claude AI – 03/11/2026.
- **Prompt:**

```text
Estou desenvolvendo um sistema de oficina mecânica em PHP. Como posso organizar a página inicial, criar páginas utilizando query string, disponibilizar os dados em JSON e iniciar o servidor local utilizando a pasta public? Explique de maneira simples.
```

- **Resumo da resposta:** O Claude explicou como organizar a camada web, utilizar parâmetros na URL, configurar uma resposta JSON com json_encode e iniciar o servidor PHP pelo terminal do VS Code.
- **O que aceitei, modifiquei ou rejeitei, e por quê:** Aceitei as orientações e utilizei o comando php -S localhost:8000 -t public para iniciar o servidor. Também compreendi melhor como o navegador solicita a página inicial e o arquivo CSS.

## O erro que a LLM cometeu

Durante a interação sobre orientação a objetos, o Claude apresentou inicialmente um exemplo de classe utilizando atributos públicos.

Percebi que essa sugestão não estava de acordo com o requisito OO4 do trabalho, que determina que nenhum atributo pode ser public.

Para corrigir, utilizei atributos privados (`private`) e métodos getters e setters para acessar e modificar os dados de maneira controlada.

Essa correção foi importante porque me ajudou a compreender melhor o conceito de encapsulamento e a seguir os requisitos estabelecidos pelo professor.

## Considerações finais

A utilização do Claude AI ajudou principalmente na compreensão dos conceitos de programação orientada a objetos e na organização do sistema MotorPrime. A ferramenta também auxiliou na interpretação de comandos e na resolução de dúvidas durante o desenvolvimento.

Apesar da ajuda, foi necessário analisar as sugestões e realizar adaptações para atender aos requisitos do trabalho. Com isso, consegui compreender melhor a estrutura das classes, o tratamento de erros e o funcionamento de um sistema web em PHP.