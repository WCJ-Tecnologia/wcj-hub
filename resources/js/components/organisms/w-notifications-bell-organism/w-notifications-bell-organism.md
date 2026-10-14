# WNotificationsBellOrganism

Organismo · `organisms/w-notifications-bell-organism/w-notifications-bell-organism.tsx` · story `Organisms/Notifications Bell`

## O que faz

O sino de avisos do shell, no `WAppTopbarOrganism`, entre a troca de tema e o menu do usuário. É "a lista de avisos da pessoa, com link para o card" do F3 §07 (P22, R3). Na fatia 1 são três avisos, e o texto vem pronto do servidor:

- dev definido: "Você é o dev de SGM-17 — <título> (projeto <nome>), definido por Fulano.";
- QA definido: "Você é o QA de SGM-17 — <título> (projeto <nome>), definido por Fulano.";
- retrabalho: "SGM-17 — <título> voltou para <coluna>: origem <QA>, motivo <…>, por Fulano.".

O botão (`Button` outline, ícone `Bell`) tem o nome "Avisos" ou "Avisos, 3 não lidos"; com não lidos, mostra um selo azul com o número ("9+" acima de 9), escondido do leitor de tela porque o nome já diz. Clicar abre um `Popover` (`align="end"`, `w-80`, `modal={false}`) com:

- cabeçalho "Avisos" e, só com não lidos, "Marcar todas como lidas";
- a lista (`ul`) dos `items`, na ordem em que vieram (os 10 mais recentes, não lidos primeiro): ponto azul e "Não lido" (só para o leitor de tela) no não lido, o texto e a data (`dateTimeBr`);
- o texto é link para a ficha do card: o clique fecha a lista, marca como lido (se não lido) e, quando o servidor responde, visita a URL. Ctrl/Cmd+clique abre em outra aba como um link comum. Aviso de card excluído (`url: null`) fica só como texto;
- "Marcar como lida" em cada não lido;
- vazio: "Nenhum aviso.".

## Props

| Prop            | Tipo                   | Padrão                          | O que é                                                                                                 |
| --------------- | ---------------------- | ------------------------------- | ------------------------------------------------------------------------------------------------------- |
| `notifications` | `NotificationsPayload` | `usePage().props.notifications` | Sobrepõe o que o Inertia compartilha (stories e testes). Sem as duas, `{ unread_count: 0, items: [] }`. |
| `defaultOpen`   | `boolean`              | `false`                         | Abre a lista ao montar (stories).                                                                       |
| `className`     | `string`               | —                               | Classe extra no botão.                                                                                  |

`NotificationsPayload` e `HubNotification` estão em `resources/js/types/index.d.ts`. A prop `notifications` do `SharedProps` é opcional: o `HandleInertiaRequests` ainda não manda (B3).

## Rotas (proposta do kit; o Cauã confirma no B3)

| Ação                | Chamada                                                                                   |
| ------------------- | ----------------------------------------------------------------------------------------- |
| Marcar um como lido | `router.post('/avisos/{id}/lida', {}, { preserveScroll: true, only: ['notifications'] })` |
| Marcar todos        | `router.post('/avisos/lidas', {}, { preserveScroll: true, only: ['notifications'] })`     |

## Exemplo

```tsx
<WNotificationsBellOrganism />
<WNotificationsBellOrganism notifications={NOTIFICATIONS} defaultOpen />
```

## Cuidados

- **Nada é otimista.** O shell não tem estado próprio: depois de marcar, o servidor manda `notifications` de novo (`only: ['notifications']`). Enquanto uma marcação roda, os botões ficam desabilitados (`aria-busy` na lista, `Loader2` no botão que disparou).
- **Marcar e depois visitar.** No clique do link, a visita sai no `onFinish` do POST: uma visita nova cancelaria o POST em voo (Inertia 2).
- O sino não decide quem recebe aviso nem monta o texto: isso é do servidor (F3 §07; "quem define a si mesmo não recebe" é a proposta do 10.5).
- O selo usa `bg-primary text-primary-foreground` e `font-mono`; o link do texto é `text-foreground` (está sozinho no bloco), as ações são `Button variant="link"` (`text-primary-text`).
