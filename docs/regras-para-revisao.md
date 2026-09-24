# Regras de negócio do WCJ Hub — revisão 3

Versão 3, de 24/09/2026. As revisões 1 e 2 estão aprovadas: suas respostas estão aplicadas, inclusive K1 a K5 e as quatro leituras que você confirmou. As regras que mudaram estão marcadas *(revisado)*. Os originais comentados estão nos commits `3f8080a` e `0e1125f`.

**O que falta:** revisei as regras aprovadas procurando o que ficou sem definir. Olhei entidade por entidade (quem cria, quem edita, quem encerra, como se desfaz) e cada ponto em que uma regra depende de outra. Achei **17 lacunas**. Elas aparecem **só na tabela abaixo**, cada uma com a minha recomendação. Nenhuma está escrita nas regras ainda: entram depois da sua resposta.

**Como revisar:** responda L1 a L17 ("ok" ou outra coisa).

**Depois:** a `necessidade.md` e o `banco.txt` são reescritos numa **sessão separada**. O prompt está no `00-COMECE-AQUI.md`, e o que muda no banco está no fim deste arquivo.

**Fixa** = vale para todo projeto. **Padrão** = vem ligada e o gestor muda por projeto.

---

## Lacunas para definir

| # | Onde | O que falta | Recomendo |
|---|---|---|---|
| L1 | P21, P47, P49 | **Regra aprovada que se contradiz.** A P21 deixa desligar "sem dev, a tarefa não entra na sprint". Só que a P47 e a P49 precisam do dev para conferir o time e a capacidade. | P21 **fixa**: sem dev, a tarefa não entra na sprint. |
| L2 | Sprint | O projeto pode ter duas sprints ao mesmo tempo? Quem encerra a sprint, e o que acontece com o que não terminou? | Uma sprint em andamento por projeto. O gestor encerra e decide, tarefa por tarefa, o que não terminou: vai para a próxima sprint ou volta ao Backlog. |
| L3 | Sprint | Dá para tirar uma tarefa da sprint no meio dela? | Dá: o gestor tira e a tarefa volta ao Backlog. Como já entrou em sprint, não pode mais ser excluída, só arquivada (P26). |
| L4 | P52, P25, P43 | Além do card de suporte, o que mais entra direto na sprint em andamento? E se não houver sprint em andamento? | Entram direto: card de suporte, bug de produção urgente e tarefa devolvida pela homologação. Sem sprint em andamento, esperam a próxima. |
| L5 | P52 | O card de suporte passa pelo fluxo inteiro (`Doing`, `Code-review`, `In test`, `Done`) ou só registra o atendimento? | Passa pelo mesmo fluxo, com as validações que o gestor deixar ligadas no card (P32). Atendimento sem código: o gestor desliga as três validações e move direto para `Done`. |
| L6 | P15, P28 | A pausa empurra a data prevista? Hoje não empurra: a tarefa pausada por causa do cliente aparece atrasada na conta do dev. | Não empurra. O atraso mostra à parte quanto dele foi pausa. |
| L7 | P53 | O que é "tarefa atrasada"? | Passou da data prevista e ainda não chegou em `Done`. |
| L8 | Tarefa | Quem cria tarefa? O fluxo diz "o gestor escreve". O dev, ou a IA dele, pode criar tarefa no Backlog? | Criam o gestor e o Administrador; o dev não. A IA segue o papel (G4). |
| L9 | P30 | Horas realizadas: quem lança em qual tarefa, e até quando dá para corrigir? | Lança quem está na tarefa: o dev, o QA e quem puxou o teste cruzado. Cada um corrige as próprias horas até a sprint encerrar; depois disso, só o gestor. |
| L10 | P41 a P44 | O pacote é obrigatório? Quem cria o pacote e muda o estado dele? | Não é obrigatório: tarefa em `Done` sem pacote fica "pronta, não publicada". O gestor e o Tech Lead criam o pacote e mudam o estado. |
| L11 | P5, P7 | Dá para encerrar um projeto com tarefa aberta? E tirar do projeto um dev que tem tarefa aberta? | Não, nos dois casos. Antes de encerrar, conclua ou arquive as tarefas abertas. Antes de tirar o dev, troque o dev das tarefas dele. |
| L12 | P22 | Além da atribuição, que eventos geram aviso no sistema? | Atribuição; tarefa devolvida para desenvolvimento (avisa o dev); tarefa chegou em `Code-review` (avisa o Tech Lead); tarefa chegou em `In test` (avisa o QA). |
| L13 | Listas | Quem edita cada lista? | **Administrador** edita as listas gerais: tipos de tarefa, tipos de serviço, etapas do funil, origens de lead, motivos de perda, tipos de compromisso. **Gestor do projeto** edita as listas do projeto: etapas, áreas, motivos de retrabalho, estados do pacote. |
| L14 | Núcleo | Quem define a carga diária? Quem reativa o que foi desativado? O gestor pode cadastrar contato? Dá para desativar cliente com algo aberto? | O Administrador define a carga diária e reativa usuário, cliente e contato. O gestor cadastra contato dos clientes dos projetos dele, que ele precisa no card de suporte. Cliente com projeto aberto ou lead aberto não pode ser desativado. |
| L15 | C8 | Como o follow-up sai de "vencido"? | Registrar uma tratativa no lead dá como feito o follow-up vencido dele. Também dá para marcar como feito à mão. |
| L16 | CRM | Lead cadastrado por engano ou duplicado pode ser excluído? Lead perdido pode voltar ao funil? Quem troca o dono do lead? | Só se exclui lead sem nenhuma tratativa. O lead perdido volta ao funil pelo dono ou pelo Administrador, e fica o histórico. Só o Administrador troca o dono. |
| L17 | G2 e LGPD | Um contato pede, pela LGPD, que os dados dele sejam apagados. Mas a G2 diz que nada usado é excluído. | O Administrador **anonimiza** o contato: nome, e-mail e telefone viram "removido a pedido do titular". O histórico continua, sem os dados pessoais. |

---

## 0. Vale para todos os módulos

- **G1** O sistema traz um padrão pronto que o gestor ajusta por projeto. Só é fixo o que protege segurança, histórico e indicadores.
- **G2** Nada que já foi usado é excluído: é desativado ou arquivado, com quem e quando.
- **G3** Toda alteração, em qualquer módulo, fica na auditoria: quem, quando, o antes e o depois, e por onde (tela ou qual IA). Tentativa negada também fica. A auditoria nunca é apagada.
- **G4** O que o papel não vê na tela, a IA dele também não vê nem altera.
- **G5** Dado sem regra que o use não entra no sistema. Volta quando alguém pedir, com o motivo.
- **G6** Na v1 não existe: aprovação em dois níveis, ranking ou metas, mensagem ao cliente por WhatsApp, aplicativo nativo (é web responsiva).

## 1. Quem usa (papéis da v1)

| Papel | Quem é | Vê e faz | Não vê |
|---|---|---|---|
| Administrador | Sócios, inclusive o Wilson | Tudo. Cadastra usuários e clientes e atribui o papel | — |
| Gestor de projetos | Rafael Garcia; Lucas Scholz nos projetos 100% IA | Cria e acompanha os projetos que gere, estima e atribui tarefas, monta sprints, exclui ou arquiva tarefa | Pipeline comercial e valores de proposta |
| Desenvolvedor | Devs PJ | Projetos em que está alocado e todas as tarefas desses projetos, para poder puxar teste cruzado; lança horas | CRM; painel de produtividade |
| Comercial | Ninguém no início | Todos os leads; edita os próprios; converte lead em cliente | Tarefas internas de projeto |

- O papel Comercial fica pronto para quando houver comercial. Hoje o Wilson é sócio e entra como Administrador.
- **Tech Lead** não é papel: é uma função dada por projeto (P6).
- **Suporte** e **contato do cliente** entram com o módulo Chamados.

## 2. Núcleo

- **N1** Usuário é convidado pelo Administrador, com senha provisória trocada no primeiro acesso.
- **N2** Cada usuário tem um papel, entre os 4 fixos, atribuído pelo Administrador. Não se cria papel novo.
- **N3** Carga diária (ex.: 8 h ou 4 h): obrigatória para o Desenvolvedor, opcional para os outros papéis. Só entra no time da sprint quem tem carga. Mudar a carga vale dali para a frente.
- **N4** Usuário desativado perde o acesso na hora, inclusive as conexões de IA.
- **N5** Cliente brasileiro é identificado pelo CNPJ; CNPJ repetido é recusado. Empresa estrangeira: país + identificador fiscal do país, opcional. Pessoa física não é cliente.
- **N6** Cliente nasce por cadastro direto ou por lead ganho, sempre sem contrato.
- **N7** A própria WCJ é um cliente: é a dona dos projetos internos e dos produtos (ex.: Wpay).
- **N8** Contato é um cadastro só, seja pessoa de cliente ou de lead: nome, e-mail e telefone. Quando o lead vira cliente, o contato não é redigitado. O sistema avisa quando já existe contato com o mesmo e-mail, sem recusar.
- **N9** Contato não tem login na v1.
- **N10** Contrato fica fora da v1. Fica a lista de **tipos de serviço** (projeto, sustentação, auditoria, alocação de dev, infraestrutura), editável pelo Administrador, que o lead usa para dizer o que está sendo vendido.

## 3. Projetos

### 3.1 Projeto

- **P1** Todo projeto pertence a um cliente e tem um gestor responsável. **Fixa.**
- **P2** Dois tipos de projeto:
  - **projeto de entrega**: tem data de início e data de entrega. A data de entrega é informação para o gestor, que acompanha o prazo do projeto por conta própria na v1;
  - **produto contínuo** (ex.: Wpay): só tem data de início, fica em melhoria contínua, e o prazo é o de cada sprint.
- **P3** Cada projeto tem um prefixo (ex.: `SGM`), único no sistema, que não muda depois da primeira tarefa.
- **P4** Modalidade tradicional ou 100% IA. Na v1 é só uma marcação: o fluxo é o mesmo.
- **P5** O dev vê só os projetos em que está alocado; o gestor, os que gere; o Administrador, todos. **Fixa.**
- **P6** O Tech Lead é definido por projeto, um ou mais. A mesma pessoa pode ser Tech Lead num projeto e dev em outro.
- **P7** Projeto encerrado não recebe tarefa nova. Só o gestor ou o Administrador reabre. **Fixa.**
- **P8** Mudar a configuração do projeto vale dali para a frente e nunca apaga o que já foi registrado. **Fixa.**
- ~~**P9** Quantas sprints cabem até a entrega e "fora do cronograma"~~: **fora da v1**.

### 3.2 Quadro e etapas

- **P10** Cada projeto tem o próprio quadro. Padrão: `Backlog`, `To do`, `Doing`, `Paused`, `Code-review`, `In test`, `Done` e `Support`, em inglês. **Padrão.**
- **P11** O gestor renomeia, cria, remove e reordena etapas. Toda etapa tem um papel fixo: fila, sprint, desenvolvimento, code review, teste, pronto, pausa ou suporte. Os indicadores leem o papel, não o nome.
- **P12** Para remover uma etapa que tem tarefas, é preciso dizer para onde elas vão. **Fixa.**
- **P13** Qualquer pessoa alocada no projeto move a tarefa entre as etapas, a partir do `To do`. A saída do Backlog é só pela sprint (P45). Restringir etapa por função fica para depois da v1.
- **P14** Toda mudança de etapa grava quem, quando, de onde e para onde. **Fixa.**
- **P15** Ir para `Paused` exige motivo, e o tempo parado conta à parte. **Padrão** (pode desligar o motivo).

### 3.3 Tarefa

- **P16** Chave `<prefixo>-<número>` (ex.: `SGM-17`), sequencial por projeto, nunca reaproveitada. **Fixa.**
- **P17** A tarefa é escrita no modelo de requisição: Sendo / Quero / Para + critérios de aceite `CA_1…CA_n`.
- **P18** Tipo (melhoria, correção de bug, suporte, mudança/GMUD) e área (lista do projeto, que nasce vazia). As duas listas são editáveis.
- **P19** Prioridade: baixa, normal, alta ou urgente.
- **P20** A tarefa tem **um dev** e, quando a validação de QA está ligada, **um QA**. *(revisado)*
- **P21** Sem dev, a tarefa não entra na sprint. **Padrão** (pode desligar). Ver L1.
- **P22** Quem é atribuído à tarefa recebe aviso **no sistema**. Sem e-mail na v1.
- **P23** A tarefa aceita nenhum, um ou vários PRs, colados como link. Exigir PR para ir a `Code-review` vem **desligado**.
- **P24** A tarefa tem anexos e comentários.
- **P25** Bug achado em produção vira tarefa nova, ligada à original. Conta como indicador de qualidade do projeto, não como retrabalho do dev.
- **P26** Excluir e arquivar:
  - o Administrador ou o gestor **exclui**, sem precisar de motivo, a tarefa que **nunca entrou em sprint**; a exclusão fica na auditoria;
  - tarefa que já entrou em sprint não se exclui. Se for abandonada, é **arquivada** pelo gestor ou pelo Administrador, com motivo obrigatório. Sai do quadro e das contas de prazo e de capacidade, e as horas já lançadas continuam valendo. Desarquivar devolve a tarefa ao Backlog.

### 3.4 Horas e prazo

- **P27** Horas previstas: **um total só, Dev + QA**. Ao criar a tarefa são opcionais. Na montagem da sprint, o gestor, com o diretor de tecnologia, **define ou valida** as horas; tarefa sem horas não entra na sprint. Vale para toda tarefa, inclusive bug e card de suporte. Reestimar fica no histórico. *(revisado)*
- **P28** A **data prevista** é calculada quando a tarefa entra em `Doing` pela primeira vez: horas previstas ÷ carga diária do dev, em dias úteis de segunda a sexta. Só muda se o gestor reestimar. Antes de `Doing`, a tarefa não tem data: o planejamento é a sprint. O retrabalho não empurra a data, e é assim que o atraso fica visível.
  Exemplo: 16 h com um dev de 8 h/dia = 2 dias úteis; com um dev de 4 h/dia = 4 dias úteis.
- **P29** Feriados não contam na v1.
- **P30** Horas realizadas: quem trabalhou lança à mão, por dia, em horas decimais. Não há cronômetro.
- **P31** As horas do projeto não são comparadas com horas de contrato.

### 3.5 Validações: code review, teste cruzado, QA

- **P32** Code review, teste cruzado e QA vêm **ligados** em toda tarefa. Cada um desliga por projeto e por tarefa (ex.: tarefa de Swagger sem QA). **Padrão.**
- **P33** Code review: na prática quem aprova é o Tech Lead do projeto, mas o sistema não trava isso na v1.
- **P34** Teste cruzado: não é atribuído. Qualquer dev alocado puxa a tarefa, menos o dev dela, e só uma pessoa por vez. *(revisado)*
- **P35** Em `In test`, primeiro vem o teste cruzado, depois o QA.
- **P36** O QA testa no próprio ambiente local. Executa o roteiro, em que cada critério de aceite é um item: marca aprovado ou reprovado e anexa a evidência no item.
- **P37** Critério editado depois de testado volta para "a testar", e a alteração fica no histórico. Depois de um retrabalho, o roteiro inteiro volta para "a testar".
- **P38** Aprovada no QA, a tarefa recebe o merge (normalmente pelo Tech Lead) e vai para `Done`.

### 3.6 Retrabalho

- **P39** Toda volta para desenvolvimento conta um ciclo de retrabalho, com **origem** (code review, teste cruzado, QA ou homologação) e **motivo**. **Fixa.**
- **P40** Os motivos são uma lista editável por projeto, sempre com "outro" com texto. Padrão, já validado: ajuste visual, regra de negócio, erro/bug, requisito incompleto ou mal documentado, outro.

### 3.7 Pacote (versão)

- **P41** Tarefas em `Done` entram num pacote, que é a versão (ex.: `1.0.9`). Só tarefa em `Done` entra.
- **P42** Estados do pacote: em montagem → em homologação → homologado → em produção. O gestor pode renomear, criar e remover estados no projeto. Pacote em homologação não recebe tarefa nova.
- **P43** A homologação é da equipe WCJ, testando tudo junto; o cliente não participa. Reprovação devolve as tarefas afetadas com retrabalho de origem homologação.
- **P44** A data planejada de liberação é definida por projeto, em geral pelo cliente. A data da versão é quando ela entra em produção.

### 3.8 Sprint

- **P45** A sprint é **obrigatória** e é o único caminho para a tarefa sair do Backlog. O gestor cria a sprint (nome, objetivo, início e fim), escolhe as tarefas do Backlog, e elas vão para o `To do`. Só entra em `Doing` tarefa que está numa sprint.
- **P46** Duração padrão de 10 dias úteis (2 semanas), ajustável por projeto. **Padrão.**
- **P47** A sprint tem um time. Só entra na sprint tarefa cujo dev está no time. *(revisado)*
- **P48** Capacidade de cada pessoa na sprint = carga diária × dias disponíveis. O gestor reduz os dias por férias ou folga.
- **P49** Por dev, a soma das **horas que faltam** das tarefas dele (previstas − já lançadas, nunca abaixo de zero) é comparada com a capacidade. Se estourar, o sistema **só avisa**, nunca recusa. A capacidade do QA não é conferida na v1.
- **P50** Tarefa não concluída no fim da sprint: o gestor leva para a próxima, e fica o histórico.
- **P51** A sprint é a "Fase" do Histórico de Desenvolvimento entregue ao cliente.

### 3.9 Suporte

- **P52** Enquanto o módulo Chamados não existe, quando o cliente aciona a WCJ direto (WhatsApp, telefone, e-mail, reunião ou outro), o gestor registra um **card de suporte** na coluna `Support`, com quem pediu (contato), por onde e quando o pedido chegou. O card entra direto na sprint em andamento. As horas contam como em qualquer tarefa.

### 3.10 Indicadores

- **P53** Na v1: tempo por etapa, horas previstas × realizadas, data prevista e atraso das tarefas, somados por projeto e por pessoa.
- **P54** Depois da v1, com os dados já gravados desde a v1: painel de produtividade por dev e indicador de retrabalho. Só o gestor e o Administrador veem.
- **P55** Depois da v1: histórico de desenvolvimento em PDF, relatório de fechamento, notas de versão e geração do documento de requisição.

**Fora da v1 em Projetos:**
- feriados;
- restrição de etapa por função;
- vínculo automático com o GitHub;
- migração do Trello;
- contrato no projeto;
- prazo do projeto pelo sistema (quantas sprints cabem, fora do cronograma);
- aviso de tarefa por e-mail.

## 4. CRM

- **C1** Um funil só. Etapas padrão, as do DaaS: Prospecção → Contato → Reunião → Proposta → Ganhou / Perdido. São editáveis, mas sempre existe uma etapa de ganho e uma de perda. O lead anda livre entre as etapas: quem decide é o comercial.
- **C2** O lead tem: empresa (nome, país, CNPJ ou identificador), contato(s), origem, tipo de serviço, **valor** e dono. O valor é obrigatório para ganhar. Não tem previsão de fechamento. A origem vem de uma lista editável, que nasce com **Indicação, Tráfego e Site**.
- **C3** O mesmo CNPJ pode ter vários leads. Ao cadastrar, o sistema **avisa** se já existe cliente ou lead aberto com esse CNPJ e mostra o dono; não recusa.
- **C4** Quem tem papel Comercial vê todos os leads e edita só os próprios. O Administrador edita todos. **Fixa.**
- **C5** Tratativa é cada conversa com o lead: data, canal, resumo, próximo passo e, **opcional**, com quem falou (um contato do lead). Pode ser editada a qualquer momento, e toda edição fica no histórico. *(revisado)*
- **C6** Situação atual do lead: texto curto, sempre o mais recente, atualizado a cada tratativa. As versões antigas ficam no histórico.
- **C7** Compromisso: follow-up ou reunião (lista editável), data e hora, duração (na reunião), responsável, observação, local ou link. É sempre ligado a um **lead ou a um cliente**, nunca solto. É criado, remarcado ou cancelado pela tela ou pela IA.
- **C8** Todo lead ativo tem um follow-up futuro. Sem isso, aparece em alerta para o dono.
- **C9** Lembretes, sem IA embutida:
  - ao criar, remarcar ou cancelar um compromisso, o responsável recebe um convite de calendário por e-mail. Os participantes, ele mesmo chama pelo Teams;
  - todo dia de manhã, um e-mail de resumo com os compromissos do dia e os follow-ups vencidos;
  - no Hub, a lista do dia e dos vencidos.
- **C10** Lead ganho vira cliente sem redigitar nada; se o CNPJ já é cliente, só vincula. O cliente nasce sem contrato.
- **C11** Lead perdido exige motivo de uma lista editável: preço, momento, concorrente, sem resposta, sem perfil, outro (com texto).
- **C12** Lead de auditoria só é ganho com o sizing preenchido: setor (texto) e as 5 perguntas do DaaS. O preço da auditoria é o valor do lead (C2), sem campo repetido. **Fixa.**
- **C13** Ganhar um lead de auditoria não cria nada no DaaS; a auditoria é aberta lá à mão.
- **C14** O gestor de projetos não vê o pipeline comercial; o dev não vê o CRM. **Fixa.**
- **C15** O CRM começa do zero, sem migração do DaaS.

**Fora da v1 no CRM:** oportunidades pós-auditoria; integração CRM → DaaS; assistente de voz embutido; contrato gerado pelo lead ganho; previsão de fechamento.

## 5. Interface de IA

- **IA1** Só usuários internos conectam IA na v1.
- **IA2** O sistema não chama IA por conta própria: toda IA é a do usuário, paga por ele.
- **IA3** Quem usa o quê:
  - devs: Claude (app ou Claude Code) ou Codex, lendo e escrevendo;
  - ChatGPT Pro: só lê;
  - ChatGPT Plus: não conecta;
  - comercial (hoje o Wilson): pelo GPT "WCJ Hub" no ChatGPT, com áudio, entregue junto com a v1 do CRM.
- **IA4** A conexão é só por login (OAuth), nunca por chave colada. Cada usuário tem uma autorização por assistente, com permissão por módulo (ler ou escrever).
- **IA5** A IA age como o usuário: tem as mesmas permissões da tela, e a permissão da conexão nunca passa do papel. **Fixa.**
- **IA6** O usuário vê e revoga as próprias conexões; o Administrador, as de todos. Reconectar revoga a conexão anterior. Usuário desativado ou com papel trocado perde as conexões na hora. **Fixa.**
- **IA7** Limite por conexão: 60 leituras e 20 escritas por minuto, ajustáveis. Quando estoura, a IA recebe um erro legível.
- **IA8** Cada escrita altera uma coisa só. Não existe alteração em lote, exclusão pela IA nem consulta livre (SQL). **Fixa.**
- **IA9** As ferramentas são marcadas como leitura ou escrita, para o assistente pedir confirmação antes de escrever. A segurança não depende disso: o servidor confere tudo.
- **IA10** Texto vindo de cliente volta para a IA como dado, marcado como conteúdo externo.
- **IA11** Toda escrita feita pela IA fica na auditoria com o canal (`ia:claude`, `ia:chatgpt`, `ia:codex`, `ia:gpt`).
- **IA12** Toda chamada da IA, inclusive de leitura, fica registrada por 180 dias (só ids e filtros), e o Administrador consulta numa tela.
- **IA13** O resumo do cliente pela IA mostra só o que o papel vê.
- **IA14** Ferramentas da v1:
  - Núcleo: buscar cliente, resumo do cliente;
  - Projetos, leitura: minhas tarefas, status do projeto, linha do tempo da tarefa, horas do projeto, retrabalho do projeto, tarefas paradas, notas da versão;
  - Projetos, escrita: escrever tarefa no modelo de requisição, mover e comentar tarefa, lançar horas, anexar PR, devolver para desenvolvimento com motivo;
  - CRM: meus follow-ups, minha agenda, histórico do lead, cadastrar lead, registrar tratativa, mover lead, agendar compromisso, atualizar situação.

  A lista é validada por quem desenvolve o Hub (Lucas Scholz e Cauã).

## 6. Chamados

Fora desta revisão. É o último módulo: ainda não tem demanda, e a decisão entre construir no Hub, adotar o GLPI ou fazer em duas fases está aberta. As regras em rascunho continuam na `necessidade.md`. Na v1, o que existe é o card de suporte do quadro (P52).

---

## Anexo: o que muda no `banco.txt` (para a sessão de reescrita)

Levantado nesta revisão, a partir das regras aprovadas. As lacunas L1 a L17 podem acrescentar itens.

- **Códigos:** as referências RN-…, D-PRJ-…, D-CRM-…, D-NUC-… e D-IA-… de produto passam a ser os códigos deste arquivo (G, N, P, C, IA). Ficam as decisões de projeto (D-GER, D-IA-04, D-IA-07, D-CRM-04, D-CHM). A seção 7 do banco (mapa das respostas) sai.
- **users.daily_hours:** passa a aceitar vazio; obrigatória para o Desenvolvedor (N3). Só entra em sprint_members quem tem carga.
- **contract_types → service_types**, e leads.contract_type_id → service_type_id (N10).
- **contacts:** índice em email para o aviso de duplicado (N8). A pergunta T11 deixa de ser pergunta.
- **projects:**
  - saem os comentários de "sprints que cabem" e "fora do cronograma" (P9 fora da v1). O due_on continua, só informativo (P2);
  - sai enforce_sprint_capacity, porque a capacidade só avisa (P49);
  - require_assignee_out_of_backlog vira "dev obrigatório para entrar na sprint" (P21), ou sai se a L1 tornar a regra fixa.
- **tasks:**
  - estimated_hours passa a aceitar vazio. A obrigação vem na entrada da sprint, e quem confere é o Service (P27);
  - continua 1 developer_id + 1 qa_id (P20).
- **Sprint obrigatória (P45):** a tarefa só sai do Backlog pela sprint (Service). A tarefa pode ser excluída se nunca entrou em sprint, ou seja, se só tem o movimento de criação (P26); a exclusão fica em audit_logs.
- **leads:** entra value (money, aceita vazio), obrigatório para ganhar (C2).
- **lead_audit_sizings:** sai price, porque o preço é o valor do lead (C12).
- **lead_sources:** semente Indicação, Tráfego, Site (C2).
- **lead_interactions:** entra contact_id fk contacts, aceita vazio (C5); tem que ser contato do lead (Service).
- **rework_reasons:** sai "[validar com Rafael]" (P40).
- **notifications:** só no sistema para Projetos (P22). E-mail só nos lembretes do CRM (C9).
