# WAppTopbarOrganism

Organismo · `organisms/w-app-topbar-organism/w-app-topbar-organism.tsx` · story `Organisms/App Topbar`

## O que faz

A barra do topo do backoffice, fixa ao rolar. Da esquerda para a direita:

1. botão do menu lateral (`SidebarTrigger`): abre a gaveta no celular e recolhe o menu no desktop;
2. logo (só no celular) ou o nome da marca (desktop);
3. troca de tema claro/escuro (`useTheme`, salvo em `localStorage`);
4. sino de avisos (`WNotificationsBellOrganism`, F3 §07), que lê `notifications` pelo `usePage()`;
5. menu do usuário (`DropdownMenu`) com "Meu perfil" e "Sair".

O título e as ações da página **não** ficam aqui: ficam no `WPageHeaderMolecule`.

## Props

Nenhuma. Lê `auth.user` e `app.brand` pelo `usePage()`; o sino lê `notifications` (opcional: sem ela, o sino fica vazio).

## Depende de

- `SidebarProvider` acima dele (o `WAppLayoutTemplate` monta).
- `WBrandLogoAtom`, `WNotificationsBellOrganism`, `Avatar`, `Button`, `DropdownMenu`, `SidebarTrigger`.
- `router.post('/admin/logout')` para sair.
