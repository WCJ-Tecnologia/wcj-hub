# WDatePickerMolecule

Molécula · `molecules/w-date-picker-molecule/w-date-picker-molecule.tsx` · story `Molecules/Date Picker`

## O que faz

O Date Picker do shadcn (`Popover` + `Calendar`, sobre o `react-day-picker`) em português. O campo mostra `dd/mm/aaaa`, e a semana do calendário segue o pt-BR.

Ele **lê e devolve `AAAA-MM-DD`**, o mesmo formato do `<input type="date">` e do que o servidor recebe (data de negócio, `date`). Trocar um pelo outro não muda nada no controller. O botão de limpar devolve `null`.

**Use** em todo campo de data, de filtro ou de formulário. Não use `<Input type="date">`: o calendário nativo fica fora do tema e o formato muda com a língua do navegador.

## Props

| Prop          | Tipo                              | Padrão         | Obrigatória | Para quê                                                                  |
| ------------- | --------------------------------- | -------------- | ----------- | ------------------------------------------------------------------------- |
| `value`       | `string \| null \| undefined`     |                | sim         | `AAAA-MM-DD`; vazio = sem data                                            |
| `onChange`    | `(value: string \| null) => void` |                | sim         | Recebe `AAAA-MM-DD`, ou `null` ao limpar                                  |
| `label`       | `string`                          |                | não         | Nome do campo para leitor de tela, quando não há `<label>` (ex.: filtros) |
| `id`          | `string`                          |                | não         | Para ligar a um `<label htmlFor>` / `WFormFieldMolecule`                  |
| `placeholder` | `string`                          | `'dd/mm/aaaa'` | não         | Texto com o campo vazio                                                   |
| `disabled`    | `boolean`                         |                | não         |                                                                           |
| `clearable`   | `boolean`                         | `true`         | não         | Mostra o X de limpar quando há data                                       |
| `className`   | `string`                          |                | não         | Vai no wrapper                                                            |

## Exemplos

Filtro (como na auditoria):

```tsx
import { WDatePickerMolecule } from '@/components/molecules/w-date-picker-molecule/w-date-picker-molecule';

<WDatePickerMolecule
    value={filters.from ? String(filters.from) : null}
    onChange={(value) => navigate({ from: value ?? '' })}
    label="De"
    placeholder="De (dd/mm/aaaa)"
/>;
```

Formulário com `useForm`:

```tsx
<WFormFieldMolecule label="Data de entrega" htmlFor="due_on" error={form.errors.due_on}>
    <WDatePickerMolecule id="due_on" value={form.data.due_on} onChange={(value) => form.setData('due_on', value ?? '')} />
</WFormFieldMolecule>
```

## Cuidados

- A data é de **negócio** (sem hora). O componente monta a data no fuso do navegador e nunca passa por UTC, então não "volta um dia".
- Validação de intervalo (De ≤ Até, data mínima) é do servidor. O componente não bloqueia nada.
- Teclado: Enter/Espaço abre o calendário, as setas andam entre os dias, Enter escolhe e ESC fecha e devolve o foco ao campo.
