{{-- Decorative script font for loan page titles. Add class "loan-script-title" to the heading. --}}
@once
    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fleur+De+Leah&display=swap" rel="stylesheet">

        <style>
            .page-header .loan-script-title {
                font-family: 'Fleur De Leah', cursive;
                font-size: 38px;
                font-weight: 400;
                letter-spacing: 0;
                line-height: 1.15;
            }

            @media (max-width: 767.98px) {
                .page-header .loan-script-title {
                    font-size: 32px;
                }
            }
        </style>
    @endpush
@endonce
