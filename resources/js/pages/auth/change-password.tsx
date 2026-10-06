import { router, useForm } from '@inertiajs/react';
import { ArrowRight, KeyRound, Loader2 } from 'lucide-react';
import { type FormEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ChangePassword({ firstName }: { firstName: string }) {
    const { data, setData, put, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        put('/admin/first-access');
    };

    return (
        <AuthLayout title="Primeiro acesso">
            <div className="mb-8">
                <h1 className="font-heading text-2xl font-bold tracking-tight">Oi, {firstName}. Só mais um passo</h1>
                <p className="mt-1.5 text-sm text-muted-foreground">
                    A senha temporária já te trouxe até aqui. Agora escolha uma que seja só sua.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="password">Sua senha nova</Label>
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
                            aria-describedby={errors.password ? 'password-error' : 'password-hint'}
                        />
                    </div>
                    {errors.password && (
                        <p id="password-error" className="text-xs font-medium text-destructive" role="alert">
                            {errors.password}
                        </p>
                    )}
                    <p id="password-hint" className="text-xs text-muted-foreground">
                        Pelo menos 10 caracteres, com letras e números, diferente da temporária.
                    </p>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">Confirme a senha</Label>
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
                            Continuar <ArrowRight className="size-4" aria-hidden />
                        </>
                    )}
                </Button>
            </form>

            <p className="mt-6 text-center text-sm text-muted-foreground">
                Não é você, {firstName}?{' '}
                <button type="button" className="font-medium text-primary hover:underline" onClick={() => router.post('/admin/logout')}>
                    Sair e usar outra conta
                </button>
            </p>
        </AuthLayout>
    );
}
