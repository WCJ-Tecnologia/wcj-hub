import { Link, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, KeyRound, Loader2, Mail } from 'lucide-react';
import { type FormEvent } from 'react';

import { Button } from '@/components/shadcn/button';
import { Input } from '@/components/shadcn/input';
import { Label } from '@/components/shadcn/label';
import { WAuthLayoutTemplate } from '@/components/templates/w-auth-layout-template/w-auth-layout-template';
import type { SharedProps } from '@/types';

/** Tela de login (Inertia: `auth/login`). */
export function WLoginPage() {
    const flash = usePage<SharedProps>().props.flash;
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/admin/login', { onFinish: () => reset('password') });
    };

    return (
        <WAuthLayoutTemplate title="Entrar">
            <div className="mb-8">
                <h1 className="font-heading text-2xl font-bold tracking-tight">Entrar</h1>
                <p className="mt-1.5 text-sm text-muted-foreground">Use o e-mail e a senha da sua conta.</p>
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

                <div className="space-y-2">
                    <div className="flex items-center justify-between gap-3">
                        <Label htmlFor="password">Senha</Label>
                        <Link href="/admin/forgot-password" className="text-xs font-medium text-primary hover:underline">
                            Esqueci minha senha
                        </Link>
                    </div>
                    <div className="relative">
                        <KeyRound
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            placeholder="••••••••"
                            className="pl-9"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            aria-invalid={!!errors.password}
                        />
                    </div>
                    {errors.password && <p className="text-xs font-medium text-destructive">{errors.password}</p>}
                </div>

                <Button type="submit" size="lg" className="w-full" disabled={processing}>
                    {processing ? (
                        <Loader2 className="size-4 animate-spin" aria-label="Entrando" />
                    ) : (
                        <>
                            Entrar <ArrowRight className="size-4" aria-hidden />
                        </>
                    )}
                </Button>
            </form>
        </WAuthLayoutTemplate>
    );
}
