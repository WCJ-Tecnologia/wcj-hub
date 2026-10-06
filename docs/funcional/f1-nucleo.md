# F1. Núcleo: usuários, clientes, contatos e auditoria

**Documento funcional.** Versão 0.1, de 06/10/2026, para aprovação. Estrutura e fluxo em [`README.md`](README.md).

| | |
|---|---|
| **Objetivo** | Dar ao Administrador o cadastro das pessoas que usam o sistema, das empresas atendidas e das pessoas dessas empresas, e uma tela para ver tudo o que foi alterado, por quem e por onde. |
| **Regras que alimentam** | G1 a G5, G7, N1 a N9, N11, N12 (`docs/necessidade.md`). |
| **Tabelas do `banco.txt`** | `roles`, `users`, `clients`, `contacts`, `contact_channels`, `audit_logs`. |
| **Depende de** | Template `ai-systems`: login, senha provisória, troca de senha, shell de navegação. |
| **Quem aprova** | Lucas Scholz (dono do Núcleo, D-GER-07). |

## 01. Fluxo funcional

**Usuários.** O Administrador abre *Administração → Usuários*, clica em *Convidar usuário*, preenche nome, e-mail, papel e carga diária e confirma. O sistema cria a pessoa com uma senha provisória, que ela troca no primeiro acesso (N1). Dali em diante o Administrador edita a ficha, troca o papel, ajusta a carga, desativa e reativa (N2, N3, N11). Usuário desativado perde o acesso no mesmo instante, inclusive as conexões de IA (N4).

**Clientes.** O Administrador abre *Administração → Clientes*, clica em *Novo cliente*, informa o nome, o país e o CNPJ (ou o identificador fiscal, se a empresa for de fora) e confirma (N5). O cliente nasce sem contrato (N6). A própria WCJ já existe como cliente, para os projetos internos e os produtos (N7). Na v2, o lead ganho no CRM também cria cliente por aqui, sem redigitar (C10).

**Contatos.** Na ficha do cliente, aba *Contatos*, o Administrador ou o gestor de um projeto desse cliente clica em *Novo contato*, informa nome, e-mail e telefone e confirma (N8). Se já existe contato com o mesmo e-mail, o sistema avisa e deixa seguir (N8). O contato não tem login (N9). A pedido do titular, o Administrador anonimiza o contato: os dados pessoais somem, o histórico fica (N12).

**Auditoria.** O Administrador abre *Administração → Auditoria* e vê, em ordem do mais recente, toda alteração feita em qualquer módulo e toda tentativa negada: quem, quando, por onde (tela ou qual IA), o que mudou (G3). Nada ali se edita nem se apaga (G3). De qualquer ficha (usuário, cliente, contato, projeto, card), o botão *Histórico* abre a auditoria já filtrada naquele registro.

## 02. Telas e campos

### Usuário (criar e editar)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 120 | sim | | |
| E-mail | e-mail, até 160, único | sim | | É o login. Repetido é recusado. |
| Papel | lista fixa: Administrador, Gestor de projetos, Desenvolvedor, Comercial | sim | | Só os 4; não se cria papel (N2). Comercial fica pronto para a v2, sem uso hoje. |
| Carga diária | horas, com decimais, maior que 0 e até 24 | sim para Desenvolvedor; opcional para os outros | | Horas por dia na WCJ, ex.: 8 ou 4 (N3). Usada na data prevista e na capacidade (P28, P48). Mudar vale dali para a frente. |
| Senha provisória | gerada pelo sistema | | | Trocada no primeiro acesso (N1). Nunca aparece na auditoria. |
| Situação | só leitura: Ativo / Desativado em DD/MM/AAAA por Fulano | | Ativo | (N11) |

### Cliente (criar e editar)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Nome | texto, até 160 | sim | | Como a WCJ chama o cliente. |
| País | lista de países | sim | Brasil | (N5) |
| CNPJ / identificador fiscal | texto, até 30 | sim quando o país é Brasil; opcional fora | | No Brasil: CNPJ de 14 caracteres, números e letras, pois o CNPJ alfanumérico vale desde jul/2026. Repetido no mesmo país é recusado (N5). O rótulo muda com o país: "CNPJ" para Brasil, "Identificador fiscal" para os demais. |
| Situação | só leitura: Ativo / Desativado em … por … | | Ativo | (N11) |

### Contato (criar e editar, dentro do cliente)

| Campo | Tipo | Obrigatório | Padrão | Regra |
|---|---|---|---|---|
| Cliente | fixo: o cliente de onde se abriu | sim | | Na fatia 1 todo contato é de um cliente (N8). |
| Nome | texto, até 120 | sim | | |
| E-mail | e-mail, até 160 | não | | Se já existe contato com este e-mail, aviso "Já existe Fulano (Cliente X) com este e-mail"; segue mesmo assim (N8). |
| Telefone | texto, até 20 | não | | Como o comercial ou o gestor liga ou chama no WhatsApp. |
| Situação | só leitura: Ativo / Desativado em … por … / Anonimizado | | Ativo | (N11, N12) |

### Auditoria (só leitura)

Lista com os campos da seção 03; ao clicar numa linha, abre o detalhe com o antes e o depois de cada campo alterado (G3).

### Perfil (do template)

Nome e troca de senha vêm do template. A aba *Conexões de IA* está em [F8](f8-conexoes-de-ia.md).

## 03. Listagem

| Tela | Colunas | Ordem padrão | Observação |
|---|---|---|---|
| Usuários | Nome, E-mail, Papel, Carga diária, Último acesso, Situação | Nome | Desativados ficam escondidos; o filtro *Mostrar desativados* os traz, em cinza. Último acesso vem da auditoria (login). |
| Clientes | Nome, País, CNPJ / identificador, Situação | Nome | Idem para desativados. |
| Contatos do cliente | Nome, E-mail, Telefone, Situação | Nome | Dentro da ficha do cliente. Uma lista geral *Administração → Contatos*, com a coluna Cliente, para achar uma pessoa sem saber a empresa. |
| Auditoria | Data e hora, Quem, Canal (Tela, ou IA: Claude, Codex, ChatGPT), Ação (criou, editou, moveu, desativou, arquivou, anonimizou…), Registro (tipo e identificação, ex.: "Card SGM-17", "Usuário Maria"), Resultado (ok / negado) | Mais recente primeiro | Páginas de 50. Linha negada em destaque (G3). |

Estado vazio em toda lista: uma frase e o botão de criar ("Nenhum cliente ainda. *Novo cliente*"). Na auditoria: "Nenhum registro com esses filtros".

## 04. Ações

### Usuários

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Convidar usuário | Administrador | Cria a pessoa com senha provisória (N1). | Mostra a senha provisória uma vez, para o Administrador repassar (ver 10.1). |
| Editar | Administrador | Nome, e-mail, papel, carga. Trocar o papel derruba as conexões de IA da pessoa (IA6). Mudar a carga vale dali para a frente (N3). | Ao trocar o papel: "As conexões de IA de Fulano serão desconectadas. Continuar?" |
| Desativar | Administrador | A pessoa perde o acesso agora: sessões encerradas e conexões de IA revogadas (N4, IA6). As tarefas em que ela é dev ou QA continuam apontando para ela (P5 trata só de sair do projeto). | "Fulano perde o acesso imediatamente. Continuar?" |
| Reativar | Administrador | Volta a entrar com a senha de antes. As conexões de IA não voltam: a pessoa reconecta (IA6). | |
| Excluir | Administrador | Só quem nunca entrou e não aparece em nada (G2). Quem já usou o sistema não tem o botão. | "Excluir de verdade. Não dá para desfazer." |
| Histórico | Administrador | Abre a auditoria filtrada na pessoa. | |

### Clientes

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Novo cliente | Administrador | Cria o cliente, sem contrato (N5, N6). | CNPJ repetido: recusa com "Já existe o cliente X com este CNPJ" (N5). |
| Editar | Administrador | Nome, país, documento. | Mesma recusa de CNPJ repetido. |
| Desativar / Reativar | Administrador | Desativado sai das listas de escolha (ex.: criar projeto) e fica em cinza; ter projeto aberto não impede (N11). | "O cliente X tem N projetos abertos; eles continuam. Desativar?" |
| Excluir | Administrador | Só cliente sem projeto e sem contato (G2). | "Excluir de verdade. Não dá para desfazer." |
| Histórico | Administrador | Auditoria filtrada no cliente. | |

### Contatos

| Ação | Quem | O que faz | Confirmação / aviso |
|---|---|---|---|
| Novo contato | Administrador; gestor nos clientes dos projetos dele (N8) | Cria o contato no cliente. | E-mail já existente: aviso, sem recusar (N8). |
| Editar | Idem | Nome, e-mail, telefone. | Idem. |
| Desativar / Reativar | Administrador | Sai das listas de escolha (ex.: "quem pediu" no card de suporte, P52); o que já cita a pessoa continua (N11). | |
| Excluir | Administrador | Só contato nunca citado em card de suporte (G2). | "Excluir de verdade. Não dá para desfazer." |
| Anonimizar | Administrador | Nome vira "removido a pedido do titular"; e-mail e telefone somem, inclusive do histórico da auditoria daquele contato (N12). Irreversível. | "Isto atende a um pedido do titular (LGPD) e não pode ser desfeito. Digite ANONIMIZAR para confirmar." |

### Auditoria

Só ver e filtrar. Não existe editar, apagar nem exportar (G3).

## 05. Filtros e busca

| Tela | Busca | Filtros |
|---|---|---|
| Usuários | nome, e-mail | Papel; Situação (ativos por padrão) |
| Clientes | nome, CNPJ / identificador | País; Situação |
| Contatos | nome, e-mail, telefone | Cliente; Situação |
| Auditoria | identificação do registro (ex.: "SGM-17", "Maria") | Período (padrão: últimos 7 dias); Quem; Canal (Tela / Claude / Codex / ChatGPT); Ação; Tipo de registro (usuário, cliente, contato, projeto, card, sprint, pacote, conexão de IA…); Resultado (ok / negado) |

## 06. Indicadores

Não se aplica: nenhuma regra pede número no Núcleo (G5).

## 07. Avisos e documentos gerados

- **Senha provisória:** mostrada uma vez na tela ao Administrador (ver 10.1). Nenhum e-mail no Núcleo na fatia 1.
- **Aviso de contato repetido:** "Já existe Fulano (Cliente X) com este e-mail" (N8). Deixa seguir.
- **Recusa de CNPJ repetido:** "Já existe o cliente X com este CNPJ" (N5). Não segue.
- Nenhum documento gerado.

## 08. Exportação

Não se aplica na fatia 1: nenhuma regra pede exportação, e a auditoria não exporta (G3, G5).

## 09. Permissionamento

| O quê | Administrador | Gestor de projetos | Desenvolvedor |
|---|---|---|---|
| Usuários: ver lista | sim | só nomes ativos, nas telas de alocar e atribuir (F2, F3) | só nomes dos alocados no projeto, nas telas do card (F3) |
| Usuários: convidar, editar, desativar, excluir | sim | não | não |
| Clientes: ver lista e ficha | sim | sim, para escolher no projeto (P1) | só o nome do cliente no projeto em que está (P5) |
| Clientes: criar, editar, desativar, excluir | sim | não (N6) | não |
| Contatos: ver | sim | dos clientes dos projetos dele | dos clientes dos projetos dele, só para ler "quem pediu" no card de suporte (P52) |
| Contatos: criar, editar | sim | nos clientes dos projetos dele (N8) | não |
| Contatos: desativar, excluir, anonimizar | sim | não | não |
| Auditoria: tela geral | sim | não | não |
| Histórico de um card (movimentos) | sim | nos projetos dele | nos projetos em que está (F7) |
| Próprio perfil e senha | sim | sim | sim |

A IA de cada pessoa tem exatamente estas permissões, nem mais nem menos (G4). O Comercial não aparece: não tem uso na fatia 1.

## 10. Pontos a validar

1. **Entrega da senha provisória.** O template mostra a senha uma vez na tela, para o Administrador repassar. Com o SMTP da WCJ configurado para os avisos de Projetos (P22), vale mandar o convite por e-mail? Hoje nenhuma regra pede; proposta: manter como o template faz e decidir depois do uso.
2. **Gestor na auditoria.** G3 não diz quem vê a tela geral. Proposta: só o Administrador; o gestor vê o histórico de cada card, projeto e sprint pela ficha (F7). Confirmar.
3. **WCJ como cliente.** N7 diz que a WCJ é um cliente. Proposta: ela já vem semeada na instalação, com o CNPJ da WCJ, e não pode ser desativada. Confirmar o CNPJ a usar.
4. **Contato de lead na fatia 1.** O banco deixa o cliente do contato vazio para a v2 (lead). Na fatia 1 a tela exige cliente. Confirmar que ninguém precisa cadastrar contato "solto" antes do CRM.
