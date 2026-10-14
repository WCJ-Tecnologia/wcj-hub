# WTaskAttachmentsSectionOrganism

Organismo · `organisms/w-task-attachments-section-organism/w-task-attachments-section-organism.tsx` · story `Organisms/Task Attachments Section`

## O que faz

O conteúdo da seção _Anexos_ da ficha do card (F3 §02, §03, §04; P24, P59). A página o põe dentro do `WCollapsibleSectionMolecule`, com o resumo de `attachmentsSummary()` no cabeçalho recolhido.

- Lista (`ul/li`) na ordem em que os anexos entraram (F3 §03): miniatura de 48 px (`<img alt="">`, `object-cover`) quando `is_image`, senão o ícone de arquivo; o nome original como link de download (`<a download={name}>`, P24); tamanho (`formatBytes`) e "por Carla Mendes em 13/10/2026 15:01" (`dateTimeBr`).
- `Badge info` "Capa" no anexo que é a capa (P59); `Badge outline` "Evidência do QA" quando o anexo é de um critério (`criterion_id`, F4).
- Com `canEdit`, só nos anexos de imagem: _Usar como capa_, ou _Tirar a capa_ no que já é (`onSetCover(id | null)`, P59).
- Com `canAttach`: _Anexar arquivo_ (escolhe um ou vários, `onUpload(File[])`) e _Remover_ em cada linha, com o `WConfirmDialogMolecule` destrutivo "Remover <nome>?" / "Remover o anexo? O arquivo será apagado." (`REMOVE_ATTACHMENT_TEXT`, F3 §04). Se o anexo é a capa, a descrição acrescenta "Ele é a capa do card: o card fica sem capa." (P59).
- Vazio: "Nenhum anexo." e _Anexar arquivo_ (F3 §03); sem `canAttach`, só a frase.
- `busy`: botões desabilitados, `aria-busy` no bloco e o `Loader2` no botão que disparou a gravação.
- Erros do servidor do envio (`file`; também `file.N`, `files`, `files.N`, se o B3 validar por arquivo) aparecem embaixo, com `role="alert"`.

`attachmentsSummary(attachments, coverId)`: "3 anexos · capa: tela-guia-ok.png", "1 anexo" ou "Nenhum anexo".

## Props

| Prop          | Tipo                           | Padrão  | Obrigatória | Para quê                                                    |
| ------------- | ------------------------------ | ------- | ----------- | ----------------------------------------------------------- |
| `attachments` | `TaskAttachment[]`             |         | sim         | `detail.attachments`, na ordem em que entraram              |
| `coverId`     | `number \| null`               |         | sim         | `detail.cover_attachment_id` (P59)                          |
| `canAttach`   | `boolean`                      |         | sim         | `abilities.attach`: anexar e remover (P24)                  |
| `canEdit`     | `boolean`                      |         | sim         | `abilities.update`: _Usar como capa_ / _Tirar a capa_ (P59) |
| `busy`        | `boolean`                      | `false` | não         | Uma gravação da ficha está rodando                          |
| `errors`      | `Record<string, string>`       |         | não         | Erros do servidor: `file`                                   |
| `onUpload`    | `(files: File[]) => void`      |         | sim         | Os arquivos escolhidos, todos de uma vez                    |
| `onRemove`    | `(id: number) => void`         |         | sim         | Depois do _Remover_ do diálogo                              |
| `onSetCover`  | `(id: number \| null) => void` |         | sim         | `null` = tirar a capa                                       |

## Exemplo

```tsx
<WCollapsibleSectionMolecule title="Anexos" summary={attachmentsSummary(detail.attachments, detail.cover_attachment_id)}>
    <WTaskAttachmentsSectionOrganism
        attachments={detail.attachments}
        coverId={detail.cover_attachment_id}
        canAttach={detail.abilities.attach}
        canEdit={detail.abilities.update}
        busy={processing}
        errors={errors}
        onUpload={(files) => router.post(`${detail.url}/anexos`, { files }, { forceFormData: true })}
        onRemove={(id) => router.delete(`${detail.url}/anexos/${id}`)}
        onSetCover={(id) => router.patch(detail.url, { cover_attachment_id: id })}
    />
</WCollapsibleSectionMolecule>
```

## Cuidados

- O organismo não grava nem chama `router`: devolve a intenção. Tirar a capa ao remover o anexo é do servidor (P59).
- O `<input type="file">` fica escondido (`hidden`, fora da árvore de acessibilidade) e o _Anexar arquivo_ é um botão que o abre; o campo é limpo depois de cada escolha, para o mesmo arquivo poder ser escolhido de novo.
- Os botões da linha têm o nome do arquivo em `sr-only` ("Remover guia-modelo.pdf"), para o leitor de tela distinguir as linhas.
- Imagem que não carrega vira o ícone `ImageOff`.
- Limite de tamanho e tipos aceitos são do servidor; a tela só mostra o erro que vier.
