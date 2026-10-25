@extends('layouts.guest')

@section('title', __('common.app_name') . ' — ' . __('common.temple_motto'))

@section('content')
<div class="row align-items-center g-5 py-4">
    <div class="col-lg-7">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-warning bg-opacity-25 text-dark fw-semibold small mb-3 border border-warning">
            <span class="temple-emblem" style="width: 24px; height: 24px; font-size: 0.8rem;">ॐ</span>
            <span>{{ __('common.temple_motto') }}</span>
        </div>
        <h1 class="display-5 fw-bold text-dark lh-sm mb-3">
            {{ __('common.app_name') }}
        </h1>
        <p class="lead text-secondary mb-4">
            {{ __('common.app_subtitle') }}. Dedicated to maintaining transparent community administration, religious festival coordination, and devotee engagement.
        </p>

        <div class="d-flex flex-wrap gap-3 mb-5">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-lg temple-btn-maroon px-4 fw-bold">
                    <i class="bi bi-speedometer2 me-2"></i> {{ __('nav.dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-lg temple-btn-maroon px-4 fw-bold">
                    <i class="bi bi-box-arrow-in-right me-2"></i> {{ __('nav.login') }}
                </a>
            @endauth
            <a href="#aboutSection" class="btn btn-lg btn-outline-secondary px-4 fw-semibold">
                <i class="bi bi-info-circle me-1"></i> {{ __('common.details') }}
            </a>
        </div>

        <!-- Highlights Row -->
        <div class="row g-3 pt-2">
            <div class="col-sm-4">
                <div class="border-start border-3 border-danger ps-3">
                    <h5 class="fw-bold mb-1 text-dark">১০০% স্বচ্ছতা</h5>
                    <p class="text-muted small mb-0">অডিট যাচাইকৃত কেন্দ্রীয় আর্থিক খতিয়ান</p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="border-start border-3 border-warning ps-3">
                    <h5 class="fw-bold mb-1 text-dark">ভক্ত সেবা</h5>
                    <p class="text-muted small mb-0">অনলাইন মানত, রসিদ ও পারিবারিক ডিরেক্টরি</p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="border-start border-3 border-primary ps-3">
                    <h5 class="fw-bold mb-1 text-dark">দ্বিভাষিক পোর্টাল</h5>
                    <p class="text-muted small mb-0">বাংলা ও ইংরেজি উভয় ভাষায় সম্পূর্ণ সহায়তা</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-header p-4 text-white text-center" style="background: linear-gradient(135deg, var(--temple-maroon), var(--temple-gold));">
                <div class="temple-emblem mx-auto mb-2" style="width: 52px; height: 52px; font-size: 1.6rem;">ॐ</div>
                <h4 class="fw-bold mb-0">দৈনিক আরতি ও সেবা সময়সূচি</h4>
                <small class="text-warning">Daily Temple Schedule</small>
            </div>
            <div class="card-body p-4 bg-white">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <div>
                            <span class="fw-bold text-dark d-block">প্রাতঃ আরতি ও মঙ্গলারতি</span>
                            <small class="text-muted">Mangal Aarti & Morning Prayers</small>
                        </div>
                        <span class="badge bg-danger bg-opacity-10 text-danger fs-6 px-3 py-2 rounded-pill">০৬:০০ AM</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <div>
                            <span class="fw-bold text-dark d-block">মধ্যাহ্ন পূজা ও ভোগরাগ</span>
                            <small class="text-muted">Noon Puja & Bhog Offering</small>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning fs-6 px-3 py-2 rounded-pill">১২:০০ PM</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <div>
                            <span class="fw-bold text-dark d-block">সন্ধ্যা আরতি ও প্রসাদ বিতরণ</span>
                            <small class="text-muted">Sandhya Aarti & Prasad Seva</small>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2 rounded-pill">০৬:৩০ PM</span>
                    </li>
                </ul>

                <div class="mt-4 p-3 rounded-3 bg-light border text-center">
                    <small class="text-muted d-block">
                        <i class="bi bi-shield-check text-success me-1"></i> Phase 1 System Foundation Active
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Long-term Modules Overview Grid -->
<div class="row g-4 pt-5 border-top mt-4" id="aboutSection">
    <div class="col-12 text-center mb-2">
        <h3 class="fw-bold text-dark">সিস্টেম কাঠামো ও দীর্ঘমেয়াদী মডিউল পরিকল্পনা</h3>
        <p class="text-muted">Long-Term Modular Temple Architecture</p>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-3">
            <div class="card-body">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger mb-3">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h5 class="fw-bold">নিরাপত্তা ও ভূমিকাভিত্তিক অধিকার</h5>
                <p class="text-muted small">
                    প্রধান প্রশাসক, কোষাধ্যক্ষ, কার্যনির্বাহী কমিটি ও সাধারণ ভক্তদের জন্য পৃথক ও সুরক্ষিত পারমিশন ব্যবস্থা।
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-3">
            <div class="card-body">
                <div class="stat-icon bg-success bg-opacity-10 text-success mb-3">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h5 class="fw-bold">দ্বৈত-দাখিলা আর্থিক লেজার</h5>
                <p class="text-muted small">
                    সকল লেনদেন অপরিবর্তনীয় অডিট খতিয়ানে সংরক্ষিত থাকবে, যার ফলে একই অনুদান বা ভাউচার পুনরায় যুক্ত হওয়া অসম্ভব।
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm rounded-4 p-3">
            <div class="card-body">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary mb-3">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h5 class="fw-bold">ভক্ত পরিবার ও কমিটি ডিরেক্টরি</h5>
                <p class="text-muted small">
                    সিস্টেম লগইন থেকে পৃথক রেখে ভক্তদের ব্যক্তিগত ও পারিবারিক ডাটাবেস সংরক্ষণ এবং মাসিক চাঁদা ব্যবস্থাপনা।
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
