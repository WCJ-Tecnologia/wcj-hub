# Levantamento: modelos enviados pelo Rafael

Recebidos em 24/09/2026, depois da reunião de Projetos. Os originais ficam fora do git (conteúdo de projeto de cliente e nomes de terceiros). Aqui fica só a **estrutura**, que vira modelo do sistema.

## 1. Documento de Requisição (SGM-13, SGM-17, SGM-22)

Origem: projeto de gestão de multas. O identificador `SGM-NN` é a chave da tarefa dentro do projeto (prefixo do projeto + número sequencial).

### Estrutura

| Bloco | Conteúdo | Exemplo (resumido) |
|---|---|---|
| História de usuário | **Sendo** <papel> / **Quero** <capacidade> / **Para** <objetivo> | "Sendo um usuário do módulo financeiro, quero o cadastro de fornecedores, para lançar contas a pagar" |
| Roteiro de aceite | Lista numerada `CA_1 … CA_n`, com subitens (`CA_4.1`) | Menu, submenu, listagem, botão de criar, campos, mensagens, filtros, exportação, paginação, privilégio |
| Informações adicionais | Imagem (protótipo ou print) | — |
| Histórico de versões | Alterações do documento | — |
| Referência cruzada | Critério aponta para outra tarefa | `CA_10 … (SGM-21)` |

### O que se repete nos critérios de aceite

Os três documentos são CRUD e repetem os mesmos critérios:

- menu e submenu onde a tela fica;
- listagem com colunas definidas;
- botão "Ações > Criar/Cadastrar";
- tela de cadastro com **cada campo, tipo, máscara, obrigatoriedade e chave única**;
- mensagem de sucesso e de erro com o texto exato;
- link "Retornar listagem";
- botão editar;
- filtros com os campos de busca;
- exportação para Excel/CSV;
- paginação;
- privilégio de acesso.

### Implicações para o sistema

1. **Modelo de tarefa = este documento.** Campos estruturados: papel, quero, para, lista de critérios, anexos de imagem, histórico. O documento Word/PDF é **gerado** a partir desses campos (F-PRJ-13), não escrito à parte.
2. **Critério de aceite vira item de teste** (F-PRJ-14): cada `CA_n` gera uma caixa de marcar no roteiro do QA, com a evidência presa ao item (F-PRJ-07).
3. **Bloco CRUD reutilizável:** como os critérios de CRUD se repetem, o modelo oferece um conjunto padrão (listagem, criar, editar, filtros, exportar, paginação, privilégio) que o gestor ajusta. Casa com o gabarito de CRUD do padrão (`stacks/ui.md`).
4. **Chave por projeto** (`SGM-17`): cada projeto tem um prefixo e a numeração é sequencial por projeto, feita por sequência do banco (F-PRJ-20).
5. **Formato para IA:** o documento vai ser lido pela IA do dev. O conteúdo (campo, regra, mensagem) importa mais que a formatação. `CA_n` já é verificável um a um; o escopo do padrão pede Dado/Quando/Então, e os dois formatos convivem: `CA_n` descreve a tela, Dado/Quando/Então descreve a regra.

## 2. Histórico de Desenvolvimento (plataforma Tecsolution)

PDF de 7 páginas, montado à mão pelo Rafael. É o modelo do relatório de linha do tempo para o cliente (F-PRJ-15).

### Estrutura

| Página | Conteúdo |
|---|---|
| Capa | Nome da plataforma + "Histórico de Desenvolvimento" + data |
| Processo | Etapas do processo de desenvolvimento: refinamento → protótipo → aprovação do protótipo → desenvolvimento → homologação (ajustes, correções, testes finais) → liberação para produção |
| Fases (1 a 4) | Uma página por fase, com o **período** (ex.: 24/10/22 a 28/12/22). Linha do tempo com **marcos datados**; cada marco tem um título (ex.: "Localização / Rastreamento") e os itens entregues em tópicos. Rodapé com a **equipe** dos dois lados (cliente e WCJ) |
| Funcionalidades | Resumo por área do produto (Localização, Upload, Integrações, Segurança, Cadastros…), com os itens de cada área |

### Implicações para o sistema

1. **Fase** é um agrupador de versões ou sprints com período. [LEVANTAR com Rafael] se fase é o mesmo que sprint/versão ou um nível acima.
2. **Marco = versão ou entrega datada.** Título + itens entregues: sai das tarefas prontas daquela versão (F-PRJ-03).
3. **Área do produto** (RN-PRJ-07) alimenta a página de funcionalidades por área.
4. **Equipe por fase**, dos dois lados: o projeto registra os participantes do cliente e da WCJ, com período.
5. **Etapas do processo** mostram um fluxo de projeto de cliente com **refinamento e protótipo aprovados antes do desenvolvimento**, diferente do quadro Wpay. Confirma o fluxo configurável por projeto (D-PRJ-01).
6. Saída em PDF na identidade WCJ. A estética fica com o gerador; os dados vêm do sistema.
