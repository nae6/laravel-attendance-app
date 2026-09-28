@props(['currentMonth', 'prevUrl', 'nextUrl'])

<div class="month-nav font-setting">
    <a href="{{ $prevUrl }}">
        <img src="{{ asset('images/arrow.png') }}" alt="left-arrow" class="arrow">
        前月
    </a>
    <span class="display-month">
        <img src="{{ asset('images/calender.png') }}" alt="calender-icon">
        {{ $currentMonth->format('Y/m') }}
    </span>
    <a href="{{ $nextUrl }}">
        翌月
        <img src="{{ asset('images/arrow.png') }}" alt="right-arrow" class="arrow arrow__right">
    </a>
</div>
