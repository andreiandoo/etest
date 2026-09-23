@php
    use App\Services\Settings;

    $analyticsEnabled = Settings::boolean('analytics_enabled', false);
    $gaId = Settings::string('google_analytics_id');
    $gtmId = Settings::string('google_tag_manager_id');
    $pixelId = Settings::string('facebook_pixel_id');
    $siteVerification = Settings::string('google_site_verification');
@endphp

{{-- Verificarea Search Console nu e urmărire, deci nu depinde de comutator. --}}
@if($siteVerification !== '')
    <meta name="google-site-verification" content="{{ $siteVerification }}">
@endif

@if($analyticsEnabled)
    @if($gtmId !== '')
        <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
        var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
        j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');
        </script>
    @endif

    @if($gaId !== '')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
        </script>
    @endif

    @if($pixelId !== '')
        <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $pixelId }}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1" alt=""></noscript>
    @endif
@endif
