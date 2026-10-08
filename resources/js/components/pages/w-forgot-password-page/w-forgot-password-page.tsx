import { useForm, usePage } from '@inertiajs/react';
import { ArrowRight, Loader2, Mail } from 'lucide-react';
import { type FormEvent } from 'react';

import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { Label } from '@/components/shadcn/label';
import { WAuthBackLinkAtom } from '@/components/atoms/w-auth-back-link-atom/w-auth-back-link-atom';
import { WAuthLayoutTemplate } from '@/components/templates/w-auth-layout-template/w-auth-layout-template';
import type { SharedProps } from '@/types';

/** Tela "Esqueci minha senha" (Inertia: `auth/forgot-password`). */
export function WForgotPasswordPage() {
    const flash = usePage<SharedProps>().props.flash;
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/forgot-password');
    };

    return (
        <WAuthLayoutTemplate title="Esqueci minha senha">
            <div className="mb-8">
                <h1 className="font-heading text-2xl font-bold tracking-tight">Esqueci minha senha</h1>
                <p className="mt-1.5 text-sm text-muted-foreground">
                    Digite o e-mail da conta. Se ele existir por aqui, mandamos um link para você escolher uma senha nova.
                </p>
            </div>

            {flash?.success && (
                <p
                    role="status"
                    className="mb-5 rounded-lg border border-status-approved/30 bg-status-approved-bg px-3 py-2.5 text-sm text-status-approved"
                >
                    {flash.success}
                </p>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="email">E-mail</Label>
                    <div className="relative">
                        <Mail
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            id="email"
                            type="email"
                            autoComplete="username"
                            autoFocus
                            placeholder="voce@empresa.com"
                            className="pl-9"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            aria-invalid={!!errors.email}
                            aria-describedby={errors.email ? 'email-error' : undefined}
                        />
                    </div>
                    {errors.email && (
                        <p id="email-error" className="text-xs font-medium text-destructive" role="alert">
                            {errors.email}
                        </p>
                    )}
                </div>

                <Button type="submit" size="lg" className="w-full" disabled={processing}>
                    {processing ? (
                        <Loader2 className="size-4 animate-spin" aria-label="Enviando" />
                    ) : (
                        <>
                            Enviar o link <ArrowRight className="size-4" aria-hidden />
                        </>
                    )}
                </Button>
            </form>

            <p className="mt-4 text-center text-xs leading-relaxed text-muted-foreground">
                Olhe a caixa de entrada — e o spam, por via das dúvidas.
            </p>

            <WAuthBackLinkAtom href="/admin/login">Voltar para o login</WAuthBackLinkAtom>
        </WAuthLayoutTemplate>
    );
}
