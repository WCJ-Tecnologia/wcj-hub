# Regras de negócio do WCJ Hub — para revisão

Tudo o que está decidido hoje, módulo por módulo, num só lugar e numa linguagem só. Tirado da `necessidade.md` em 24/09/2026 (decisões D-, regras RN- e configurações). Nada foi acrescentado. Onde a regra está incompleta ou em conflito, está marcado **[REVISAR]**.

**Como revisar**

- Cada regra tem um número curto (G1, N1, P1, C1, IA1). Responda pelo número: "ok", "muda para …" ou "corta".
- **[REVISAR]** traz uma pergunta logo abaixo da regra. A lista completa está na seção "Pontos para você decidir".
- Entre parênteses fica de onde a regra veio, para rastrear.
- **Fixa** = vale para todo projeto. **Padrão** = vem ligada e o gestor muda por projeto (D-GER-05).
- Depois da sua revisão, as regras da `necessidade.md` são reescritas a partir deste arquivo e o `banco.txt` segue.

---

## Pontos para você decidir

| # | Regra | Pergunta |
|---|---|---|
| R1 | N8 | Avisar quando já existe contato com o mesmo e-mail? |
| R2 | N10 | Renomear "tipo de contrato" para "tipo de serviço", já que contrato saiu da v1? |
| R3 | P9 | Qual critério deixa o projeto "fora do cronograma"? |
| R4 | P26 | O que é "tarefa nunca usada" (a que pode ser excluída)? |
| R5 | P28 | Tarefa sem data prevista antes de `Doing` está bom? E o retrabalho não mexe na data? |
| R6 | P40 | A lista padrão de motivos de retrabalho está validada? |
| R7 | P42 | Os estados do pacote precisam ser editáveis por projeto? |
| R8 | C1 | Existe critério para o lead passar de uma etapa do funil para a outra? |
| R9 | C2 | O lead precisa de valor estimado e de previsão de fechamento? |
| R10 | C2 | Quer uma lista padrão de origens de lead? |
| R11 | C5 | A tratativa precisa dizer com qual contato foi a conversa? |
| R12 | C7 | Compromisso com cliente, sem lead: em que caso acontece na v1? |
| R13 | IA14 | Quem valida a lista de ferramentas da IA de cada módulo? |

---

## 0. Vale para todos os módulos

- **G1** O sistema traz um padrão pronto que o gestor ajusta por projeto. Só é fixo o que protege segurança, histórico e indicadores. (D-GER-05)
- **G2** Nada que já foi usado é excluído: é desativado ou arquivado, com quem e quando. (RN-NUC-02)
- **G3** Toda alteração, em qualquer módulo, fica na auditoria: quem, quando, o antes e o depois, e por onde (tela ou qual IA). Tentativa negada também fica. A auditoria nunca é apagada. (RN-NUC-04, D-NUC-04)
- **G4** O que o papel não vê na tela, a IA dele também não vê nem altera. (RN-IA-01)
- **G5** Dado sem regra que o use não entra no sistema. Volta quando alguém pedir, com o motivo. (D-GER-20)
- **G6** Na v1 não existe: aprovação em dois níveis, ranking ou metas, mensagem ao cliente por WhatsApp, aplicativo nativo (é web responsiva). (O que não pode; Fora da primeira versão)

## 1. Quem usa (papéis da v1)

| Papel | Quem é | Vê e faz | Não vê |
|---|---|---|---|
| Administrador | Sócios | Tudo. Cadastra usuários e clientes e atribui o papel | — |
| Gestor de projetos | Rafael Garcia; Lucas Scholz nos projetos 100% IA | Cria e acompanha os projetos que gere, estima e atribui tarefas, monta sprints, arquiva tarefa | Pipeline comercial e valores de proposta |
| Desenvolvedor | Devs PJ | Projetos em que está alocado, as tarefas deles, lança horas | CRM; projetos em que não está; painel de produtividade |
| Comercial | Equipe comercial (Wilson) | Todos os leads; edita os próprios; converte lead em cliente | Tarefas internas de projeto |

- **Tech Lead** não é papel: é uma função dada por projeto (P6).
- **Suporte** e **contato do cliente** entram com o módulo Chamados.

## 2. Núcleo

- **N1** Usuário é convidado pelo Administrador, com senha provisória trocada no primeiro acesso. (padrão da casa, `core/seguranca.md`)
- **N2** Cada usuário tem um papel, entre os 4 fixos, atribuído pelo Administrador. Não se cria papel novo. (D-NUC-03)
- **N3** Todo usuário interno tem carga diária (ex.: 8 h ou 4 h). Mudar a carga vale dali para a frente. (RN-PRJ-17)
- **N4** Usuário desativado perde o acesso na hora, inclusive as conexões de IA. (RN-NUC-05, RN-IA-05)
- **N5** Cliente brasileiro é identificado pelo CNPJ; CNPJ repetido é recusado. Empresa estrangeira: país + identificador fiscal do país, opcional. Pessoa física não é cliente. (RN-NUC-01, D-NUC-01)
- **N6** Cliente nasce por cadastro direto ou por lead ganho, sempre sem contrato. (D-CRM-10)
- **N7** A própria WCJ é um cliente: é a dona dos projetos internos e dos produtos (ex.: Wpay). (RN-PRJ-01)
- **N8** Contato é um cadastro só, seja pessoa de cliente ou de lead: nome, e-mail e telefone. Quando o lead vira cliente, o contato não é redigitado. (D-CRM-06)
  - **[REVISAR] R1** Hoje nada impede cadastrar a mesma pessoa duas vezes. Proposta: o sistema avisa quando já existe contato com o mesmo e-mail, sem recusar.
- **N9** Contato não tem login na v1. (D-NUC-02)
- **N10** Contrato fica fora da v1. Fica só a lista de tipos (projeto, sustentação, auditoria, alocação de dev, infraestrutura), editável pelo Administrador, que o lead usa para dizer o que está sendo vendido. (D-CRM-10, D-GER-11)
  - **[REVISAR] R2** Sem contrato, essa lista só diz o que o lead está comprando. Renomear para "tipo de serviço"?

## 3. Projetos

### 3.1 Projeto

- **P1** Todo projeto pertence a um cliente e tem um gestor responsável. **Fixa.** (RN-PRJ-01, RN-PRJ-05)
- **P2** Dois tipos de projeto (D-PRJ-29):
  - **projeto de entrega**: tem data de início e data de entrega;
  - **produto contínuo** (ex.: Wpay): só tem data de início, fica em melhoria contínua, e o prazo é o de cada sprint.
- **P3** Cada projeto tem um prefixo (ex.: `SGM`), único no sistema, que não muda depois da primeira tarefa. (RN-PRJ-19, D-PRJ-26)
- **P4** Modalidade tradicional ou 100% IA. Na v1 é só uma marcação: o fluxo é o mesmo. (RN-PRJ-06)
- **P5** O dev vê só os projetos em que está alocado; o gestor, os que gere; o Administrador, todos. **Fixa.** (RN-PRJ-04, RN-PRJ-05)
- **P6** O Tech Lead é definido por projeto, um ou mais. A mesma pessoa pode ser Tech Lead num projeto e dev em outro. (RN-PRJ-24)
- **P7** Projeto encerrado não recebe tarefa nova. Só o gestor ou o Administrador reabre. **Fixa.** (RN-PRJ-03)
- **P8** Mudar a configuração do projeto vale dali para a frente e nunca apaga o que já foi registrado. **Fixa.** (RN-PRJ-29)
- **P9** No projeto de entrega, o sistema mostra quantas sprints cabem até a entrega (dias úteis até a entrega ÷ duração da sprint) e avisa quando o projeto está **fora do cronograma**. (RN-PRJ-33)
  - **[REVISAR] R3** O sistema precisa de uma regra para pintar o projeto de vermelho. A proposta de hoje: fica vermelho se **(a)** alguma tarefa aberta tem data prevista depois da entrega, ou **(b)** a entrega já passou e ainda tem tarefa aberta.
    Exemplo: entrega em 30/10; a tarefa SGM-17 entrou em `Doing` e ficou com data prevista 03/11 → projeto fora do cronograma.
    Limite: essa regra só enxerga as tarefas que já entraram em `Doing`, porque as do Backlog e do To do ainda não têm data (P28).
    Alternativa: somar as horas que faltam de **todas** as tarefas abertas e comparar com a capacidade do time até a entrega. Pega o atraso mais cedo, mas depende de todas as tarefas estarem estimadas.
    Qual das duas, ou outra?

### 3.2 Quadro e etapas

- **P10** Cada projeto tem o próprio quadro. Padrão: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` e `Support`, em inglês. **Padrão.** (D-PRJ-11, D-PRJ-14, D-PRJ-20)
- **P11** O gestor renomeia, cria, remove e reordena etapas. Toda etapa tem um papel fixo: fila, sprint, desenvolvimento, code review, teste, pronto, pausa ou suporte. Os indicadores leem o papel, não o nome. (RN-PRJ-13, RN-PRJ-30)
- **P12** Para remover uma etapa que tem tarefas, é preciso dizer para onde elas vão. **Fixa.** (RN-PRJ-29)
- **P13** Qualquer pessoa alocada no projeto move a tarefa entre as etapas. Restringir etapa por função fica para depois da v1. (D-PRJ-16, D-PRJ-25)
- **P14** Toda mudança de etapa grava quem, quando, de onde e para onde. **Fixa.** (RN-PRJ-11)
- **P15** Ir para `Paused` exige motivo, e o tempo parado conta à parte. **Padrão** (pode desligar o motivo). (RN-PRJ-12)

### 3.3 Tarefa

- **P16** Chave `<prefixo>-<número>` (ex.: `SGM-17`), sequencial por projeto, nunca reaproveitada. **Fixa.** (RN-PRJ-19)
- **P17** A tarefa é escrita no modelo de requisição: Sendo / Quero / Para + critérios de aceite `CA_1…CA_n`. (F-PRJ-05)
- **P18** Tipo (melhoria, correção de bug, suporte, mudança/GMUD) e área (lista do projeto, que nasce vazia). As duas listas são editáveis. (RN-PRJ-07)
- **P19** Prioridade: baixa, normal, alta ou urgente. (D-PRJ-21)
- **P20** Uma tarefa = **1 dev + 1 QA**. O QA só existe quando a validação de QA está ligada na tarefa. (D-PRJ-27, RN-PRJ-14)
- **P21** Sem dev, a tarefa não sai do Backlog. **Padrão** (pode desligar). (RN-PRJ-02)
- **P22** Quem é atribuído à tarefa recebe aviso no sistema e por e-mail. (RN-PRJ-21)
- **P23** A tarefa aceita nenhum, um ou vários PRs, colados como link. Exigir PR para ir a `Code-review` vem **desligado**. (RN-PRJ-23; vínculo automático com o GitHub em avaliação, D-GER-13)
- **P24** A tarefa tem anexos e comentários. (D-PRJ-05)
- **P25** Bug achado em produção vira tarefa nova, ligada à original. Conta como indicador de qualidade do projeto, não como retrabalho do dev. (D-PRJ-21)
- **P26** Tarefa nunca usada pode ser excluída. Tarefa abandonada é **arquivada** pelo gestor ou pelo Administrador, com motivo obrigatório: sai do quadro e das contas de prazo e de capacidade, e as horas já lançadas continuam valendo. Desarquivar devolve a tarefa ao Backlog. (D-PRJ-25, D-PRJ-31, RN-PRJ-34)
  - **[REVISAR] R4** O que é "nunca usada"? Proposta: nunca saiu do Backlog e não tem hora lançada.

### 3.4 Horas e prazo

- **P27** Ao criar a tarefa, o gestor estima, junto com o diretor de tecnologia, as horas da **tarefa inteira**: desenvolvimento + testes, um número só. Vale para toda tarefa, inclusive bug e card de suporte. Reestimar fica no histórico. (D-PRJ-27, RN-PRJ-15)
- **P28** A **data prevista** é calculada quando a tarefa entra em `Doing` pela primeira vez: horas previstas ÷ carga diária do dev, em dias úteis de segunda a sexta. Só muda se o gestor reestimar. (D-PRJ-28, RN-PRJ-16)
  Exemplo: 16 h com um dev de 8 h/dia = 2 dias úteis; com um dev de 4 h/dia = 4 dias úteis.
  - **[REVISAR] R5** Duas consequências para você confirmar:
    - a tarefa no Backlog e no To do fica **sem data prevista**; o planejamento antes de `Doing` é feito pela sprint;
    - o retrabalho **não empurra** a data: a tarefa devolvida pelo QA aparece atrasada, e é assim que o atraso fica visível.
- **P29** Feriados não contam na v1. (D-PRJ-22)
- **P30** Horas realizadas: quem trabalhou lança à mão, por dia, em horas decimais. Não há cronômetro. (RN-PRJ-15, D-PRJ-21)
- **P31** As horas do projeto não são comparadas com horas de contrato. (D-PRJ-18)

### 3.5 Validações: code review, teste cruzado, QA

- **P32** Code review, teste cruzado e QA vêm **ligados** em toda tarefa. Cada um desliga por projeto e por tarefa (ex.: tarefa de Swagger sem QA). **Padrão.** (RN-PRJ-09, D-PRJ-15)
- **P33** Code review: na prática quem aprova é o Tech Lead do projeto, mas o sistema não trava isso na v1. (D-PRJ-09, D-PRJ-25)
- **P34** Teste cruzado: não é atribuído. Qualquer dev alocado puxa a tarefa, menos o dev dela, e só uma pessoa por vez. (RN-PRJ-26, D-PRJ-19)
- **P35** Em `In test`, primeiro vem o teste cruzado, depois o QA. (fluxo de Projetos, passo 7)
- **P36** O QA testa no próprio ambiente local. Executa o roteiro, em que cada critério de aceite é um item: marca aprovado ou reprovado e anexa a evidência no item. (D-PRJ-12, F-PRJ-14)
- **P37** Critério editado depois de testado volta para "a testar", e a alteração fica no histórico. Depois de um retrabalho, o roteiro inteiro volta para "a testar". (RN-PRJ-20)
- **P38** Aprovada no QA, a tarefa recebe o merge (normalmente pelo Tech Lead) e vai para `Done`. (D-PRJ-12)

### 3.6 Retrabalho

- **P39** Toda volta para desenvolvimento conta um ciclo de retrabalho, com **origem** (code review, teste cruzado, QA ou homologação) e **motivo**. **Fixa.** (RN-PRJ-08)
- **P40** Os motivos são uma lista editável por projeto, sempre com "outro" com texto. Padrão: ajuste visual, regra de negócio, erro/bug, requisito incompleto ou mal documentado, outro. (RN-PRJ-08)
  - **[REVISAR] R6** A lista padrão ainda está marcada "validar com o Rafael".

### 3.7 Pacote (versão)

- **P41** Tarefas em `Done` entram num pacote, que é a versão (ex.: `1.0.9`). Só tarefa em `Done` entra. (RN-PRJ-10)
- **P42** Estados do pacote: em montagem → em homologação → homologado → em produção. Pacote em homologação não recebe tarefa nova. Hoje os estados são renomeáveis, e dá para criar e remover estado por projeto. (RN-PRJ-10)
  - **[REVISAR] R7** Alguém precisa criar ou remover estado de pacote? Com os 4 estados fixos, só renomeáveis, a regra e o banco ficam mais simples.
- **P43** A homologação é da equipe WCJ, testando tudo junto; o cliente não participa. Reprovação devolve as tarefas afetadas com retrabalho de origem homologação. (D-PRJ-13, RN-PRJ-10)
- **P44** A data planejada de liberação é definida por projeto, em geral pelo cliente. A data da versão é quando ela entra em produção. (fluxo de Projetos, passo 9)

### 3.8 Sprint

- **P45** Sprint é opcional por projeto. O gestor cria com nome, objetivo, início e fim. (D-PRJ-23)
- **P46** Duração padrão de 10 dias úteis (2 semanas), ajustável por projeto. **Padrão.** (D-PRJ-24, RN-PRJ-35)
- **P47** O gestor monta a sprint com um time e junta tarefas do `To do`. Só entra tarefa cujo dev está no time. (D-PRJ-24)
- **P48** Capacidade de cada pessoa na sprint = carga diária × dias disponíveis. O gestor reduz os dias por férias ou folga. (D-PRJ-24)
- **P49** Por dev, a soma das **horas que faltam** das tarefas dele (previstas − já lançadas, nunca abaixo de zero) tem que caber na capacidade. Por padrão o sistema **recusa** o que estoura; o projeto pode trocar para só avisar. Na v1 a tarefa conta só na capacidade do dev; a do QA não é conferida. (D-PRJ-24, D-PRJ-30)
- **P50** Tarefa não concluída no fim da sprint: o gestor leva para a próxima, e fica o histórico. (D-PRJ-23)
- **P51** A sprint é a "Fase" do Histórico de Desenvolvimento entregue ao cliente. (D-PRJ-23)

### 3.9 Suporte

- **P52** Enquanto o módulo Chamados não existe, quando o cliente aciona a WCJ direto (WhatsApp, telefone, e-mail, reunião ou outro), o gestor registra um **card de suporte** na coluna `Support`, com quem pediu (contato), por onde e quando o pedido chegou. As horas contam como em qualquer tarefa. (D-PRJ-17, RN-PRJ-32)

### 3.10 Indicadores

- **P53** Na v1: tempo por etapa, horas previstas × realizadas, data prevista e atraso por projeto e por pessoa, projeto fora do cronograma. (F-PRJ-02, F-PRJ-09, F-PRJ-11, F-PRJ-12, RN-PRJ-33)
- **P54** Depois da v1, com os dados já gravados desde a v1: painel de produtividade por dev e indicador de retrabalho. Só o gestor e o Administrador veem. (F-PRJ-01, F-PRJ-17, RN-PRJ-22)
- **P55** Depois da v1: histórico de desenvolvimento em PDF, relatório de fechamento, notas de versão e geração do documento de requisição. (F-PRJ-15, F-PRJ-16, F-PRJ-03, F-PRJ-13)

**Fora da v1 em Projetos:** feriados; restrição de etapa por função; vínculo automático com o GitHub; migração do Trello; contrato no projeto.

## 4. CRM

- **C1** Um funil só. Etapas padrão, as do DaaS: Prospecção → Contato → Reunião → Proposta → Ganhou / Perdido. São editáveis, mas sempre existe uma etapa de ganho e uma de perda. (D-CRM-01)
  - **[REVISAR] R8** Hoje o lead anda livre entre as etapas. Existe alguma condição para avançar (ex.: só vai para Proposta depois de uma reunião registrada)? Se não existir, fica livre.
- **C2** O lead tem: empresa (nome, país, CNPJ ou identificador), contato(s), origem, o que está sendo vendido (tipo) e dono. (fluxo do CRM, passo 1; RN-CRM-07)
  - **[REVISAR] R9** O lead não tem valor estimado nem previsão de fechamento. Sem isso, não dá para ver o tamanho do pipeline nem o que fecha no mês. O comercial precisa?
  - **[REVISAR] R10** A lista de origens nasce vazia. Quer um padrão (indicação, LinkedIn, grupos, site, evento)?
- **C3** O mesmo CNPJ pode ter vários leads. Ao cadastrar, o sistema **avisa** se já existe cliente ou lead aberto com esse CNPJ e mostra o dono; não recusa. (D-CRM-06, RN-CRM-01)
- **C4** Todo comercial vê todos os leads e edita só os próprios. O Administrador edita todos. **Fixa.** (RN-CRM-05)
- **C5** Tratativa é cada conversa com o lead: data, canal, resumo e próximo passo. Pode ser editada a qualquer momento, e toda edição fica no histórico. (RN-CRM-03)
  - **[REVISAR] R11** A tratativa não diz com qual contato foi a conversa ("falei com a Fulana"). Precisa?
- **C6** Situação atual do lead: texto curto, sempre o mais recente, atualizado a cada tratativa. As versões antigas ficam no histórico. (RN-CRM-14)
- **C7** Compromisso: follow-up ou reunião (lista editável), data e hora, duração (na reunião), responsável, lead **ou** cliente, observação, local ou link. É criado, remarcado ou cancelado pela tela ou pela IA. (RN-CRM-12, D-CRM-09)
  - **[REVISAR] R12** Compromisso com cliente, sem lead: em que caso acontece na v1? Se não houver caso, fica só com lead.
- **C8** Todo lead ativo tem um follow-up futuro. Sem isso, aparece em alerta para o dono. (RN-CRM-04)
- **C9** Lembretes, sem IA embutida (RN-CRM-13, D-CRM-08):
  - ao criar, remarcar ou cancelar um compromisso, o responsável recebe um convite de calendário por e-mail. Os participantes, ele mesmo chama pelo Teams;
  - todo dia de manhã, um e-mail de resumo com os compromissos do dia e os follow-ups vencidos;
  - no Hub, a lista do dia e dos vencidos.
- **C10** Lead ganho vira cliente sem redigitar nada; se o CNPJ já é cliente, só vincula. O cliente nasce sem contrato. (RN-CRM-01, D-CRM-10)
- **C11** Lead perdido exige motivo de uma lista editável: preço, momento, concorrente, sem resposta, sem perfil, outro (com texto). (RN-CRM-02)
- **C12** Lead de auditoria só é ganho com o sizing preenchido: setor (texto), as 5 perguntas do DaaS e o preço. **Fixa.** (RN-CRM-07, D-CRM-08)
- **C13** Ganhar um lead de auditoria não cria nada no DaaS; a auditoria é aberta lá à mão. (RN-CRM-08)
- **C14** O gestor de projetos não vê o pipeline comercial; o dev não vê o CRM. **Fixa.** (Quem usa)
- **C15** O CRM começa do zero, sem migração do DaaS. (D-CRM-03)

**Fora da v1 no CRM:** oportunidades pós-auditoria; integração CRM → DaaS; assistente de voz embutido; contrato gerado pelo lead ganho.

## 5. Interface de IA

- **IA1** Só usuários internos conectam IA na v1. (D-IA-01)
- **IA2** O sistema não chama IA por conta própria: toda IA é a do usuário, paga por ele. (D-IA-02)
- **IA3** Quem usa o quê (D-IA-03, D-IA-05, D-IA-06):
  - devs: Claude (app ou Claude Code) ou Codex, lendo e escrevendo;
  - ChatGPT Pro: só lê;
  - ChatGPT Plus: não conecta;
  - comercial: pelo GPT "WCJ Hub" no ChatGPT, com áudio, entregue junto com a v1 do CRM.
- **IA4** A conexão é só por login (OAuth), nunca por chave colada. Cada usuário tem uma autorização por assistente, com permissão por módulo (ler ou escrever). (Interface de IA, "Como funciona")
- **IA5** A IA age como o usuário: tem as mesmas permissões da tela, e a permissão da conexão nunca passa do papel. **Fixa.** (RN-IA-01, RN-IA-02)
- **IA6** O usuário vê e revoga as próprias conexões; o Administrador, as de todos. Reconectar revoga a conexão anterior. Usuário desativado ou com papel trocado perde as conexões na hora. **Fixa.** (RN-IA-04, RN-IA-05, D-IA-09)
- **IA7** Limite por conexão: 60 leituras e 20 escritas por minuto, ajustáveis. Quando estoura, a IA recebe um erro legível. (RN-IA-06)
- **IA8** Cada escrita altera uma coisa só. Não existe alteração em lote, exclusão pela IA nem consulta livre (SQL). **Fixa.** (RN-IA-07, O que não pode)
- **IA9** As ferramentas são marcadas como leitura ou escrita, para o assistente pedir confirmação antes de escrever. A segurança não depende disso: o servidor confere tudo. (RN-IA-08)
- **IA10** Texto vindo de cliente volta para a IA como dado, marcado como conteúdo externo. (RN-IA-09)
- **IA11** Toda escrita feita pela IA fica na auditoria com o canal (`ia:claude`, `ia:chatgpt`, `ia:codex`, `ia:gpt`). (RN-IA-03)
- **IA12** Toda chamada da IA, inclusive de leitura, fica registrada por 180 dias (só ids e filtros), e o Administrador consulta numa tela. (RN-IA-10, D-IA-08)
- **IA13** O resumo do cliente pela IA mostra só o que o papel vê. (D-IA-10)
- **IA14** Ferramentas da v1: Núcleo (buscar cliente, resumo do cliente); Projetos (leitura: minhas tarefas, status do projeto, linha do tempo da tarefa, horas do projeto, retrabalho do projeto, tarefas paradas, notas da versão; escrita: escrever tarefa no modelo de requisição, mover e comentar tarefa, lançar horas, anexar PR, devolver para desenvolvimento com motivo); CRM (meus follow-ups, minha agenda, histórico do lead, cadastrar lead, registrar tratativa, mover lead, agendar compromisso, atualizar situação). (Ferramentas da v1)
  - **[REVISAR] R13** A lista ainda está marcada "proposta, validar com os donos". Quem valida: o Rafael a de Projetos e o Wilson a do CRM?

## 6. Chamados

Fora desta revisão. É o último módulo (D-GER-12): ainda não tem demanda, e a decisão entre construir no Hub, adotar o GLPI ou fazer em duas fases está aberta (D-CHM-01). As regras em rascunho continuam na `necessidade.md`. Na v1, o que existe é o card de suporte do quadro (P52).
