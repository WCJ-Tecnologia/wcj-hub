import { Head, useForm } from '@inertiajs/react';
import { KeyRound, Loader2, UserRound } from 'lucide-react';
import { type FormEvent } from 'react';

import { FormField } from '@/components/form-field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { PhoneInput } from '@/components/ui/masked-input';
import AppLayout from '@/layouts/app-layout';

interface Props {
    profile: { name: string; email: string | null; phone: string | null; type_label: string };
}

export default function ProfilePage({ profile }: Props) {
    const form = useForm({
        name: profile.name,
        phone: profile.phone ?? '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/admin/profile', {
            preserveScroll: true,
            onSuccess: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <AppLayout eyebrow="Conta" title="Meu perfil" subtitle="Seus dados de acesso" width="narrow">
            <Head title="Meu perfil" />

            <form onSubmit={submit} className="grid max-w-xl gap-5">
                <Card className="gap-0">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <UserRound className="size-4" aria-hidden /> Quem é você
                        </CardTitle>
                        <CardDescription>{profile.type_label}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <FormField label="Nome" htmlFor="name" required error={form.errors.name}>
                            <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                        </FormField>

                        <FormField label="E-mail" htmlFor="email" hint="O e-mail da conta não muda por aqui.">
                            <Input id="email" value={profile.email ?? ''} disabled />
                        </FormField>

                        <FormField label="Telefone" htmlFor="phone" error={form.errors.phone}>
                            <PhoneInput id="phone" value={form.data.phone} onChange={(value) => form.setData('phone', value)} />
                        </FormField>

                        <fieldset className="rounded-lg border border-dashed border-border p-3">
                            <legend className="flex items-center gap-2 px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                <KeyRound className="size-3.5" aria-hidden /> Trocar senha
                            </legend>
                            <div className="space-y-3">
                                <FormField
                                    label="Nova senha"
                                    htmlFor="password"
                                    error={form.errors.password}
                                    hint="Mínimo de 10 caracteres, com letras e números. Deixe em branco para manter a atual. Trocar a senha encerra as outras sessões."
                                >
                                    <Input
                                        id="password"
                                        type="password"
                                        autoComplete="new-password"
                                        value={form.data.password}
                                        onChange={(event) => form.setData('password', event.target.value)}
                                    />
                                </FormField>
                                <FormField label="Confirmar" htmlFor="password_confirmation">
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        value={form.data.password_confirmation}
                                        onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                    />
                                </FormField>
                            </div>
                        </fieldset>
                    </CardContent>
                </Card>

                <div>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? <Loader2 className="size-4 animate-spin" aria-label="Salvando" /> : 'Salvar meu perfil'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
