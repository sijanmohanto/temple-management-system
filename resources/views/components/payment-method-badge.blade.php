@props(['method' => 'cash', 'class' => '', 'variant' => 'short', 'showLabel' => true])
@include('partials.payment-method-badge', ['method' => $method, 'class' => $class, 'variant' => $variant, 'showLabel' => $showLabel])
