@php
    $profileName = trim((string) ($order->user?->name ?? ''));
    $isReadyToWear = strtolower((string) ($order->order_type ?? '')) === 'ready_to_wear';
    $genderLabel = match (strtolower((string) ($order->child_gender ?? ''))) {
        'boy' => 'Мальчик',
        'girl' => 'Девочка',
        default => null,
    };
    $fmt = fn ($v) => $v !== null && trim((string) $v) !== '' ? trim((string) $v) : null;
@endphp

<p>Здравствуйте@if($profileName !== ''), {{ $profileName }}@endif!</p>

@if($isReadyToWear)
<p>Спасибо за заказ готовых изделий. Заказ <strong>№{{ $order->id }}</strong> принят к обработке. Сумма: <strong>{{ $order->total_amount }}&nbsp;₽</strong>.</p>
@else
<p>Спасибо за заказ пошива на заказ. Заказ <strong>№{{ $order->id }}</strong> принят к обработке. Сумма: <strong>{{ $order->total_amount }}&nbsp;₽</strong>.</p>
@endif

@if($order->items->isNotEmpty())
<p><strong>Состав заказа</strong></p>
<ul>
@foreach($order->items as $item)
<li>
{{ $item->product_name }} — {{ $item->quantity }}&nbsp;шт.
@if(($lineSize = $fmt($item->size_override ?? null)))
, размер: {{ $lineSize }}
@endif
@if(($lineComment = $fmt($item->line_comment ?? null)))
<br><span style="color:#444;font-size:13px;">Комментарий к позиции: {{ $lineComment }}</span>
@endif
</li>
@endforeach
</ul>
@endif

@if(!$isReadyToWear)
<p><strong>Данные для пошива</strong></p>
<table cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 12px 0;">
<tbody>
@if($fmt($order->child_full_name))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Ребёнок</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->child_full_name) }}</td></tr>
@endif
@if($genderLabel)
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Пол</strong></td><td style="padding:2px 0 6px 0;">{{ $genderLabel }}</td></tr>
@endif
@if($fmt($order->settlement))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Населённый пункт</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->settlement) }}</td></tr>
@endif
@if($fmt($order->school))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Школа</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->school) }}</td></tr>
@endif
@if($fmt($order->class_num))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Класс</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->class_num) }}@if($fmt($order->class_letter ?? null)){{ $fmt($order->class_letter) }}@endif</td></tr>
@elseif($fmt($order->class_letter ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Литера</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->class_letter) }}</td></tr>
@endif
@if($fmt($order->school_year))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Учебный год</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->school_year) }}</td></tr>
@endif
@if($fmt($order->size_from_table))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Размер по таблице</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->size_from_table) }}</td></tr>
@endif
@if($fmt($order->height_cm ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Рост, см</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->height_cm) }}</td></tr>
@endif
@if($fmt($order->chest_cm ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Обхват груди</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->chest_cm) }}</td></tr>
@endif
@if($fmt($order->waist_cm ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Обхват талии</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->waist_cm) }}</td></tr>
@endif
@if($fmt($order->hips_cm ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Обхват бёдер</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->hips_cm) }}</td></tr>
@endif
@if($fmt($order->figure_comment ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Особенности фигуры</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->figure_comment) }}</td></tr>
@endif
@if($fmt($order->kit_comment ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Комментарий к комплекту</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->kit_comment) }}</td></tr>
@endif
@if($fmt($order->parent_phone ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Телефон в заказе</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->parent_phone) }}</td></tr>
@endif
@if($fmt($order->parent_email ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Email в заказе</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->parent_email) }}</td></tr>
@endif
@if($fmt($order->messenger_max ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>MAX</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->messenger_max) }}</td></tr>
@endif
@if($fmt($order->messenger_telegram ?? null))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Telegram</strong></td><td style="padding:2px 0 6px 0;">{{ $fmt($order->messenger_telegram) }}</td></tr>
@endif
@if(!$order->recipient_is_customer && ($fmt($order->recipient_name ?? null) || $fmt($order->recipient_phone ?? null)))
<tr><td style="padding:2px 16px 6px 0;vertical-align:top;"><strong>Получатель</strong></td><td style="padding:2px 0 6px 0;">
@if($fmt($order->recipient_name ?? null)){{ $fmt($order->recipient_name) }}@endif
@if($fmt($order->recipient_phone ?? null))
@if($fmt($order->recipient_name ?? null)), @endif{{ $fmt($order->recipient_phone) }}
@endif
</td></tr>
@endif
</tbody>
</table>
@endif

<p>Мы свяжемся с вами при необходимости по указанным контактам.</p>

<p>С уважением,<br>{{ config('app.name') }}</p>
