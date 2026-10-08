# WResetPasswordPage

Tela · `pages/w-reset-password-page/w-reset-password-page.tsx` · story `Pages/Nova senha`

## O que faz

Escolha da senha nova a partir do link do e-mail.

O Laravel chama esta tela por `Inertia::render('auth/reset-password')`. O arquivo `resources/js/pages/auth/reset-password.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

`email`, `token` (vêm do link)

## Observações

Salvar encerra as outras sessões da conta.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
