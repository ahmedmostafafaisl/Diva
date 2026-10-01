@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/payment-success.css') }}">

    <div class="container payment-success-container">
        <div class="text-center">
            <img src="{{ asset('images/tabby.svg') }}" alt="Tabby" class="tabby-logo mb-3">
            <div class="checkmark-circle mb-4">
                <img src="{{ asset('images/success.webp') }}" alt="Success" class="checkmark">
            </div>
            <h4 class="fw-bold">Payment Successful</h4>
            <p class="text-muted">Your payment is successfully done, you can check your summary now.</p>
        </div>

        <div class="card price-card mt-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Price details</h5>
                <div class="d-flex justify-content-between">
                    <span>Subtotal (2 Items)</span>
                    <span>﷼ 2,798.49</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Shipping</span>
                    <span>﷼ 100.00</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>VAT 15%</span>
                    <span>﷼ 10.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total (Incl. VAT)</span>
                    <span>﷼ 2,798.49</span>
                </div>
            </div>
        </div>

        <div class="card payment-card mt-4 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Payment details</h5>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <img src="{{ asset('images/tabby.svg') }}" alt="Tabby" class="tabby-icon">
                        <span>****3458</span>
                    </div>
                    <span>﷼ 2,798.49</span>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('home') }}" class="btn btn-primary w-100 rounded-pill">Back to Home</a>
        </div>
    </div>
@endsection
