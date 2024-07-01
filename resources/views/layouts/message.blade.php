@if (Session::has('success'))
    <div class="arlet arlet-success">
        {{ Session::get('success') }}
    </div>
@endif

@if (Session::has('error'))
    <div class="arlet arlet-danger">
        {{ Session::get('error') }}
    </div>
@endif
