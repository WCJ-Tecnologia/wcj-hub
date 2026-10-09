# WForgotPasswordPage

Tela · `pages/w-forgot-password-page/w-forgot-password-page.tsx` · story `Pages/Esqueci minha senha`

## O que faz

Pede o e-mail e manda o link de redefinição.

O Laravel chama esta tela por `Inertia::render('auth/forgot-password')`. O arquivo `resources/js/pages/auth/forgot-password.tsx` só reexporta este componente (ADR-003). Mude a tela aqui, não lá.

## Props (vindas do controller)

nenhuma (lê `flash.success`)

## Observações

A resposta é a mesma exista o e-mail ou não.

## Story

As props de exemplo ficam em `.storybook/fixtures.ts`, e as props compartilhadas (usuário logado, marca, flash), em `parameters.inertia` da story. Formulários e navegação não saem do navegador: aparecem no painel **Actions**.
