# WChangePasswordPage

Tela · `pages/w-change-password-page/w-change-password-page.tsx` · story `Pages/Primeiro acesso`

## O que faz

Troca obrigatória da senha temporária no primeiro acesso, com a opção de sair e usar outra conta.

O Laravel chama esta tela por `Inertia::render('auth/change-password')`. O arquivo `resources/js/pages/auth/change-password.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`firstName`

## Observações

O servidor redireciona para cá enquanto `must_change_password` for verdadeiro.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
