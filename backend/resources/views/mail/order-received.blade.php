<p>Здравствуйте@if($order->parent_full_name), {{ $order->parent_full_name }}@endif!</p>

<p>Ваш заказ <strong>№{{ $order->id }}</strong> принят. Сумма: <strong>{{ $order->total_amount }} ₽</strong>.</p>

@if($order->items->isNotEmpty())
<p>Состав заказа:</p>
<ul>
@foreach($order->items as $item)
<li>{{ $item->product_name }} — {{ $item->quantity }} шт.</li>
@endforeach
</ul>
@endif

<p>Мы свяжемся с вами при необходимости по указанным контактам.</p>

<p>С уважением,<br>{{ config('app.name') }}</p>
