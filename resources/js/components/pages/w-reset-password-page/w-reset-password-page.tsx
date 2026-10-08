import { useForm } from '@inertiajs/react';
import { ArrowRight, KeyRound, Loader2 } from 'lucide-react';
import { type FormEvent } from 'react';

import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { Label } from '@/components/shadcn/label';
import { WAuthBackLinkAtom } from '@/components/atoms/w-auth-back-link-atom/w-auth-back-link-atom';
import { WAuthLayoutTemplate } from '@/components/templates/w-auth-layout-template/w-auth-layout-template';

/** Tela de nova senha pelo link do e-mail (Inertia: `auth/reset-password`). */
export function WResetPasswordPage({ email, token }: { email: string; token: string }) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/reset-password');
    };

    return (
        <WAuthLayoutTemplate title="Nova senha">
            <div className="mb-8">
                <h1 className="font-heading text-2xl font-bold tracking-tight">Escolha uma senha nova</h1>
                <p className="mt-1.5 text-sm text-muted-foreground">
                    Mínimo de 10 caracteres, com letras e números. Ao salvar, as outras sessões da conta são encerradas.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="email">E-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="username"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        aria-invalid={!!errors.email}
                        aria-describedby={errors.email ? 'email-error' : undefined}
                    />
                    {errors.email && (
                        <p id="email-error" className="text-xs font-medium text-destructive" role="alert">
                            {errors.email}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password">Nova senha</Label>
                    <div className="relative">
                        <KeyRound
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            id="password"
                            type="password"
                            autoComplete="new-password"
                            autoFocus
                            className="pl-9"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            aria-invalid={!!errors.password}
                            aria-describedby={errors.password ? 'password-error' : undefined}
                        />
                    </div>
                    {errors.password && (
                        <p id="password-error" className="text-xs font-medium text-destructive" role="alert">
                            {errors.password}
                        </p>
                    )}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">Confirmar senha</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />
                </div>

                <Button type="submit" size="lg" className="w-full" disabled={processing}>
                    {processing ? (
                        <Loader2 className="size-4 animate-spin" aria-label="Salvando" />
                    ) : (
                        <>
                            Salvar senha <ArrowRight className="size-4" aria-hidden />
                        </>
                    )}
                </Button>
            </form>

            <WAuthBackLinkAtom href="/admin/login">Voltar para o login</WAuthBackLinkAtom>
        </WAuthLayoutTemplate>
    );
}
