@php
$messages = [
    'tabby' => 'Sorry, Tabby is unable to approve this purchase. Please use an alternative payment method for your order.',
    'tamara' => 'Sorry, Tamara is unable to approve this purchase. Please use an alternative payment method for your order.',
    'clickpay' => 'Sorry, Click Pay is unable to approve this purchase. Please use an alternative payment method for your order.',
];
@endphp
@extends('layouts.app')

@section('content')
            <link rel="stylesheet" href="{{ asset('css/payment-success.css') }}">

            <div class="container payment-success-container">
                <div class="text-center">
        @if($payment_type === 'tabby')
            <img src="{{ asset('images/tabby.svg') }}" alt="Tabby" class="tabby-logo mb-3">
        @elseif($payment_type === 'tamara')
            <img src="{{ asset('images/tamara.png') }}" alt="Tamara" class="tabby-logo mb-3">
        @endif

                    <div class="checkmark-circle mb-4">
                        @if($status == 'paid' || $status == 'success')
                            <img src="{{ asset('images/success.webp') }}" alt="Success" class="checkmark">
                        @else
                            <img src="{{ asset('images/close.png') }}" alt="Failed" class="checkmark">
                        @endif
                    </div>

                    @if($status == 'paid' || $status == 'success')
                        <h4 class="fw-bold">Payment Successful</h4>
                        <p class="text-muted">Your payment is successfully done, you can check your summary now.</p>
                    @elseif($status == 'failed')
                        <h4 class="fw-bold">Payment Canceled</h4>
                        <p class="text-muted">You aborted the payment. Please retry or choose another payment method.</p>
                    @elseif($status == 'failed')
                            <h4 class="fw-bold">Payment Failed</h4>
                        <p class="text-muted">
                            {{ $messages[$payment_type] ?? 'Sorry, your payment is unable to be approved. Please use an alternative payment method for your order.' }}
                        </p>
                    @else
                        <h4 class="fw-bold">There is something wrong</h4>
                        <p class="text-muted">Your payment was unsuccessful, please try again.</p>
                    @endif
                </div>

                {{-- @if($status=='success') --}}
                    <div class="card price-card mt-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Price details</h5>
                            <div class="d-flex justify-content-between">
                                <span>Subtotal </span>
                                <span>{{ number_format($priceWithoutTax, 2) ?? 0 }} SAR</span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <span>VAT 15%</span>
                                <span>{{ number_format($taxAmount, 2) ?? 0 }} SAR</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total (Incl. VAT)</span>
                                <span>{{ number_format($payment->amount ?? 0, 2) ?? 0 }} SAR</span>
                            </div>
                        </div>
                    </div>

                    <div class="card payment-card mt-4 mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Payment details</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
    @if($payment_type === 'tabby')
        <img src="{{ asset('images/tabby.svg') }}" alt="Tabby" class="tabby-icon">
    @elseif($payment_type === 'tamara')
        <img src="{{ asset('images/tamara.png') }}" alt="Tamara" class="tabby-icon">
    @elseif($payment_type === 'clickpay'||$payment_type === 'E-Commerce'||$payment_type === 'E-COMMERCE')
        <img src="{{ asset('images/clickpay.png') }}" alt="ClickPay" class="tabby-icon">
    @endif
                                <span>{{ $payment->phone ?? 0 }}</span>
                                </div>
                            <span>{{ number_format( $payment->amount ?? 0, 2) }} SAR</span>

                            </div>
                        </div>
                    </div>
                {{-- @endif --}}

                <div class="text-center">
                    <a href="{{ route('home') }}" class="btn btn-primary w-100 rounded-pill">Back to Home</a>
                </div>
            </div>
@endsection
