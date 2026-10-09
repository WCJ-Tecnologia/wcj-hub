# WLoginPage

Tela · `pages/w-login-page/w-login-page.tsx` · story `Pages/Login`

## O que faz

Formulário de login (e-mail e senha) com link para "Esqueci minha senha".

O Laravel chama esta tela por `Inertia::render('auth/login')`. O arquivo `resources/js/pages/auth/login.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

nenhuma (lê `flash.success` do `usePage`)

## Observações

A mensagem de erro é única, exista a conta ou não (e2e `auth.spec.ts`).

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
