@props(['title', 'description' => null])
<section class="form-section"><header><h2>{{ $title }}</h2>@if($description)<p>{{ $description }}</p>@endif</header><div class="form-fields">{{ $slot }}</div></section>
