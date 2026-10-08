# WProfilePage

Tela · `pages/w-profile-page/w-profile-page.tsx` · story `Pages/Meu perfil`

## O que faz

Edição do próprio nome e troca de senha (nova + confirmação).

O Laravel chama esta tela por `Inertia::render('admin/profile/index')`. O arquivo `resources/js/pages/admin/profile/index.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`profile` (`{ name, email, role_label }`)

## Observações

O e-mail não muda por aqui. Trocar a senha encerra as outras sessões (regra do servidor).

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
