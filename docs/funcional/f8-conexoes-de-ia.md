# F8. Conexões de IA e as 6 ferramentas

**Documento funcional.** Versão 1.0, de 06/10/2026. **Aprovado por Lucas Scholz em 06/10/2026**, com os pontos a validar respondidos (seção 10). Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Deixar cada pessoa conectar o próprio assistente de IA (Claude, Codex ou ChatGPT) ao Hub, ver e desligar essa conexão, e usar pela conversa seis ferramentas que fazem o mesmo que a tela, com as mesmas permissões. Dar ao Administrador a visão e o corte de todas as conexões. |
| **Regras que alimentam** | G3, G4, N4, IA1 a IA14 (`docs/necessidade.md`). As ferramentas repetem regras de Projetos: P7, P13, P15, P17, P24, P30, P39, P45, P52, P53 e P56. |
| **Tabelas do `banco.md`** | `ai_assistants`, `ai_authorizations`, `audit_logs`, tabelas do Passport (`oauth_clients`, `oauth_access_tokens`, `oauth_refresh_tokens`, `oauth_auth_codes`). As ferramentas leem e gravam pelas tabelas de F3, F5 e F7. |
| **Depende de** | [F1](f1-nucleo.md) (usuários, papéis, auditoria), [F3](f3-quadro-e-card.md) (card, movimento, comentário), [F5](f5-sprint-e-horas.md) (horas), [F7](f7-indicadores.md) (situação do projeto) e a prova de conceito do MCP (D-GER-17). |
| **Quem aprova** | Lucas Scholz (dono da Interface de IA, D-GER-07). |

Três palavras aparecem no assistente e nesta tela. **MCP** é o padrão de conector que deixa o assistente usar as ferramentas do Hub. **OAuth** é o login no Hub feito a partir do assistente, sem colar senha nem chave. **Escopo** é o que a conexão pode fazer em cada módulo: ler, ou ler e escrever.

## 01. Fluxo funcional

**Conectar (IA4, IA5).**

1. A pessoa abre *Perfil → Conexões de IA*. Há um cartão por assistente: Claude (app claude.ai), Claude Code (terminal, IDE ou desktop), Codex (terminal, IDE ou app) e ChatGPT (plano Pro, só leitura; condicional à parte 2 da prova) (IA3, D-GER-30).
2. Clica em *Conectar* no cartão. O Hub mostra o endereço do conector do Hub e onde colá-lo no assistente: no Claude, "Customize → Connectors"; no Codex, o comando de adicionar o servidor e o de login; no ChatGPT Pro, o modo desenvolvedor. Não há chave para copiar (IA4).
3. No assistente, a pessoa adiciona o conector com esse endereço. O assistente abre a página de login do Hub. Quem ainda não entrou no Hub naquele navegador entra com o e-mail e a senha de sempre.
4. O Hub mostra a tela de autorização: o nome do assistente, a pessoa e o que a conexão vai poder fazer em cada módulo, limitado pelo papel dela (IA5). Se já existe conexão daquele assistente, a tela avisa que ela será substituída (IA6).
5. *Autorizar* devolve a pessoa ao assistente, já conectado, e o cartão passa a "Conectado desde DD/MM/AAAA HH:MM". *Recusar* não cria nada.
6. Daí em diante a pessoa pede na conversa ("o que eu tenho para hoje?") e o assistente chama a ferramenta. Antes de uma escrita, o assistente pede confirmação (IA9).

**Reconectar.** Conectar de novo o mesmo assistente revoga a conexão anterior e cria outra (IA6). É também o jeito de mudar o que a conexão pode: não existe editar uma conexão.

**Desconectar.** No cartão, *Desconectar*. O assistente perde o acesso na hora; a próxima chamada recebe "não autorizado" (IA6). A conexão revogada fica na auditoria (G3).

**Desligamento automático.** Usuário desativado ou com papel trocado perde todas as conexões no mesmo instante (N4, IA6). Reativar a pessoa não devolve as conexões: ela conecta de novo (F1).

**Administrador.** Em *Administração → Conexões de IA*, o Administrador vê as conexões de todo mundo e revoga qualquer uma (IA6).

**O que não muda.** O Hub não chama IA nenhuma por conta própria: a IA é a do usuário, paga por ele (IA2). Só usuários internos conectam (IA1). A IA age como a pessoa, com as permissões dela na tela (G4, IA5).

**Fora da fatia 1.** O GPT "WCJ Hub" do comercial e as ferramentas de CRM (v2, D-GER-24); as nove ferramentas de Projetos e Núcleo que vêm com uso real (IA14); a tela de consulta das chamadas da IA (IA12).

## 02. Telas e campos

### Cartão do assistente (*Perfil → Conexões de IA*)

| Campo | O que mostra | Regra |
|---|---|---|
| Assistente | Claude (app); Claude Code; Codex (terminal, IDE ou app); ChatGPT (plano Pro). | (IA3, D-GER-30) |
| Situação | "Conectado desde DD/MM/AAAA HH:MM" ou "Não conectado". | (IA6) |
| O que pode | Por módulo: "Núcleo: ler", "Projetos: ler e escrever" ou "Projetos: ler". Só quando conectado. | (IA4, IA5) |
| Observação | No ChatGPT: "Só leitura. O ChatGPT Plus não conecta." | (IA3) |

### Tela de autorização (aberta pelo assistente)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Assistente | só leitura | | | Como o assistente se identifica, conferido na prova de conceito (ver 10.2). |
| Pessoa | só leitura: nome e e-mail | | | A conexão age como ela (IA5). |
| Núcleo | só leitura: "ler" | | | Todos os papéis (IA5; ver 10.4). |
| Projetos | só leitura: "ler e escrever" ou "só ler" | | o máximo permitido pelo papel e pela marca "IA pode escrever" do cadastro da pessoa (F1) | A pessoa não escolhe: quem decide é o Administrador, no cadastro (IA4). No ChatGPT, sempre "ler" (IA3). O Comercial não tem a linha (C14). |
| Aviso de substituição | texto | | | "Isto substitui a conexão do Claude feita em DD/MM/AAAA." (IA6) |

O que cada papel pode autorizar (IA5). A permissão da conexão nunca passa do papel:

| Papel | Núcleo | Projetos |
|---|---|---|
| Administrador | ler | ler e escrever |
| Gestor de projetos | ler | ler e escrever |
| Desenvolvedor | ler | ler e escrever |
| Comercial | ler | nada |

No ChatGPT, tudo vira só leitura, e quem garante é o Hub, não o ChatGPT (IA3). O mesmo vale para quem o Administrador marcou como "IA só consulta": qualquer assistente dessa pessoa recebe só leitura (IA4); mudar a marca derruba as conexões dela, como trocar o papel (IA6). "Escrever em Projetos" não libera tudo: cada ferramenta confere de novo o papel e a alocação, como a tela (G4).

### Conexões de IA (*Administração*)

Lista de todas as conexões, com as colunas da seção 03 e a ação *Revogar*.

## 03. Listagem

| Tela | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| *Perfil → Conexões de IA* | Os três cartões da seção 02 | Claude, Codex, ChatGPT | Sempre os três cartões; não há lista vazia. |
| *Administração → Conexões de IA* | Pessoa, Papel, Assistente, O que pode, Conectada em, Situação (Ativa / Revogada em DD/MM/AAAA por Fulano) | Conexão mais recente primeiro | Revogadas ficam escondidas; o filtro *Mostrar revogadas* as traz, em cinza. Revogação automática mostra o motivo: "usuário desativado", "papel trocado" ou "nova conexão". Vazia: "Nenhuma conexão de IA ativa". |
| Respostas das ferramentas de leitura | As da seção 04, em cada ferramenta | A de cada ferramenta | Em páginas, com o total, para não estourar a conversa. Lista vazia volta com uma frase, ex.: "Nenhuma tarefa sua com esses filtros". |

## 04. Ações

### Na tela

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Conectar | A própria pessoa | Mostra o endereço do conector e o caminho em cada assistente. A conexão nasce no assistente (IA4). | |
| Autorizar | A própria pessoa | Cria a conexão com o que foi escolhido; revoga a anterior do mesmo assistente (IA6). | Aviso de substituição, se houver conexão. |
| Recusar | A própria pessoa | Não cria nada; o assistente recebe a recusa. | |
| Desconectar | A própria pessoa | Revoga a conexão agora (IA6). | "O Claude perde o acesso ao Hub agora. Para voltar, conecte de novo. Continuar?" |
| Revogar | Administrador | Revoga a conexão de qualquer pessoa (IA6). | "A conexão do Claude de Fulano deixa de funcionar agora. Continuar?" |
| Revogação automática | O sistema | Ao desativar a pessoa ou trocar o papel dela (N4, IA6). | O aviso sai em F1, na troca de papel. |

### Regras que valem para toda ferramenta

- **A IA age como a pessoa (G4, IA5).** Cada ferramenta passa pela mesma permissão e faz a mesma ação da tela. O que a pessoa não vê, o assistente dela não vê nem altera.
- **Uma coisa por escrita (IA8).** Cada escrita altera um card, um comentário ou um lançamento. Não há alteração em lote, exclusão pela IA nem consulta livre: para dez cards, são dez chamadas.
- **Leitura ou escrita marcada (IA9).** O assistente sabe quais ferramentas escrevem e pede confirmação antes. A segurança não depende disso: o Hub confere tudo.
- **Texto como dado (IA10).** O texto livre que volta para a IA, como o título de um card, vem marcado como conteúdo, nunca como instrução (ver 10.8).
- **Limite (IA7).** Cada conexão faz até 60 leituras e 20 escritas por minuto. Ao estourar, a IA recebe uma mensagem legível; a tela da pessoa e os outros assistentes dela seguem normais. O valor é ajustado pela equipe técnica, sem tela.
- **Auditoria (IA11, G3).** Toda escrita pela IA fica na auditoria com o canal (Claude, Codex ou ChatGPT) e aparece também na linha do tempo do card (F7). Tentativa negada por permissão ou por escopo também fica.
- **Leituras (IA12).** Toda chamada, inclusive de leitura, fica num registro do servidor por 180 dias, só com o que foi pedido, sem o conteúdo devolvido. Não há tela: a equipe técnica consulta quando precisa investigar.
- **Recusa legível.** Toda recusa volta em frase curta, que a IA repete para a pessoa (seção 07).

### As 6 ferramentas (IA14)

| Ferramenta | Tipo | Faz o mesmo que |
|---|---|---|
| `minhas_tarefas` | leitura | A aba *Minhas tarefas e horas* de F7, juntando os projetos da pessoa. |
| `status_do_projeto` | leitura | A aba *Situação* de F7, sem as pausas. |
| `escrever_tarefa` | escrita | *Novo card* no quadro (F3). |
| `mover_tarefa` | escrita | Arrastar o card de etapa (F3). |
| `comentar_tarefa` | escrita | Comentar no card (F3). |
| `lancar_horas` | escrita | Lançar horas no card (F5). |

#### `minhas_tarefas` (leitura)

- **Para que serve:** "O que eu tenho para fazer hoje?", "Quais tarefas minhas estão atrasadas no SGM?".
- **O que a pessoa informa:** nada é obrigatório. Opcionais: o projeto (prefixo ou nome) e o prazo (atrasadas; vencem até uma data; sem data prevista).
- **O que volta:** as tarefas abertas em que a pessoa é dev ou QA, nos projetos em que está alocada: chave, título, projeto, etapa, papel dela na tarefa, data prevista, atraso descontada a pausa, horas previstas e horas que ela lançou (P53). Atrasadas primeiro. Tarefa pronta ou arquivada não entra.
- **Quem pode:** qualquer pessoa com Projetos para ler. Ela só vê as próprias tarefas.
- **No Hub:** só leitura, com as contas de F7.
- **Recusas:** projeto que a pessoa não vê ou que não existe: "negado: projeto não encontrado no seu acesso"; data em formato errado: "dado inválido"; limite estourado.

#### `status_do_projeto` (leitura)

- **Para que serve:** "Como está o projeto SGM?", "O que está atrasado na Wpay?".
- **O que a pessoa informa:** o projeto (prefixo ou nome). Se o nome bater com mais de um projeto, a ferramenta devolve as opções.
- **O que volta:** quantas tarefas em cada etapa, na ordem do quadro; as atrasadas (chave, título, dev, data prevista, atraso); as próximas datas previstas (chave, título, dev, data) (P53). As pausas ficam para `tarefas_paradas`, depois da fatia 1.
- **Quem pode:** Administrador em todos os projetos; Gestor nos que gere. O Desenvolvedor recebe "negado": nos indicadores ele não vê nada que não seja dele (F7, decisão de 06/10); para ele existe `minhas_tarefas`.
- **No Hub:** só leitura, com as contas de F7.
- **Recusas:** projeto fora do acesso ou inexistente: "negado: projeto não encontrado no seu acesso", com registro na auditoria (G3); Comercial, que não entra em Projetos: também "negado", com registro na auditoria (C14); limite estourado.

#### `escrever_tarefa` (escrita)

- **Para que serve:** "Cria no SGM um card: erro ao exportar o relatório de multas"; "A partir deste escopo, escreve a tarefa do cadastro de motoristas, com os critérios de aceite".
- **O que a pessoa informa:** o projeto e o título (obrigatórios). Opcionais, no modelo de requisição: Sendo, Quero, Para, informações adicionais e a lista de critérios de aceite (P17).
- **O que volta:** a chave do card criado (ex.: SGM-31), o título, a etapa (Backlog), quantos critérios entraram e o link do card no Hub.
- **Quem pode:** qualquer pessoa alocada no projeto, inclusive o dev; o Gestor nos que gere; o Administrador em todos (P56).
- **No Hub:** o mesmo que *Novo card* (F3). O card nasce no Backlog, com tipo Desenvolvimento (decisão de 14/10; F3 §11) e prioridade Normal; critérios numerados CA_1, CA_2… na ordem enviada; movimento de criação com o canal (P17, P14). Tipo, prioridade, área, horas, dev e QA se ajustam na tela (ver 10.6). Um card por chamada (IA8).
- **Recusas:** não alocado: "negado", com registro na auditoria (G4, G3); conexão só de leitura: "escopo insuficiente", nada criado; projeto encerrado: "projeto encerrado não recebe tarefa nova" (P7); título vazio ou com mais de 200 caracteres, "Sendo" com mais de 200, critério vazio: "dado inválido", dizendo qual; limite estourado.

#### `mover_tarefa` (escrita)

- **Para que serve:** "Passa o SGM-17 para Code-review"; "Volta o SGM-17 para Doing: o QA reprovou, regra de negócio"; "Pausa o SGM-20, aguardando acesso do cliente".
- **O que a pessoa informa:** a chave do card e a etapa de destino, pelo nome no quadro. Quando a volta conta como retrabalho, a origem (code review, teste cruzado, QA ou homologação) e o motivo da lista, com texto se for "outro" (P39, P40). Quando vai para a pausa, o motivo, se o projeto pede (P15).
- **O que volta:** o card, de onde saiu, para onde foi, se contou como retrabalho e, na primeira entrada em desenvolvimento, a data prevista calculada (P28).
- **Quem pode:** qualquer pessoa alocada move a partir do To do e a partir do Support (P13, P52). Tirar do Backlog, que é pôr na sprint, e devolver ao Backlog só o Gestor do projeto e o Administrador (P45).
- **No Hub:** o mesmo movimento da tela (F3), com os mesmos efeitos: o movimento fica gravado com o canal (P14, IA11); a volta para desenvolvimento avisa o dev no sistema e por e-mail (P22), limpa a marca do teste cruzado (P34) e devolve o roteiro para "a testar" (P37). O Hub não exige dev, horas nem PR para mover (premissa).
- **Recusas:** não alocado, ou dev tentando pôr na sprint ou devolver ao Backlog: "negado", com registro na auditoria; conexão só de leitura: "escopo insuficiente", nada muda; volta sem origem ou motivo: "esta volta conta como retrabalho: informe a origem e o motivo", com as listas, e nada muda até a IA mandar de novo; pausa sem motivo, quando o projeto pede: o mesmo, com o motivo; etapa que não existe: "dado inválido", com as etapas do quadro; card já na etapa: "o card já está em Doing"; card arquivado: "card arquivado: o gestor desarquiva pela tela"; tirar do Backlog sem sprint aberta: "não há sprint aberta no SGM: abra a sprint pela tela" (ver 10.5); limite estourado.

#### `comentar_tarefa` (escrita)

- **Para que serve:** "Comenta no SGM-17 que o endpoint mudou para /v2"; "Registra no SGM-20 o que eu fiz hoje".
- **O que a pessoa informa:** a chave do card e o texto.
- **O que volta:** a confirmação, com a chave, o autor e a data e hora do comentário.
- **Quem pode:** qualquer pessoa alocada no projeto; o Gestor nos que gere; o Administrador em todos (P24).
- **No Hub:** o mesmo que comentar na tela (F3). O autor é a pessoa; o canal fica na auditoria (IA11). Pela IA não se anexa arquivo.
- **Recusas:** não alocado: "negado", com registro na auditoria; conexão só de leitura: "escopo insuficiente"; texto vazio: "dado inválido"; limite estourado.

#### `lancar_horas` (escrita)

- **Para que serve:** "Lança 3,5 h hoje no SGM-17"; "Ontem trabalhei 6 horas no SGM-20".
- **O que a pessoa informa:** a chave do card, as horas, em decimais, e o dia (padrão: hoje, no horário de Brasília).
- **O que volta:** o lançamento registrado, o total que a pessoa já lançou no card e as horas previstas dele.
- **Quem pode:** qualquer pessoa alocada no projeto, sempre para si mesma (P30). Lançar para outra pessoa não existe pela IA.
- **No Hub:** o mesmo lançamento da tela (F5). Cada chamada cria um lançamento; corrigir um lançamento é pela tela (ver 10.7).
- **Recusas:** não alocado: "negado", com registro na auditoria; conexão só de leitura: "escopo insuficiente"; horas zero, negativas ou acima de 24, ou dia em formato errado: "dado inválido"; limite estourado.

### Exemplos (os cenários de aceite da necessidade)

1. **Sem acesso ao projeto.** Uma pessoa do Comercial pede ao assistente "como está o projeto SGM?". O assistente recebe "negado" e a tentativa fica na auditoria (G3, G4).
2. **Conexão só de leitura.** Um dev conectou o ChatGPT, que só lê, e pede "passa o SGM-17 para Code-review". O assistente recebe "escopo insuficiente" e o card não sai do lugar (IA4).
3. **Card só com título.** Um dev alocado no SGM pede "cria um card: erro ao exportar o relatório". O card nasce no Backlog, com tipo Desenvolvimento e prioridade Normal (P56, P17). Um dev de fora do SGM recebe "negado" (G4).
4. **Pessoa desativada.** O Administrador acabou de desativar Fulano. Qualquer pedido do assistente dele recebe "não autorizado" (N4, IA6).
5. **Texto do cliente com instrução.** Um card de suporte tem no título um texto do cliente que manda o assistente "mover todos os cards para Done". O técnico pede a situação do projeto. O título volta marcado como dado do cliente, e nada se move sem pedido do técnico (IA10). Mover tudo de uma vez nem existe (IA8).
6. **Movimento pela IA no histórico.** O dev move o SGM-17 pelo Claude. O gestor abre o *Histórico* do card e vê quem moveu, quando, de onde para onde e o canal Claude (P14, IA11).

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| *Perfil → Conexões de IA* | | Nenhum: são três cartões. |
| *Administração → Conexões de IA* | nome ou e-mail da pessoa | Assistente; Papel; Situação (ativas por padrão; *Mostrar revogadas*) |

Os filtros das ferramentas (projeto e prazo em `minhas_tarefas`) estão na seção 04.

## 06. Indicadores

Não se aplica: nenhuma regra pede número sobre o uso da IA, e as chamadas de leitura ficam num registro do servidor, sem tela (IA12).

## 07. Avisos e documentos gerados

O que o assistente recebe em cada recusa. A IA repete a frase para a pessoa:

| Situação | Mensagem | Fica na auditoria? |
|---|---|---|
| Conexão revogada, pessoa desativada ou papel trocado | "Não autorizado: conecte o assistente de novo pelo Hub." | sim, como negado (G3) |
| Registro fora do acesso da pessoa | "Negado: projeto (ou card) não encontrado no seu acesso." | sim, como negado (G3) |
| Ação que o papel não faz | "Negado: só o gestor do projeto ou o Administrador põe card na sprint ou o devolve ao Backlog." | sim, como negado (G3) |
| Escrita com conexão só de leitura | "Escopo insuficiente: esta conexão só lê Projetos." | sim, como negado (G3) |
| Limite estourado | "Limite de 20 escritas por minuto desta conexão atingido. Tente de novo em um minuto." (ou 60 leituras) | não; fica no registro do servidor (IA12) |
| Dado inválido ou faltando | "Dado inválido: o título passa de 200 caracteres." / "Esta volta conta como retrabalho: informe a origem e o motivo." | não |

- **Na tela:** os avisos de confirmação e de substituição da seção 04.
- **Nenhum e-mail:** conectar, desconectar e revogar não avisam ninguém por e-mail (P22 lista os únicos e-mails da fatia 1). As escritas pela IA disparam os mesmos avisos que dispariam pela tela, como o e-mail da volta para desenvolvimento (P22).
- Nenhum documento gerado.

## 08. Exportação

Não se aplica na fatia 1: nenhuma regra pede exportação de conexões nem de chamadas da IA.

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Ver, conectar e desconectar as próprias conexões | sim | sim | sim |
| Ver e revogar as conexões de todos | sim | não | não |
| O que a conexão pode | Núcleo: ler; Projetos: ler e escrever | Núcleo: ler; Projetos: ler e escrever | Núcleo: ler; Projetos: ler e escrever |
| `minhas_tarefas` | as próprias | as próprias | as próprias |
| `status_do_projeto` | todos os projetos | nos que gere | não (negado; usa `minhas_tarefas`) |
| `escrever_tarefa`, `comentar_tarefa`, `lancar_horas` | todos os projetos | nos que gere | nos que está alocado (P56, P24, P30) |
| `mover_tarefa` | tudo | tudo nos que gere, inclusive pôr e tirar da sprint | nos que está alocado, a partir do To do e do Support; não põe nem tira da sprint (P13, P45) |

No ChatGPT, qualquer papel só lê (IA3). O Comercial conecta, só com Núcleo para ler, e não entra em Projetos (C14). A IA de cada pessoa tem exatamente o que ela tem na tela (G4).

## 10. Pontos a validar

Respondidos por Lucas Scholz em 06/10/2026 e já aplicados nas seções acima, no F1, na `necessidade.md` (IA4) e no `banco.md` (coluna aditiva em `users`).

1. **Claude app e Claude Code dividem a conexão?** Respondido pela prova de conceito em 06/10 (D-GER-30): **não**, quando o Claude Code entra pelo terminal. O Claude Code é o quarto cartão. O conector do claude.ai aparece sozinho no Claude Code da mesma conta e aí divide a conexão do app; isso continua valendo e não precisa de cartão.
2. **Como cada assistente se identifica.** OK: depende da prova de conceito; assistente não reconhecido é recusado como "não homologado".
3. **Quem escolhe "só ler".** **O Administrador**, no cadastro do usuário (F1): a marca "IA pode escrever" define se a IA da pessoa altera dados ou só consulta (IA4). A tela de autorização não oferece escolha. Mudar a marca derruba as conexões da pessoa (IA6).
4. **"Núcleo: ler" sem ferramenta.** OK: o escopo aparece desde já.
5. **Tirar do Backlog pela IA sem sprint aberta.** Decidido no F3: o Service cria a sprint proposta e segue.
6. **O que `escrever_tarefa` aceita.** OK: título, modelo de requisição e critérios; o resto pela tela.
7. **Corrigir horas pela IA.** OK: cada chamada é um lançamento novo; correção pela tela.
8. **Texto marcado como dado.** OK: todo texto livre devolvido.
9. **Último uso da conexão.** OK: não mostrar na fatia 1.
10. **Limites.** OK: 60/20 por minuto, ajuste técnico sem tela; reavaliar depois do primeiro mês.
